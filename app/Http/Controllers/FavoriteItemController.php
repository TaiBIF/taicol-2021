<?php

namespace App\Http\Controllers;

use App\FavoriteFolder;
use App\FavoriteItem;
use App\FavoriteMineItem;
use App\Http\Resources\ReferenceCollection;
use App\Http\Resources\TaxonNameCollection;
use App\Reference;
use App\ReferenceUsage;
use App\TaxonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteItemController extends Controller
{
    private $validTypes = [
        FavoriteItem::TYPE_USAGE,
        FavoriteItem::TYPE_TAXON_NAME,
        FavoriteItem::TYPE_REFERENCE,
    ];

    public function index(Request $request, $folderId)
    {
        $userId = Auth::user()->id;

        $folder = FavoriteFolder::where('id', (int) $folderId)
            ->where('user_id', $userId)
            ->first();


        if ((int) $folderId !== 0 && !$folder) {
            return response([])->setStatusCode(404);
        }

        if ((int) $folderId == 0) {
            $items = FavoriteMineItem::with('collectable')
                ->where('user_id', $userId)
                ->get();
        } else {
            $items = FavoriteItem::with('collectable')
                ->where('favorite_folder_id', $folderId)
                ->get();
        }

        $items = $items->map(function ($item) {
            $content = [
                'id' => $item->id,
                'type' => $item->collectable_type,
            ];

            if (!$item->collectable) {
                $content = [
                    'id' => $item->id,
                    'type' => $item->collectable_type,
                ];
            } else if ($item->collectable_type === FavoriteItem::TYPE_TAXON_NAME) {
                $content['content'] = TaxonNameCollection::collection([$item->collectable])[0];
            } else if ($item->collectable_type === FavoriteItem::TYPE_REFERENCE) {
                $content['content'] = ReferenceCollection::collection([$item->collectable])[0];
            } else {
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
                        ->where('reference_id', $item->collectable->reference_id)
                        ->where('group', $item->collectable->group)
                        ->orderBy('order')
                        ->get();

                $content['reference_id'] = $item->collectable->reference_id;
                $content['content'] = $usages->map(function($usage) {
                    return [
                        'id' => $usage->id,
                        'parent_taxon_name_id' => $usage->parent_taxon_name_id,
                        'reference_id' => $usage->reference_id,
                        'taxon_name_id' => $usage->taxon_name_id,
                        'status' => $usage->status,
                        'is_title' => $usage->is_title,
                        'is_indent' => $usage->is_indent,
                        'is_for_publish' => $usage->is_for_publish,
                        'group' => $usage->group,
                        'show_page' => $usage->show_page,
                        'figure' => $usage->figure,
                        'name_remark' => $usage->name_remark,
                        'custom_name_remark' => $usage->custom_name_remark,
                        'per_usages' => $usage->per_usages,
                        'type_specimens' => $usage->type_specimens,
                        'properties' => $usage->properties,
                        'order' => $usage->order,
                        'taxon_name' => TaxonNameCollection::collection([$usage->taxonName])->first(),
                    ];
                });
                $content['reference'] = ReferenceCollection::collection([Reference::find($item->collectable->reference_id)])->first();
            }

            return $content;
        });

        return response()->json([
            'data' => $items->groupBy('type'),
        ]);
    }

    public function store(Request $request, $folderId)
    {
        $userId = Auth::user()->id;

        $folder = FavoriteFolder::where('id', $folderId)
            ->where('user_id', $userId)
            ->first();

        if (!$folder) {
            return response([])->setStatusCode(404);
        }

        $type = (int) $request->get('type');

        if (!in_array($type, $this->validTypes)) {
            return response([])->setStatusCode(422);
        }

        $item = new FavoriteItem();
        $item->collectable_type = $type;
        $item->collectable_id = (int) $request->get('id');
        $item->order = 1;

        $folder->items()->save($item);

        return response('')->setStatusCode(200);
    }

    public function destroy(Request $request, $folderId, $itemId)
    {
        $request->validate([
            'type' => 'required|Integer|in:1,2,3',
            'id' => 'required|Integer',
        ]);

        $userId = Auth::user()->id;

        $folder = FavoriteFolder::where('id', $folderId)
            ->where('user_id', $userId)
            ->first();

        if (!$folder) {
            return response([])->setStatusCode(404);
        }

        $type = (int) $request->get('type');
        $id = (int) $request->get('id');

        FavoriteItem::query()
            ->where('favorite_folder_id', $folder->id)
            ->where('collectable_type', $type)
            ->where('collectable_id', $id)
            ->delete();

        return response([
            'collectable_type' => $type,
            'collectable_id' => $id
        ]);
    }
}
