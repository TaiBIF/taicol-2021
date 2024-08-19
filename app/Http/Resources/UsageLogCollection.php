<?php

namespace App\Http\Resources;

use App\ImportUsageLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


const actionMap = [
    ImportUsageLog::ACTION_FIRST_IMPORT => 'import',
    ImportUsageLog::ACTION_APPEND => 'append',
    ImportUsageLog::ACTION_OVERWRITE => 'overwrite',
    ImportUsageLog::ACTION_USAGE_ADD => 'add',
    ImportUsageLog::ACTION_USAGE_UPDATE => 'edit',
    ImportUsageLog::ACTION_USAGE_DELETE => 'delete',
];



const statusMap = [
    'accepted' => 'o',
    'not-accepted' => 'x',
    'misapplied' => '≠',
    'unknown' => '?',
];


class UsageLogCollection extends JsonResource
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
            'edited_name' => isset($this->taxonName) ? $this->taxonName->name : null,
            'name_status' => isset($this->status) ? statusMap[$this->status] : null,
            'item' => isset($this->columns) ? str_replace(',', ', ', $this->columns) : null,
            'by' => $this->user->name,
        ];
    }
}