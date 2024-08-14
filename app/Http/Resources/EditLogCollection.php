<?php

namespace App\Http\Resources;

use App\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


const actionMap = [
    Log::ACTION_CREATE => 'create',
    Log::ACTION_UPDATE => 'update',
    Log::ACTION_IMPORT => 'import',
];



class EditLogCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'created_at' => $this->created_at->format('Y-m-d'),
            'action' => actionMap[$this->action],
            'item' => isset($this->columns) ? str_replace(',', ', ', $this->columns) : null,
            'by' => $this->user->name,
        ];
    }
}
