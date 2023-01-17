<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportUsageLog extends Model
{
    const ACTION_APPEND = 1;
    const ACTION_OVERWRITE = 2;

    const UPDATED_AT = null;
}
