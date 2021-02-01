<?php

namespace App\Console\Commands;

use App\Book;
use App\Person;
use App\Reference;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportReference extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:reference';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import reference from xlsx';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $spreadsheet = IOFactory::load(storage_path('reference.xlsx'));
        $sheets = $spreadsheet->getAllSheets();

        /**
         * columns
         * A. 文獻類型(期刊文章、書籍文章、書籍)
         * B. 作者(|分隔)*比對全名*|分隔
         * C. 發表年份
         * D. 文章標題
         * E. 書名/期刊
         * F. 期刊/書名縮寫
         * G. 卷號
         * H. 期號
         * I. 部冊號
         * J. 版本
         * K. 頁碼範圍
         * L. 章節
         * M. 電子文章編號
         * N. DOI
         * O. 連結
         * P. 語言
         * Q. 版權
         * R. 備註
         */

        foreach ($sheets as $sheet) {
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $type = $sheet->getCell('A' . $row)->getValue();
                if (!$type) {
                    break;
                }
                $typeMap = [
                    '期刊文章' => Reference::TYPE_JOURNAL,
                    '書籍文章(章節)' => Reference::TYPE_BOOK_ARTICLE,
                    '書籍' => Reference::TYPE_BOOK,
                ];

                $authorNamesString = $sheet->getCell('B' . $row)->getValue();
                $authorNames = explode('|', $authorNamesString);

                $authors = Person::whereIn('original_full_name', $authorNames)->get();

                $publishYear = $sheet->getCell('C' . $row)->getValue();
                $articleTitle = $sheet->getCell('D' . $row)->getValue();
                $bookTitle = $sheet->getCell('E' . $row)->getValue();
                $bookAbbreviation = $sheet->getCell('F' . $row)->getValue();
                $volume = $sheet->getCell('G' . $row)->getValue();
                $issue = $sheet->getCell('H' . $row)->getValue();
                $volumeBook = $sheet->getCell('I' . $row)->getValue();
                $edition = $sheet->getCell('J' . $row)->getValue();
                $pageRange = $sheet->getCell('K' . $row)->getValue();
                $chapter = $sheet->getCell('L' . $row)->getValue();
                $articleNumber = $sheet->getCell('M' . $row)->getValue();
                $doi = $sheet->getCell('N' . $row)->getValue();
                $url = $sheet->getCell('O' . $row)->getValue();
                $language = $sheet->getCell('P' . $row)->getValue();
                $languageMapping = [
                    '英文' => 'en-us',
                    '日文' => 'jp-jp',
                    '其他' => 'others',
                ];
                $copyright = $sheet->getCell('Q' . $row)->getValue();
                $note = $sheet->getCell('R' . $row)->getValue();

                $reference = new Reference();
                $reference->type = (int) $typeMap[$type];
                $reference->publish_year = $publishYear;
                $reference->properties = [
                    'article_title' => $articleTitle ?? '',
                    'book_title' => $bookTitle ?? '',
                    'book_title_abbreviation' => $bookAbbreviation ?? '',
                    'volume' => $volumeBook ?? $volume,
                    'issue' => $issue ?? '',
                    'edition' => $edition ?? '',
                    'pages_range' => $pageRange ?? '',
                    'doi' => $doi ?? '',
                    'article_number' => $articleNumber ?? '',
                    'chapter' => $chapter ?? '',
                    'copyright' => $copyright ?? '',
                    'url' => $url,
                ];

                $reference->language = $languageMapping[$language] ?? '';

                $reference->note = $note ?? '';
                $reference->cover_path = '';
                $reference->is_publish = true;
                $reference->save();

                if ($bookTitle) {
                    $book = Book::query()->where('title', $bookTitle)->first();
                    if (!$book) {
                        $book = new Book();
                        $book->title = $bookTitle;
                        if ($bookAbbreviation) {
                            $book->title_abbreviation = $bookAbbreviation;
                        }
                        $book->save();
                    }

                    $reference->book()->associate($book);
                    $reference->save();
                }

                $reference->authors()->sync($authors->pluck('id'));

                $r = json_encode(Reference::with(['authors'])->find($reference->id)->toJson());
                $title = [];
                exec("node public/js/computeReference.js --r={$r} 2>&1", $title);
                $reference->title = $title[0] ?? '';
                $reference->subtitle = $title[1] ?? '';
                $reference->save();
            }
        }
    }
}
