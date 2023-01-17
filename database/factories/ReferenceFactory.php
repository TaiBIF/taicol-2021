<?php

namespace Database\Factories;

use App\Reference;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reference::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'cover_path' => '',
            'type' => Reference::TYPE_JOURNAL,
            'title' => $this->faker->realText(),
            'subtitle' => 'Candolle & Candolle, 1879, Monogr. Phan., 2',
            'publish_year' => $this->faker->year,
            'language' => $this->faker->randomElement(['en-us', 'zh-tw', 'jp-jp', 'zh-cn', 'de-de', 'fr-fr', 'lat', 'others']), //語言
            'properties' => [
                'article_title' => $this->faker->realText(60),
                'book_title' => 'ZooKeys',
                'book_title_abbreviation' => 'Monogr. Phan.',
                'volume' => $this->faker->randomDigit(), //卷號
                'issue' => '', // 期號
                'pages_range' => '1–681', //頁碼範圍
                'article_number' => '', // 電子文章編號
                'doi' => '10.3897/zookeys.102.948', // DOI
                'url' => 'http://www.botanicus.org/item/31753002501424', // 連結
                'copyright' => 'copyright@' . $this->faker->randomLetter(), // 版權
            ],
            'note' => '',
            'is_publish' => 1,
            'book_id' => null,
        ];
    }

    public function type(int $type)
    {
        switch ($type) {
            case Reference::TYPE_JOURNAL:
                return $this->state(function () use ($type) {
                    return [
                        'type' => $type,
                        'book_id' => 2,
                        'properties' => $this->generateProperty($type),
                    ];
                });
            case Reference::TYPE_BOOK_ARTICLE:
                return $this->state(function () use ($type) {
                    return [
                        'type' => $type,
                        'book_id' => 1,
                        'properties' => $this->generateProperty($type),
                    ];
                });
            case Reference::TYPE_BOOK:
                return $this->state(function () use ($type) {
                    return [
                        'type' => $type,
                        'book_id' => 1,
                        'properties' => $this->generateProperty($type),
                    ];
                });
        }
        return $this;
    }

    public function generateProperty(int $type): array
    {
        switch ($type) {
            case Reference::TYPE_JOURNAL:
                return [
                    'article_title' => $this->faker->realText(60),
                    'book_title' => 'ZooKeys',
                    'book_title_abbreviation' => 'Monogr. Phan.',
                    'volume' => $this->faker->randomDigit(), //卷號
                    'issue' => '', // 期號
                    'pages_range' => '1–681', //頁碼範圍
                    'article_number' => '', // 電子文章編號
                    'doi' => '10.3897/zookeys.102.948', // DOI
                    'url' => 'http://www.botanicus.org/item/31753002501424', // 連結
                    'copyright' => 'copyright@' . $this->faker->randomLetter(), // 版權
                ];
            case Reference::TYPE_BOOK_ARTICLE:
                return [
                    'article_title' => 'Test Book chapter',
                    'book_title' => 'Monographiae phanerogamarum',
                    'book_title_abbreviation' => 'Monogr. Phan.',
                    'edition' => '', // 版本
                    'volume' => $this->faker->randomDigit(), //部冊號
                    'pages_range' => '1–681', //頁碼範圍
                    'chapter' => '', // 章節
                    'url' => 'http://www.botanicus.org/item/31753002501424', // 連結
                    'copyright' => 'copyright@' . $this->faker->randomLetter(), // 版權
                ];

            case Reference::TYPE_BOOK:
                return [
                    'book_title' => 'Monographiae phanerogamarum',
                    'book_title_abbreviation' => 'Monogr. Phan.',
                    'edition' => '', // 版本
                    'volume' => $this->faker->randomDigit(), //部冊號
                    'pages_range' => '1–681', //頁碼範圍
                    'chapter' => '', // 章節
                    'url' => 'http://www.botanicus.org/item/31753002501424', // 連結
                    'copyright' => 'copyright@' . $this->faker->randomLetter(), // 版權
                ];
            default:
                throw new \Exception();
        }
    }
}
