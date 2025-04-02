<?php

namespace App\Http\Controllers;


use App\ImportUsageLog;
use App\Log;
use App\Http\Resources\EditLogCollection;
use App\Http\Resources\UsageLogCollection;
use App\Http\Resources\CommonNameEditLogCollection;
use Illuminate\Http\Request;

const LogTypeMap = [
    'taxonname' => 1,
    'reference' => 2,
    'person' => 3,
    'common_name' => 4,
];


class EditLogController extends Controller
{
    public function logs(Request $request)
    {
        $offset = $request->get('offset', 0);
        $log_type = $request->get('log_type', 0);
        $log_id = $request->get('log_id', 0);

        if ($log_type == 'usage'){

            $logs = ImportUsageLog::with(['user:id,name','taxonName:id,name'])
                    ->select('columns','import_usage_logs.created_at','action','user_id','reference_usages.status','import_usage_logs.taxon_name_id')
                    ->Leftjoin('reference_usages', 'reference_usage_id', '=', 'reference_usages.id')
                    ->where('import_usage_logs.reference_id','=',$log_id)
                    ->where('import_usage_logs.action_log_id', '=', NULL)
                    ->orderBy('created_at')
                    ->limit(5)
                    ->offset($offset)
                    ->get(); 
                    
            $logs = UsageLogCollection::collection($logs);

        } else if ($log_type=='commonname'){

            $logs = ImportUsageLog::
                    select('import_usage_logs.created_at','action','user_id','reference_usages.id','reference_usages.taxon_name_id')
                    ->join('reference_usages', 'reference_usage_id', '=', 'reference_usages.id')
                    ->where('import_usage_logs.reference_id','=', 95)
                    ->where('import_usage_logs.action_log_id', '=', NULL)
                    ->orderBy('created_at','desc')
                    ->limit(5)
                    ->offset($offset)
                    ->get(); 
                    
            $logs = CommonNameEditLogCollection::collection($logs);


        } else {

            $logs = Log::with(['user:id,name'])
                    ->select('columns','action','user_id','created_at')
                    ->where('logs.model_id','=',$log_id)
                    ->where('logs.type','=', LogTypeMap[$log_type])
                    ->orderBy('created_at')
                    ->limit(5)
                    ->offset($offset)
                    ->get(); 

            $logs = EditLogCollection::collection($logs);

        }

        $more = count($logs) < 5 ? false : true;

        return response()->
            json([
                'editLogs' => $logs,
                'logMore' => $more,
                'logOffset' => $offset+5,
            ]);
        
    }

}