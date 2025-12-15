<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportAiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_type',
        'job_class',
        'status',
        'user_id',
        'file_path',
        'gemini_file_uri',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
        'result_summary'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // 關聯到使用者
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 計算處理時間
    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        return null;
    }

    // 狀態檢查方法
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    // Scope 查詢
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

}
