<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** 更新服務 job 的通知紀錄（見 UpdateServiceController::notify） */
class UpdateJobNotification extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
