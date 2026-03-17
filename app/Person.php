<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Console\Commands\UpdatePersonSearchRaw;

class Person extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'persons';

    public function references()
    {
        return $this->hasMany(Reference::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_numeric_code', 'numeric_code');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 比對作者姓名 (忽略 . 空白 -，並依精準度長度排序)
     */
    public function scopeMatchFuzzyName($query, $given, $family)
    {
        // 1. PHP 端：清除查詢字串的符號
        $cleanGiven = str_replace(['.', ' ', '-'], '', $given ?? '');
        $cleanFamily = $family ?? '';

        if (empty($cleanGiven) || empty($cleanFamily)) {
            return $query->whereRaw('1 = 0'); // 如果缺少名字，直接回傳空結果
        }

        // 2. Query 端：去符號 + 模糊比對 + 長度排序
        return $query->whereRaw(
                "REPLACE(REPLACE(REPLACE(CONCAT(IFNULL(first_name,''), IFNULL(middle_name,'')), '-', ''), ' ', ''), '.', '') LIKE ?", 
                ["%$cleanGiven%"]
            )
            ->where('last_name', 'like', "%$cleanFamily%")
            ->orderByRaw("LENGTH(REPLACE(REPLACE(REPLACE(CONCAT(IFNULL(first_name,''), IFNULL(middle_name,'')), '-', ''), ' ', ''), '.', '')) ASC");
    }

    protected static function booted()
    {
        static::saving(function (Person $person) {
            $person->search_raw = UpdatePersonSearchRaw::generateSearchRaw($person);
        });
    }

}