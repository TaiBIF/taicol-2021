<?php
/**
 * 軟刪除 E2E 測試資料（命名規則見 tests/e2e/helpers/data.ts）。
 *
 * 從本機執行（php 由 STDIN 讀這支檔案，在 app 容器的專案根目錄執行）：
 *   ssh tc-stag "docker exec -i taicol-nametool-stag-app php -- --dry-run" < tests/e2e/cleanup.php
 *   ssh tc-stag "docker exec -i taicol-nametool-stag-app php" < tests/e2e/cleanup.php
 * 升級測試站把容器換成 taicol-nametool-upg-app（兩站共用 DB，擇一執行即可）。
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Book;
use App\FavoriteFolder;
use App\FavoriteItem;
use App\FavoriteMineItem;
use App\MyNamespace;
use App\MyNamespaceUsage;
use App\Person;
use App\Reference;
use App\ReferenceUsage;
use App\TaxonName;
use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv ?? [], true);

$personIds = Person::where('last_name', 'like', 'E2E-%')->pluck('id');
$referenceIds = DB::table('person_reference')->whereIn('person_id', $personIds)->pluck('reference_id')->unique()->values();
$taxonNameIds = TaxonName::where('name', 'like', 'E2etest %')->pluck('id');

$usages = ReferenceUsage::where(function ($q) use ($referenceIds, $taxonNameIds) {
    $q->whereIn('reference_id', $referenceIds)->orWhereIn('taxon_name_id', $taxonNameIds);
});

$favoriteFilter = function (string $model) use ($referenceIds, $taxonNameIds) {
    return function ($q) use ($model, $referenceIds, $taxonNameIds) {
        $q->where(function ($q) use ($model, $referenceIds) {
            $q->where('collectable_type', $model::TYPE_REFERENCE)->whereIn('collectable_id', $referenceIds);
        })->orWhere(function ($q) use ($model, $taxonNameIds) {
            $q->where('collectable_type', $model::TYPE_TAXON_NAME)->whereIn('collectable_id', $taxonNameIds);
        });
    };
};
$folderIds = FavoriteFolder::where('title', 'like', 'E2E-%')->pluck('id');
$namespaceIds = MyNamespace::where('title', 'like', 'E2E-%')->pluck('id');
$namespaceUsages = MyNamespaceUsage::whereIn('namespace_id', $namespaceIds)->orWhereIn('taxon_name_id', $taxonNameIds);

$summary = [
    'persons' => $personIds->count(),
    'references' => $referenceIds->count(),
    'taxon_names' => $taxonNameIds->count(),
    'reference_usages' => (clone $usages)->count(),
    'favorite_mine_items' => FavoriteMineItem::where($favoriteFilter(FavoriteMineItem::class))->count(),
    'favorite_items' => FavoriteItem::where($favoriteFilter(FavoriteItem::class))->count(),
    'favorite_folders' => $folderIds->count(),
    'namespaces' => $namespaceIds->count(),
    'namespace_usages' => (clone $namespaceUsages)->count(),
    'books' => Book::where('title', 'like', 'E2E-%')->count(),
];

if (!$dryRun) {
    DB::transaction(function () use ($usages, $namespaceUsages, $namespaceIds, $favoriteFilter, $folderIds, $referenceIds, $taxonNameIds, $personIds) {
        $usages->delete();
        // namespace usages 沒有軟刪除
        $namespaceUsages->delete();
        MyNamespace::whereIn('id', $namespaceIds)->delete();
        FavoriteMineItem::where($favoriteFilter(FavoriteMineItem::class))->delete();
        FavoriteItem::where($favoriteFilter(FavoriteItem::class))->delete();
        // 收藏夾沒有軟刪除：先硬刪其中的項目（含已軟刪除的），再刪收藏夾
        FavoriteItem::withTrashed()->whereIn('favorite_folder_id', $folderIds)->forceDelete();
        FavoriteFolder::whereIn('id', $folderIds)->delete();
        Reference::whereIn('id', $referenceIds)->delete();
        TaxonName::whereIn('id', $taxonNameIds)->delete();
        Person::whereIn('id', $personIds)->delete();
        // books 沒有軟刪除且被 references 外鍵參照，只取消發布
        Book::where('title', 'like', 'E2E-%')->update(['is_publish' => 0]);
    });
}

echo ($dryRun ? '[dry-run] ' : '[deleted] ') . json_encode($summary) . PHP_EOL;
