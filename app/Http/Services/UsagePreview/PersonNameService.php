<?php

namespace App\Http\Services\UsagePreview;
use Illuminate\Support\Facades\Log;

use App\Http\Services\UsagePreview\Traits\ArrayConversionTrait;

class PersonNameService
{
    use ArrayConversionTrait;

    /**
     * 將名字轉換為縮寫（根據 Python 邏輯）
     */
    public function toFirstnameAbbr(?string $name): string
    {
        if (empty($name)) {
            return "";
        }
        
        // 分割但保留分隔符（空格和連字符）
        $parts = preg_split('/(\s|-)/', trim($name), -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $result = "";
        foreach ($parts as $part) {
            if (!empty($part) && !ctype_space($part)) {
                if ($part === '-') {
                    $result .= '-';
                } else {
                    $result .= $part[0] . '.';  // 移除 strtoupper()
                }
            } elseif (ctype_space($part)) {
                $result .= '-';  // 將空格替換成連字符
            }
        }
        
        return $result;
    }

    /**
     * 將中間名轉換為縮寫（根據 Python 邏輯）
     */
    public function toMiddlenameAbbr(?string $name): string
    {
        if (empty($name)) {
            return "";
        }
        
        // 按空格分割名字並取首字母
        $parts = explode(' ', trim($name));
        $abbr = "";
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $abbr .= $part[0] . '.';  // 移除 strtoupper()
            }
        }
        
