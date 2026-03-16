<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // 關鍵：註冊模型事件
    protected static function booted()
    {
        static::saving(function ($person) {
            // 在儲存（新增或更新）前，自動產生搜尋字串
            $person->search_raw = $person->generateSearchRaw();
        });
    }

    /**
     * 產生搜尋用的原始字串 (邏輯與批次更新 SQL 一致)
     * SQL
     */
    // UPDATE persons
    // SET search_raw = LOWER(CONCAT(
    //     /* 1. 標準空格版 (最基礎，支援所有單字搜尋) */
    //     REGEXP_REPLACE(CONCAT_WS(' ', last_name, first_name, middle_name, abbreviation_name, original_full_name, other_names), '[[:punct:]]', ' '),
    //     ' ',
    //     /* 2. 核心連字 A：姓 + 名 (最常用，如 tsaiszyi) */
    //     REGEXP_REPLACE(CONCAT(last_name, first_name), '[[:punct:]\\s]', ''),
    //     ' ',
    //     /* 3. 核心連字 B：名 + 姓 (外國搜尋習慣，如 szyitsai) */
    //     REGEXP_REPLACE(CONCAT(first_name, last_name), '[[:punct:]\\s]', ''),
    //     ' ',
    //     /* 4. 中文原始內容 (確保不被拆散) */
    //     IFNULL(original_full_name, '')
    // ));
    public function generateSearchRaw()
    {
        $last = $this->last_name ?? '';
        $first = $this->first_name ?? '';
        $middle = $this->middle_name ?? '';
        $abbr = $this->abbreviation_name ?? '';
        $original = $this->original_full_name ?? '';
        $others = $this->other_names ?? '';

        // 1. 準備基礎字串 (空格版)：包含所有欄位，標點符號換空格
        $combined = implode(' ', [$last, $first, $middle, $abbr, $original, $others]);
        $spaceVersion = preg_replace('/[\p{P}\p{S}]/u', ' ', $combined);

        // 2. 連字版 A：姓 + 名 (例如 tsaiszyi)
        $tightLastFirst = preg_replace('/[\p{P}\p{S}\s]/u', '', $last . $first);
        
        // 3. 連字版 B：名 + 姓 (例如 szyitsai)
        $tightFirstLast = preg_replace('/[\p{P}\p{S}\s]/u', '', $first . $last);

        // 4. 組合所有版本，轉小寫並清理多餘空格
        // 特別加入 $original 確保中文不被標點過濾影響連貫性
        $finalString = mb_strtolower(
            $spaceVersion . ' ' . $tightLastFirst . ' ' . $tightFirstLast . ' ' . $original, 
            'UTF-8'
        );

        // 清理重複空格並回傳
        return preg_replace('/\s+/', ' ', trim($finalString));
    }
}