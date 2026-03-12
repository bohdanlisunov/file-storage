<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class UploadedFile extends Model {
    protected $table = 'uploaded_files';
    protected $fillable = ['original_name','stored_name','file_path','mime_type','size','expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function scopeExpired($query) {
        return $query->where('expires_at', '<=', Carbon::now());
    }
    public function isExpired(): bool { return $this->expires_at->isPast(); }

    public function getHumanSizeAttribute(): string {
        $b = $this->size;
        if ($b >= 1048576) return number_format($b/1048576,2).' MB';
        if ($b >= 1024)    return number_format($b/1024,2).' KB';
        return $b.' B';
    }
    public function getExtensionAttribute(): string {
        return match($this->mime_type) {
            'application/pdf' => 'PDF',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/msword' => 'DOC',
            default => strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION)),
        };
    }
    public function getTimeRemainingAttribute(): string {
        if ($this->isExpired()) return 'Expired';
        $diff = Carbon::now()->diff($this->expires_at);
        if ($diff->h > 0) return $diff->h.'h '.$diff->i.'m remaining';
        return $diff->i.'m remaining';
    }
}
