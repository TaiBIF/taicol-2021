<?php

namespace App\Http\Controllers;

use App\Book;
use App\FavoriteMineItem;
use App\Http\Entities\ReferenceOtherPropertiesFactory;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Http\Resources\UsageCollection;
use App\Reference;
use App\ReferenceUsage;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReferenceController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $referenceQuery = Reference::with(['authors']);
        if ($keyword) {
            $referenceQuery->whereRaw('LOWER(`title`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
                ->orWhereRaw('LOWER(`subtitle`) LIKE ? ', ['%' . trim(strtoLower($keyword)) . '%'])
                ->orWhereHas('authors', function ($query) use ($keyword) {
                    $query->where('last_name', 'like', $keyword)
                        ->orWhere('first_name', 'like', $keyword)
                        ->orWhere('middle_name', 'like', $keyword);
                });
        }

        if ($request->get('sortby') === 'type') {
            $referenceQuery->orderBy('type', $request->get('direction'));
        }

        if ($request->get('sortby') === 'publish_year') {
            $referenceQuery->orderBy('publish_year', $request->get('direction'));
        }

        if ($request->get('sortby') === 'article_title') {
            $referenceQuery->orderBy('properties->article_title', $request->get('direction'));
        }

        $references = $referenceQuery
            ->where('type', '!=', Reference::TYPE_BACKBONE)
            ->paginate(20);

        return response()->json([
            'total' => $references->total(),
            'data' => ReferenceCollection::collection($references->items()),
            'per_page' => $references->perPage(),
            'current_page' => $references->currentPage(),
            'last_page' => $references->lastPage(),
        ]);
    }

    public function show($id)
    {
        $reference = Reference::with(['authors', 'book'])->where('type', '!=', Reference::TYPE_BACKBONE)
            ->where('id', $id)
            ->first();
        if (!$reference) {
            return response([
                'message' => 'Not Found.'
            ], 404);
        }

        return ReferenceCollection::collection([$reference])[0];
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|Integer|not_in:0',
            'authors' => 'required|array|min:1|exists:persons,id',
            'publish_year' => 'required|regex:/^[1-2]{1}[0-9]{3}$/',
            'properties.volume' => 'required_if:type,1|nullable|regex:/^[0-9a-zA-Z]+/u',
            'properties.book_title' => 'required_if:type,1|required_if:type,2|required_if:type,3',
            'properties.pages_range' => 'nullable|regex:/^[0-9\-–,]+$/u',
            'properties.edition' => 'nullable',
            'properties.chapter' => 'nullable|regex:/^[0-9a-zA-Z]+/u',
            'properties.url' => 'nullable|url',
        ], [
            'min' => '必填',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'integer' => '須為數字',
            'properties.volume.regex' => '只允許「數字」、「英文」',
            'properties.chapter.regex' => '只允許「數字」、「英文」',
            'publish_year.regex' => '須為年份4位數',
            'properties.pages_range.regex' => '只允許「數字」、「–」、「,」'
        ]);

        $reference = Reference::with(['book', 'authors'])->find($id);

        if (!$reference) {
            return response([], 404);
        }

        $authors = $request->get('authors');
        $isPublish = $request->get('is_publish');
        $editors = $request->get('editors', []);
        $type = $request->get('type');
        $properties = $request->get('properties');

        DB::beginTransaction();
        try {
            $reference->type = $type;
            $reference->publish_year = $request->get('publish_year');
            $reference->language = $request->get('language');

            $reference->properties = (new ReferenceOtherPropertiesFactory())
                ->createPropertiesFromType($type)
                ->setProperties($properties)
                ->toArray();

            $reference->note = $request->get('note') ?? '';
            $reference->is_publish = (bool) $isPublish;
            $reference->save();

            $reference->saveAuthors($authors);

            if ($type === Reference::TYPE_JOURNAL || $type === Reference::TYPE_BOOK_ARTICLE || $type === Reference::TYPE_BOOK) {
                $reference->saveBook(
                    $properties['book_title'],
                    $properties['book_title_abbreviation'] ?? '',
                    $editors
                );
            }

            // upload image
            $file = $request->get('image');
            if ($file) {
                $extension = explode('/', mime_content_type($file))[1];
                $path = sprintf(
                    'references/%d-%d.%s',
                    $reference->id,
                    time(),
                    $extension
                );

                Storage::disk('images')->put($path, file_get_contents($file));
                $reference->cover_path = $path;
                $reference->save();
            } else if (!$file && !$request->get('cover_path')) {
                $reference->cover_path = '';
                $reference->save();
            }

            // 儲存 computed data
            $reference->save();
            $r = json_encode($reference->toJson());
            exec("node ../public/js/computeReference.js --r={$r} 2>&1", $title);
            $reference->title = $title[0] ?? '';
            $reference->subtitle = $title[1] ?? '';
            $reference->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            Log::error("[reference update]: {$e->getMessage()}");
            return response([
                'message' => $e->getMessage(),
            ])->setStatusCode(500);
        }
        return response(ReferenceCollection::collection([$reference])[0]);
    }

    public function uploadImage(Request $request, $id)
    {
        $reference = Reference::find($id);

        if (!$reference) {
            return response()->setStatusCode(404);
        }

        $file = $request->file('file');
        $path = sprintf(
            'references/%d-%d.%s',
            $id,
            time(),
            $file->extension()
        );

        Storage::disk('images')->put($path, file_get_contents($file));
        $reference->cover_path = $path;
        $reference->save();
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|Integer|not_in:0',
            'authors' => 'required|array|min:1',
            'publish_year' => 'required|regex:/[1-2][0-9]{3}/',
            'properties.volume' => 'required_if:type,1|nullable|regex:/^[0-9a-zA-Z]+/',
            'properties.book_title' => 'required_if:type,1|required_if:type,2|required_if:type,3',
            'properties.pages_range' => 'nullable|regex:/^[0-9\-–,]+$/u',
            'properties.edition' => 'nullable',
            'properties.chapter' => 'nullable|regex:/^[0-9a-zA-Z]+/',
            'properties.url' => 'nullable|url',
        ], [
            'min' => '必填',
            'not_in' => '必填',
            'required' => '必填',
            'required_if' => '必填',
            'integer' => '須為數字',
            'properties.volume.regex' => '只允許「數字」、「英文」',
            'properties.chapter.regex' => '只允許「數字」、「英文」',
            'properties.pages_range.regex' => '只允許「數字」、「–」、「,」',
            'regex' => '格式不符',
        ]);

        $authors = $request->get('authors');
        $isPublish = $request->get('is_publish');
        $editors = $request->get('editors', []);
        $type = $request->get('type');
        $properties = $request->get('properties');

        DB::beginTransaction();

        try {
            $newReference = new Reference();
            $newReference->type = $type;
            $newReference->cover_path = '';
            $newReference->title = $request->get('title') ?? '';
            $newReference->subtitle = $request->get('subtitle') ?? '';
            $newReference->publish_year = $request->get('publish_year');
            $newReference->language = $request->get('language');

            $newReference->properties = (new ReferenceOtherPropertiesFactory())
                ->createPropertiesFromType($type)
                ->setProperties($properties)
                ->toArray();

            $newReference->note = $request->get('note') ?? '';
            $newReference->is_publish = (bool) $isPublish;
            $newReference->save();

            $newReference->saveAuthors($authors);

            if ($type === Reference::TYPE_JOURNAL || $type === Reference::TYPE_BOOK_ARTICLE || $type === Reference::TYPE_BOOK) {
                $newReference->saveBook($properties['book_title'], $properties['book_title_abbreviation'] ?? '', $editors);
            }

            // upload image
            $file = $request->get('image');
            if ($file) {
                $extension = explode('/', mime_content_type($file))[1];
                $path = sprintf(
                    'references/%d-$d.%s',
                    $newReference->id,
                    time(),
                    $extension
                );

                Storage::disk('images')->put($path, file_get_contents($file));
                $newReference->cover_path = $path;
                $newReference->save();
            }

            // 儲存 computed data
            $r = json_encode(Reference::with(['authors'])->find($newReference->id)->toJson());
            exec("node ../public/js/computeReference.js --r={$r} 2>&1", $title);
            $newReference->title = $title[0] ?? '';
            $newReference->subtitle = $title[1] ?? '';
            $newReference->save();

            // save to my favorite list
            $item = new FavoriteMineItem();
            $item->collectable_type = FavoriteMineItem::TYPE_REFERENCE;
            $item->collectable_id = $newReference->id;
            $item->user_id = Auth::user()->id;
            $item->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            Log::error("[reference store]: {$e->getMessage()}");
            return response([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
            ])->setStatusCode(500);
        }
        return response(ReferenceCollection::collection([$newReference])[0]);
    }

    public function usages(Request $request, $id)
    {
        $usages = ReferenceUsage::with([
            'taxonName.nomenclature',
            'taxonName.reference',
            'taxonName.rank',
            'taxonName.authors',
            'taxonName.exAuthors',
            'taxonName.reference.authors',
            'taxonName.originalTaxonName',
            'taxonName.originalTaxonName.authors',
            'taxonName.originalTaxonName.exAuthors',
        ])
            ->where('is_for_publish', 0)
            ->where('reference_id', $id)
            ->orderBy('group')
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => $usages->groupBy('group')->map(function($group) {
                return $group->map(function($usage) {
                    return UsageCollection::collection([$usage])->first();
                });
            })
        ]);
    }
}