        return $abbr;
    }

    /**
     * 將中間名轉換為帶空格的縮寫（專用於 type specimens）
     */
    public function toMiddlenameAbbrWithSpaces(?string $name): string
    {
        if (empty($name)) {
            return "";
        }
        
        // 按空格分割名字並取首字母
        $parts = explode(' ', trim($name));
        $abbr = "";
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $abbr .= $part[0] . '. ';  // 注意這裡加了空格
            }
        }
        
        return rtrim($abbr);  // 移除最後的空格
    }

    /**
     * 生成 abbreviation_name（根據 Python 邏輯）
     */
    private function generateAbbreviationName($person): string
    {
        $firstName = $person['first_name'] ?? '';
        $middleName = $person['middle_name'] ?? '';
        $lastName = $person['last_name'] ?? '';

        // 使用新的縮寫邏輯
        $firstNameAbbr = $this->toFirstnameAbbr($firstName);
        $middleNameAbbr = $this->toMiddlenameAbbr($middleName);

        // 組合縮寫：FirstName MiddleName LastName 的格式
        $abbreviationParts = [];
        if ($firstNameAbbr) {
            $abbreviationParts[] = $firstNameAbbr;
        }
        if ($middleNameAbbr) {
            $abbreviationParts[] = $middleNameAbbr;
        }
        if (!empty($lastName)) {
            $abbreviationParts[] = $lastName;
        }
        
        return implode(' ', $abbreviationParts);
    }

    /**
     * 全名
     */
    public function fullName($person)
    {
        $firstName = $person['first_name'] ?? '';
        $middleName = $person['middle_name'] ?? '';
        $lastName = $person['last_name'] ?? '';

        $fullFirstName = trim($firstName . ' ' . $middleName);
        
        return implode(', ', array_filter([$lastName, $fullFirstName]));
    }

    /**
     * 全名的縮寫
     */
    public function fullNameAbbreviation($person, $isOpposite = false)
    {
        $firstName = $person['first_name'] ?? '';
        $middleName = $person['middle_name'] ?? '';
        $lastName = $person['last_name'] ?? '';

        // 使用新的縮寫邏輯
        $firstNameAbbr = $this->toFirstnameAbbr($firstName);
        $middleNameAbbr = $this->toMiddlenameAbbr($middleName);

        if ($isOpposite) {
            $fullFirstName = trim($firstNameAbbr . ' ' . $middleNameAbbr);
            return trim($fullFirstName . ' ' . $lastName);
        }

        $fullFirstName = trim($firstNameAbbr . ' ' . $middleNameAbbr);
        return implode(', ', array_filter([$lastName, $fullFirstName]));
    }

    /**
     * 連續「全名縮寫」
     */
    public function comboFull($persons)
    {
        if (empty($persons)) {
            return '';
        }
        
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        return implode(', ', array_map([$this, 'fullNameAbbreviation'], $personsArray));
    }

    /**
     * 連續「姓氏」縮寫
     */
    public function comboLast($persons)
    {
        if (empty($persons)) {
            return '';
        }
        
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        
        $names = array_map(function($person) {
            return $person['last_name'] ?? '';
        }, $personsArray);

        $names = array_filter($names);

        if (count($names) >= 3) {
            return $names[0] . ' et al.';
        }

        return implode(' & ', $names);
    }

    /**
     * 連續完整姓氏
     */
    public function comboFullLast($persons)
    {
        if (empty($persons)) {
            return '';
        }
        
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        
        $names = array_map(function($person) {
            return $person['last_name'] ?? '';
        }, $personsArray);

        $names = array_filter($names);
        $result = implode(', ', $names);
        
        // 將最後一個逗號替換為 &
        return preg_replace('/(,\s)(?!.*,\s)/', ' & ', $result);
    }

    /**
     * 連續「縮寫名」
     */
    public function comboAbbr($persons)
    {
        if (empty($persons)) {
            return '';
        }
        
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        
        $names = array_map(function($person) {
            // 如果已有 abbreviation_name 就使用，否則生成
            return $person['abbreviation_name'] ?? $this->generateAbbreviationName($person);
        }, $personsArray);

        $names = array_filter($names);

        if (count($names) >= 3) {
            return $names[0] . ' et al.';
        }

        return implode(' & ', $names);
    }

    /**
     * 連續完整縮寫名
     */
    public function comboFullAbbr($persons)
    {
        if (empty($persons)) {
            return '';
        }
        
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        
        $names = array_map(function($person) {
            // 如果已有 abbreviation_name 就使用，否則生成
            return $person['abbreviation_name'] ?? $this->generateAbbreviationName($person);
        }, $personsArray);

        $names = array_filter($names);
        $result = implode(', ', $names);
        
        return preg_replace('/(,\s)(?!.*,\s)/', ' & ', $result);
    }

    /**
     * Factory 方法
     */
    public function factory($type)
    {
        switch ($type) {
            case 'animal':
                return 'comboFullLast';
            case 'plant':
                return 'comboFullAbbr';
            case 'bacteria':
                return 'comboLast';
            default:
                return 'comboFull';
        }
    }

    /**
     * 動物命名者邏輯
     */
    public function animalAuthorNames($persons, $expersons = [], $originName = null, $publishYear = '', $taxonName = null)
    {
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        $expersonsArray = $this->ensureArray($expersons);

        // 若有「原始組合名」以原始組合名加上括號為「命名者」
        if ($originName) {
            // 同屬內變動
            $isSameGenus = $this->isSameGenus($taxonName, $originName);

            if ($isSameGenus) {
                return $this->animalAuthorNames($originName['authors'] ?? [], $originName['ex_authors'] ?? [], null, $originName['publish_year'] ?? '');
            }

            return '(' . $this->animalAuthorNames($originName['authors'] ?? [], $originName['ex_authors'] ?? [], null, $originName['publish_year'] ?? '') . ')';
        }

        // 動物的命名者加上「年份」
        $method = $this->factory('animal');
        $authorParts = array_filter([
            $this->$method($expersonsArray),
            $this->$method($personsArray)
        ]);

        return implode(', ', array_filter([
            implode(' ex ', $authorParts),
            $publishYear
        ]));
    }

    /**
     * 植物命名者邏輯
     */
    public function plantAuthorNames($persons, $expersons = [], $originName = null)
    {
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        $expersonsArray = $this->ensureArray($expersons);
        
        $method = $this->factory('plant');
        
        $parts = [];
        
        // 若有「原始組合名」以原始組合名加上括號為「命名者」
        if ($originName) {
            $parts[] = '(' . $this->plantAuthorNames($originName['authors'] ?? [], $originName['ex_authors'] ?? [], null) . ')';
        }

        $authorParts = array_filter([
            $this->$method($expersonsArray),
            $this->$method($personsArray)
        ]);

        if (!empty($authorParts)) {
            $parts[] = implode(' ex ', $authorParts);
        }

        return implode(' ', $parts);
    }

    /**
     * 細菌命名者邏輯
     */
    public function bacteriaAuthorNames($persons, $expersons = [], $originName = null, $publishYear = '', $taxonName = null)
    {
        // 確保是 array 格式
        $personsArray = $this->ensureArray($persons);
        $expersonsArray = $this->ensureArray($expersons);
        
        $parts = [];

        // 若有「原始組合名」以原始組合名加上括號為「命名者」
        if ($originName) {
            $isSameGenus = $this->isSameGenus($taxonName, $originName);

            if ($isSameGenus) {
                return $this->bacteriaAuthorNames(
                    $originName['authors'] ?? [],
                    $originName['ex_authors'] ?? [],
                    null,
                    $originName['publish_year'] ?? ''
                );
            }

            $parts[] = '(' . $this->bacteriaAuthorNames(
                $originName['authors'] ?? [],
                [],
                null,
                $originName['publish_year'] ?? ''
            ) . ')';
        }

        $method = $this->factory('bacteria');
        
        $exPersonsResult = '';
        if (!empty($expersonsArray)) {
            $exResult = array_filter([
                $this->$method($expersonsArray),
                $taxonName['properties']['initial_year'] ?? ''
            ]);
            if (!empty($exResult)) {
                $exPersonsResult = '(ex ' . implode(' ', $exResult) . ')';
            }
        }

        $authorParts = array_filter([
            $exPersonsResult,
            $this->$method($personsArray)
        ]);

        if (!empty($authorParts)) {
            $parts[] = implode(' ', $authorParts);
        }

        if ($publishYear) {
            $parts[] = $publishYear;
        }

        if (($taxonName['properties']['is_approved_list'] ?? false)) {
            $parts[] = '(Approved Lists 1980)';
        }

        return implode(' ', $parts);
    }

    /**
     * 作者名稱字符串工廠
     */
    protected function isSameGenus($taxonName, $originName)
    {
        // 檢查多種可能的同屬情況
        $conditions = [
            // 條件 1: taxonName->species->properties->latinGenus === originName->properties->latinGenus
            !empty($taxonName['properties']['latin_genus']) &&
            !empty($originName['properties']['latin_genus']) &&
            $taxonName['properties']['latin_genus'] === $originName['properties']['latin_genus'],

            // 條件 2: taxonName->species && originName->species && 兩者的 latinGenus 相同
            !empty($taxonName['properties']['latin_genus']) &&
            !empty($originName['properties']['latin_genus']) &&
            $taxonName['properties']['latin_genus'] === $originName['properties']['latin_genus'],

            // 條件 3: taxonName->properties->latinGenus === originName->species->properties->latinGenus
            !empty($taxonName['properties']['latin_genus']) &&
            !empty($originName['properties']['latin_genus']) &&
            $taxonName['properties']['latin_genus'] === $originName['properties']['latin_genus']
        ];

        return in_array(true, $conditions);
    }

    public function authorNameStringFactory($type, $authors, $exAuthors, $originalTaxonName, $taxonName, $publishYear = '')
    {
        switch ($type) {
            case 'animal':
                return $this->animalAuthorNames($authors, $exAuthors, $originalTaxonName, $publishYear, $taxonName);
            case 'plant':
                return $this->plantAuthorNames($authors, $exAuthors, $originalTaxonName);
            case 'bacteria':
                return $this->bacteriaAuthorNames($authors, $exAuthors, $originalTaxonName, $publishYear, $taxonName);
            case 'virus':
                return '';
            default:
                return '';
        }
    }
}