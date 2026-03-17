<?php

namespace App\Console\Commands;

use App\Person;
use Illuminate\Console\Command;

class UpdatePersonSearchRaw extends Command
{
    protected $signature = 'person:update-search-raw';
    protected $description = '更新所有 Person 的 search_raw 欄位（含去音標版本）';

    public function handle()
    {
        $count = Person::count();
        $bar = $this->output->createProgressBar($count);

        Person::chunk(500, function ($persons) use ($bar) {
            foreach ($persons as $person) {
                $person->search_raw = self::generateSearchRaw($person);
                $person->saveQuietly(); // 不觸發 event 避免重複
            }
            $bar->advance(500);
        });

        $bar->finish();
        $this->newLine();
        $this->info("已更新 {$count} 筆 Person search_raw");
    }

    public static function generateSearchRaw(Person $person): string
    {
        $lastName   = $person->last_name ?? '';
        $firstName  = $person->first_name ?? '';
        $middleName = $person->middle_name ?? '';
        $abbrName   = $person->abbreviation_name ?? '';
        $originalFn = $person->original_full_name ?? '';
        $otherNames = $person->other_names ?? '';

        // 1. 標準空格版（去標點）
        $standard = preg_replace('/[\p{P}\p{S}]/u', ' ', 
            implode(' ', array_filter([$lastName, $firstName, $middleName, $abbrName, $originalFn, $otherNames]))
        );

        // 2. 核心連字 A：姓 + 名
        $tightA = preg_replace('/[\p{P}\p{S}\s]/u', '', $lastName . $firstName);

        // 3. 核心連字 B：名 + 姓
        $tightB = preg_replace('/[\p{P}\p{S}\s]/u', '', $firstName . $lastName);

        // 4. 中文原始內容
        $chinese = $originalFn;

        // 5. 去音標版（姓 + 名 + 中間名）
        $ascii = self::toAscii(implode(' ', array_filter([$lastName, $firstName, $middleName])));

        // 6. 去音標連字版
        $asciiTightA = preg_replace('/[\p{P}\p{S}\s]/u', '', self::toAscii($lastName . $firstName));
        $asciiTightB = preg_replace('/[\p{P}\p{S}\s]/u', '', self::toAscii($firstName . $lastName));

        $raw = implode(' ', array_filter([
            $standard,
            $tightA,
            $tightB,
            $chinese,
            $ascii,
            $asciiTightA,
            $asciiTightB,
        ]));

        return mb_strtolower($raw, 'UTF-8');
    }

    /**
     * 去音標：Ôuchi → Ouchi, Müller → Muller, ß → ss
     */
    public static function toAscii(string $str): string
    {
        if (function_exists('transliterator_transliterate')) {
            return transliterator_transliterate('Any-Latin; Latin-ASCII', $str);
        }
        // fallback：iconv 方式（較不完整但堪用）
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    }
}