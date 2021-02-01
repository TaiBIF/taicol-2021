<?php

use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('indicators')->insert([
            [
                'type' => 'status',
                'status' => 'not-accepted',
                'title' => 'nomen oblitum',
                'definition' => json_encode([
                    'zh-tw' => '被遺忘名'
                ]),
                'abbreviation' => 'nom. obl.',
                'format_pattern' => '',
            ],
            [
                'type' => 'status',
                'status' => 'accpeted',
                'title' => 'nomen protectum',
                'definition' => json_encode([
                    'zh-tw' => '受保護名'
                ]),
                'abbreviation' => 'nom. prot.',
                'format_pattern' => '',
            ],
            [
                'type' => 'status',
                'status' => 'accpeted',
                'title' => 'nomen conservandum',
                'definition' => json_encode([
                    'zh-tw' => '保留名'
                ]),
                'abbreviation' => 'nom. cons.',
                'format_pattern' => '',
            ],
            [
                'type' => 'status',
                'status' => 'not-accepted',
                'title' => 'nomen regiciciendum',
                'definition' => json_encode([
                    'zh-tw' => '廢棄名'
                ]),
                'abbreviation' => 'nom. rej.',
                'format_pattern' => '',
            ],
            [
                'type' => 'status',
                'status' => 'not-accepted',
                'title' => 'nomen illegitimum',
                'definition' => json_encode([
                    'zh-tw' => '不合法名。經正當發表、但不合法規之名稱。'
                ]),
                'abbreviation' => 'nom. illeg.',
                'format_pattern' => '',
            ],
            [
                'type' => 'status',
                'status' => 'not-accepted',
                'title' => 'nomen invalid',
                'definition' => json_encode([
                    'zh-tw' => '不正當名。非經正當發表之名稱。'
                ]),
                'abbreviation' => 'nom. inval.',
                'format_pattern' => '',
            ],
            [
                'type' => 'status',
                'status' => 'not-accepted',
                'title' => 'unavailable',
                'definition' => json_encode([
                    'zh-tw' => '不適用。非經正當發表之名稱。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'classis nova',
                'definition' => json_encode([
                    'zh-tw' => '新綱'
                ]),
                'abbreviation' => 'class. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'combinatio nova',
                'definition' => json_encode([
                    'zh-tw' => '新組合'
                ]),
                'abbreviation' => 'comb. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'combination revived',
                'definition' => json_encode([
                    'zh-tw' => '組合恢復'
                ]),
                'abbreviation' => 'comb. rev.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'familia nova',
                'definition' => json_encode([
                    'zh-tw' => '新科'
                ]),
                'abbreviation' => 'fam. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'genus novum',
                'definition' => json_encode([
                    'zh-tw' => '新屬'
                ]),
                'abbreviation' => 'gen. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'nomem novum',
                'definition' => json_encode([
                    'zh-tw' => '新名'
                ]),
                'abbreviation' => 'nom. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'nomen revictum',
                'definition' => json_encode([
                    'zh-tw' => '恢復名'
                ]),
                'abbreviation' => 'nom. rev.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'ordo novus',
                'definition' => json_encode([
                    'zh-tw' => '新目'
                ]),
                'abbreviation' => 'ord. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'species novum',
                'definition' => json_encode([
                    'zh-tw' => '新種'
                ]),
                'abbreviation' => 'sp. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'subspecies novum',
                'definition' => json_encode([
                    'zh-tw' => '新亞種'
                ]),
                'abbreviation' => 'ssp. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'status novus',
                'definition' => json_encode([
                    'zh-tw' => '新地位'
                ]),
                'abbreviation' => 'stat. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'accpeted',
                'title' => 'status revived',
                'definition' => json_encode([
                    'zh-tw' => '地位恢復'
                ]),
                'abbreviation' => 'stat. rev.',
                'format_pattern' => '',
            ],
            [
                'type' => 'treatment',
                'status' => 'not-accepted',
                'title' => 'synonymum novum',
                'definition' => json_encode([
                    'zh-tw' => '新同物異名'
                ]),
                'abbreviation' => 'syn. nov.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'ex errore',
                'definition' => json_encode([
                    'zh-tw' => '錯誤發表'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen abortivum',
                'definition' => json_encode([
                    'zh-tw' => 'illegitimate name的前用法'
                ]),
                'abbreviation' => 'nom. abort.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => '-',
                'title' => 'nomen alternativum',
                'definition' => json_encode([
                    'zh-tw' => '替換名。一作者同時對同一分類群依據相同模式發表可做替換的兩個或多個名稱，1953年以前發表則皆為合法，之後則皆不合法。'
                ]),
                'abbreviation' => 'nom. alt.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen ambiguum',
                'definition' => json_encode([
                    'zh-tw' => '模糊不清之名。無法認定為何分類群的名字。'
                ]),
                'abbreviation' => 'nom. ambig.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'accpeted',
                'title' => 'nomen approbatum',
                'definition' => json_encode([
                    'zh-tw' => '批准名。列入 Approved List of Bacterial Names的學名。'
                ]),
                'abbreviation' => 'nom. approb.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen confusum',
                'definition' => json_encode([
                    'zh-tw' => '名稱混淆，命名是基於混菌的培養。'
                ]),
                'abbreviation' => 'nom. confus.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen dubium',
                'definition' => json_encode([
                    'zh-tw' => '疑問名。非正式的用法。'
                ]),
                'abbreviation' => 'nom. dub.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen erratum',
                'definition' => json_encode([
                    'zh-tw' => '命名錯誤。A name given in error.'
                ]),
                'abbreviation' => 'nom. err.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen manuscriptum',
                'definition' => json_encode([
                    'zh-tw' => '僅出現在手稿的名稱。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen monstrositatum',
                'definition' => json_encode([
                    'zh-tw' => '基於畸形個體命名的名稱。'
                ]),
                'abbreviation' => 'nom. monstr.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen nudum',
                'definition' => json_encode([
                    'zh-tw' => '裸名。名稱被發表時未與充分的文字記述一起發表。'
                ]),
                'abbreviation' => 'nom. nud.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen periculosum',
                'definition' => json_encode([
                    'zh-tw' => '名稱可能導致人身或經濟危害。'
                ]),
                'abbreviation' => 'nom. peric.',
 'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen perplexum',
                'definition' => json_encode([
                    'zh-tw' => '名稱相似而令人困惑。'
                ]),
                'abbreviation' => 'nom. perp.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'accpeted',
                'title' => 'nomen sanctionatum',
                'definition' => json_encode([
                    'zh-tw' => '公認名。真菌類所使用，用以標註對先出同名或競爭而取得保留的學名。'
                ]),
                'abbreviation' => 'nom. sanct.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen superfluum',
                'definition' => json_encode([
                    'zh-tw' => '多餘名。較晚針對同一標本做的命名。'
                ]),
                'abbreviation' => 'nom. superfl.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => 'not-accepted',
                'title' => 'nomen suppressum',
                'definition' => json_encode([
                    'zh-tw' => '抑制名。名稱被抑制而不能使用。'
                ]),
                'abbreviation' => 'nom. supp.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => '-',
                'title' => 'pro hybrida',
                'definition' => json_encode([
                    'zh-tw' => '名稱發表時以雜交種發表。'
                ]),
                'abbreviation' => 'pro. hybr.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => '-',
                'title' => 'pro specie',
                'definition' => json_encode([
                    'zh-tw' => '名稱發表時以種發表。'
                ]),
                'abbreviation' => 'pro. sp.',
                'format_pattern' => '',
            ],
            [
                'type' => 'description',
                'status' => '-',
                'title' => 'pro synonymo',
                'definition' => json_encode([
                    'zh-tw' => '名稱發表時以同物異名發表。As synonym.'
                ]),
                'abbreviation' => 'pro. syn.',
                'format_pattern' => '',
            ],
            [
                'type' => 'orthographic',
                'status' => 'accpeted',
                'title' => 'orthographia conservanda',
                'definition' => json_encode([
                    'zh-tw' => '保留拼法'
                ]),
                'abbreviation' => 'orth. cons.',
                'format_pattern' => '',
            ],
            [
                'type' => 'orthographic',
                'status' => 'not-accepted',
                'title' => 'orthographical variant',
                'definition' => json_encode([
                    'zh-tw' => '拼法變異'
                ]),
                'abbreviation' => 'orth. var.',
                'format_pattern' => '',
            ],
            [
                'type' => 'orthographic',
                'status' => 'accpeted',
                'title' => 'corrigendum',
                'definition' => json_encode([
                    'zh-tw' => '拼法修正'
                ]),
                'abbreviation' => 'corrig.',
                'format_pattern' => '',
            ],
            [
                'type' => 'orthographic',
                'status' => 'not-accepted',
                'title' => 'sic',
                'definition' => json_encode([
                    'zh-tw' => '原始拼法'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => '？',
                'definition' => json_encode([
                    'zh-tw' => '未確定分類群'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'affinis',
                'definition' => json_encode([
                    'zh-tw' => '近似'
                ]),
                'abbreviation' => 'aff.',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'Candidatus',
                'definition' => json_encode([
                    'zh-tw' => '暫定種。尚未培養的微生物的臨時分類狀態。'
                ]),
                'abbreviation' => 'Ca.',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'confer',
                'definition' => json_encode([
                    'zh-tw' => '參考、相較'
                ]),
                'abbreviation' => 'cf.',
'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'incertae sedis',
                'definition' => json_encode([
                    'zh-tw' => '地位不明'
                ]),
                'abbreviation' => 'inc. sed.',
                    'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'indeterminata',
                'definition' => json_encode([
                    'zh-tw' => '未決。樣本或資訊不足以判斷其分類地位。'
                ]),
                'abbreviation' => 'indet.',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'sp.',
                'definition' => json_encode([
                    'zh-tw' => '不能確定種名的未定種。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'species inquirenda',
                'definition' => json_encode([
                    'zh-tw' => '身份模糊，待進一步研究的物種。'
                ]),
                'abbreviation' => 'sp. inq.',
                    'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'species proxima',
                'definition' => json_encode([
                    'zh-tw' => '近似種'
                ]),
                'abbreviation' => 'sp. prox.',
                'format_pattern' => '',
            ],
            [
                'type' => 'undetermined',
                'status' => 'unresolved',
                'title' => 'stetit',
                'definition' => json_encode([
                    'zh-tw' => '維持狀態，無進一步發展。'
                ]),
                'abbreviation' => 'stet.',
                    'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'auctorum non',
                'definition' => json_encode([
                    'zh-tw' => '非指auct. non後方人名所命名的分類群。通常用於錯誤鑑定的引用。'
                ]),
                'abbreviation' => 'auct. non',
                    'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'nec',
                'definition' => json_encode([
                    'zh-tw' => '非指nec後方人名所命名的分類群。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'non',
                'definition' => json_encode([
                    'zh-tw' => '非指non後方人名所命名的分類群。通常用於同名異物情形。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'not of',
                'definition' => json_encode([
                    'zh-tw' => '非指not of後方人名所命名的分類群。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'sensu',
                'definition' => json_encode([
                    'zh-tw' => '就sensu後方人名的見解而言。與non的使用相反。'
                ]),
                'abbreviation' => '',
                'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'secudum',
                'definition' => json_encode([
                    'zh-tw' => '就sec.後方人名的見解而言。與non的使用相反。'
                ]),
                'abbreviation' => 'sec.',
                'format_pattern' => '',
            ],
            [
                'type' => 'misapply',
                'status' => 'misapplied',
                'title' => 'sensu auctorum',
                'definition' => json_encode([
                    'zh-tw' => '就sensu auct.後方人名的見解而言。與non的使用相反。'
                ]),
                'abbreviation' => 'sensu auct.',
                'format_pattern' => '',
            ],
        ]);
    }
}
