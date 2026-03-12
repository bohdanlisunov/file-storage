<?php
namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\RabbitMQService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller {
    public function __construct(private readonly RabbitMQService $mq) {}

    public function index(Request $request) {
        $q = UploadedFile::orderBy('created_at','desc');
        if ($s = $request->get('search')) $q->where('original_name','like',"%$s%");
        if ($t = $request->get('type')) {
            if ($t==='pdf') $q->where('mime_type','application/pdf');
            elseif ($t==='docx') $q->whereIn('mime_type',['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/msword']);
        }
        $files = $q->paginate(15)->withQueryString();
        return view('files.index', compact('files'));
    }

    public function store(Request $request): JsonResponse {
        $request->validate([
            'file' => ['required','file','max:'.(int)env('MAX_FILE_SIZE_KB',10240),'mimes:pdf,doc,docx'],
        ]);
        try {
            $file = $request->file('file');
            $storedName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('uploads', $storedName, 'local');
            $rec = UploadedFile::create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'file_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'expires_at'    => now()->addHours(24),
            ]);
            return response()->json(['success'=>true,'message'=>'Uploaded successfully.','file'=>[
                'id'=>$rec->id,'original_name'=>$rec->original_name,'size'=>$rec->human_size,
                'extension'=>$rec->extension,'created_at'=>$rec->created_at->format('d.m.Y H:i'),
                'expires_at'=>$rec->expires_at->format('d.m.Y H:i'),'time_remaining'=>$rec->time_remaining,
            ]],201);
        } catch(\Exception $e) {
            Log::error('Upload failed',$e->getMessage() ? ['e'=>$e->getMessage()] : []);
            return response()->json(['success'=>false,'message'=>'Upload failed.'],500);
        }
    }

    public function download(UploadedFile $file) {
        if (!Storage::disk('local')->exists($file->file_path)) abort(404);
        return Storage::disk('local')->download($file->file_path, $file->original_name);
    }

    public function destroy(UploadedFile $file): JsonResponse {
        try {
            $data = $file->toArray();
            if (Storage::disk('local')->exists($file->file_path))
                Storage::disk('local')->delete($file->file_path);
            $file->delete();
            $this->mq->publishFileDeletionNotification($data,'manual');
            return response()->json(['success'=>true,'message'=>"File \"{$data['original_name']}\" deleted."]);
        } catch(\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Delete failed.'],500);
        }
    }
}
