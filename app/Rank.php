<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    const KEY_GENUS = 'genus';
    const KEY_SPECIES = 'species';

    // ICNCP 栽培植物
    const KEY_CULTIVAR_GROUP = 'cultivar-group';
    const KEY_CULTIVAR = 'cultivar';
    const CULTIVAR_KEYS = [self::KEY_CULTIVAR_GROUP, self::KEY_CULTIVAR];

    // 前綴分類群可選的階層範圍（屬 ~ 種下）
    const CV_TAXON_RANK_ID_MIN = 30;
    const CV_TAXON_RANK_ID_MAX = 42;

    public static function isCultivarKey(?string $key): bool
    {
        return in_array($key, self::CULTIVAR_KEYS, true);
    }

    protected $casts = [
        'display' => 'array'
    ];
}
