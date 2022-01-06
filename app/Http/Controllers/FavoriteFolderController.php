<?php

namespace App\Http\Controllers;

use App\FavoriteFolder;
use App\Http\Resources\FavoriteFolderCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;


class FavoriteFolderController extends Controller
{
    public function index(Request $request)
    {
        $folders = $request->user()
            ->folders()
            ->orderBy('id')
            ->get();

        return response([
            'data' => FavoriteFolderCollection::collection($folders),
        ]);
    }

    public function show(Request $request, $id)
    {
        $folder = $request->user()
            ->folders()
            ->where('id', $id)
            ->first();

        if (!$folder) {
            return response()->noContent()->setStatusCode(404);
        }

        return response([
            'data' => FavoriteFolderCollection::collection([$folder])->first(),
        ]);
    }

    public function getFolderWithItemStatus(Request $request)
    {
        $request->validate([
            'type' => 'required|Integer|in:1,2,3',
            'id' => 'required|Integer',
        ]);

        $type = (int) $request->get('type');
        $id = (int) $request->get('id');

        $folders = FavoriteFolder::query()
            ->with([
                'items' => function ($query) use ($type, $id) {
                    $query->where('collectable_type', $type)
                        ->where('collectable_id', $id);
                }
            ])
            ->where('user_id', $request->user()->id)
            ->get();

        $folders = $folders->map(function ($f) {
            return [
                'id' => $f->id,
                'title' => $f->title,
                'is_exist_in_target' => (boolean) $f->items->count() >= 1,
                'updated_at' => $f->updated_at,
            ];
        });

        return response([
            'data' => $folders,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
        ]);

        $folder = FavoriteFolder::find($id);

        if ($folder->user_id !== $request->user()->id) {
            return response()->json([], 401);
        }

        $folder->title = $request->get('title');
        $folder->save();

        return response([
            'data' => $folder,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
        ]);

        $folder = new FavoriteFolder();
        $folder->title = $request->get('title');

        $request->user()->namespaces()->save($folder);

        return response([
            'data' => FavoriteFolderCollection::collection([$folder])->first(),
        ]);
    }
}
