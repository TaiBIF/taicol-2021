<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'status', 'phase', 'user_id', 'file_path', 'original_filename',
        'error_file_path', 'total_rows', 'processed_rows', 'success_count',
        'error_message', 'started_at', 'completed_at', 'dismissed_at', 'cancel_requested_at', 'context'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'processed_rows' => 'integer',
        'total_rows' => 'integer',
        'cancel_requested_at' => 'datetime',
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}