<?php
namespace App\Console\Commands;

use App\Models\UploadedFile;
use App\Services\RabbitMQService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteExpiredFiles extends Command {
    protected $signature   = 'files:delete-expired';
    protected $description = 'Delete files expired after 24 hours and notify via RabbitMQ';

    public function __construct(private readonly RabbitMQService $mq) {
        parent::__construct();
    }

    public function handle(): int {
        $expired = UploadedFile::expired()->get();
        if ($expired->isEmpty()) {
            $this->info('No expired files.');
            return self::SUCCESS;
        }
        $this->info("Deleting {$expired->count()} expired file(s)...");
        foreach ($expired as $file) {
            try {
                $data = $file->toArray();
                if (Storage::disk('local')->exists($file->file_path))
                    Storage::disk('local')->delete($file->file_path);
                $file->delete();
                $this->mq->publishFileDeletionNotification($data, 'expired');
                $this->line("  ✓ Deleted: {$data['original_name']}");
                Log::info('Expired file deleted', ['id'=>$data['id']]);
            } catch(\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }
        return self::SUCCESS;
    }
}
