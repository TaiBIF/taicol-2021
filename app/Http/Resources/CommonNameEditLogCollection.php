<?php

namespace App\Http\Resources;

use App\ImportUsageLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\ReferenceUsage;


const actionMap = [
    ImportUsageLog::ACTION_COMMON_NAME_UPDATE => 'create',
];


class CommonNameEditLogCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {

        # 取出對應的common_name
        $currentUsage = ReferenceUsage::find($this->id);
        $common_names = $currentUsage->properties['common_names'];

        $names = array_column($common_names, 'name');
        $common_names = implode(', ', $names);

        return [
            'created_at' => $this->created_at->format('Y-m-d'),
            'action' => actionMap[$this->action],
            'edited_taxon_name_id' => $this->taxon_name_id,
            'item' => $common_names,
            'by' => $this->user->name,
        ];
    }
}