<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportUsageLog extends Model
{
    # 匯入異名表
    const ACTION_FIRST_IMPORT = 0;
    const ACTION_APPEND = 1;
    const ACTION_OVERWRITE = 2;

    # 編輯異名表
    const ACTION_USAGE_ADD = 3;
    const ACTION_USAGE_UPDATE = 4;
    const ACTION_USAGE_DELETE = 5;

    # 編輯俗名
    const ACTION_COMMON_NAME_UPDATE = 6;

    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function taxonName()
    {
        return $this->belongsTo(TaxonName::class,'taxon_name_id');
    }

}
