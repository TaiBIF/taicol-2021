<?php

namespace App\Http\Services;

use App\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use MyCLabs\Enum\Enum;


/**
 * @mixin Enum
 */
final class LogType extends Enum
{
    private const TAXON_NAME = 1;
    private const REFERENCE = 2;
    private const PERSON = 3;
}

final class LogAction extends Enum
{
    private const CREATE = 1;
    private const UPDATE = 2;
    private const IMPORT = 3;
}

class LogService
{

    private Log $log;

    public function __construct()
    {
        $this->log = new Log();
    }

    public function writeCreateLog(LogType $type, int $modelId, $columns = []): Model
    {
        return $this->write($type, $modelId, LogAction::CREATE(), $columns);
    }

    public function write(LogType $type, int $modelId, LogAction $action, $columns): Model
    {
        $this->log->type = $type->getValue();
        $this->log->model_id = $modelId;
        $this->log->action = $action->getValue();
        $this->log->columns = implode(',', array_map(fn($column) => $this->snakeToCamel($column), $columns));
        $this->log->user_id = Auth::user()->id;
        $this->log->save();

        return $this->log;
    }

    private function snakeToCamel($input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    public function writeImportLog(LogType $type, int $modelId, $columns = []): Model
    {
        return $this->write($type, $modelId, LogAction::IMPORT(), $columns);
    }

    public function writeUpdateLog(LogType $type, Model $model): Model
    {
        $changes = array_keys($model->getChanges());
        //remove timestamp change
        if (($key = array_search("updated_at", $changes)) !== false) {
            unset($changes[$key]);
        }
        return $this->write($type, $model->id, LogAction::UPDATE(), $changes);
    }

    public function writeUpdateLogWithComparison(LogType $type, Model $newModel, Model $oldModel, array $includeColumns = [], array $excludeColumns = []): Model
    {
        $changes = $this->diffColumn($newModel->getAttributes(), $oldModel->getAttributes());

        //remove timestamp change and exclude columns
        $toRemove = array_merge(array('updated_at'), $excludeColumns);
        $changes = array_diff($changes, $toRemove);

        $changes = array_merge($changes, $includeColumns);

        //replace _id and properties
        foreach ($changes as $key => $value) {
            if (str_contains($value, "_id")) {
                $changes[$key] = str_replace("_id", "", $value);
            }
            if (str_contains($value, "properties.")) {
                $changes[$key] = str_replace("properties.", "", $value);
            }
            if (str_contains($value, "properties.usage.")) {
                $changes[$key] = str_replace("properties.usage.", "", $value);
            }
        }
        return $this->write($type, $newModel['id'], LogAction::UPDATE(), $changes);
    }

    private function diffColumn($oldModel, $newModel): array
    {
        $result = array();
        foreach ($oldModel as $key => $value) {
            if (array_key_exists($key, $newModel)) {
                $oldValue = $newModel[$key];
                if ($this->isJsonString($value) || $this->isJsonString($oldValue)) {
                    $value = json_decode($value, true);
                    $oldValue = json_decode($oldValue, true);
                }
                if ($this->isObject($value) && $this->isObject($oldValue)) {
                    $subResult = $this->diffColumn($value, $oldValue);
                    if (count($subResult)) {
                        foreach ($subResult as $subKey) {
                            array_push($result, "$key.$subKey");
                        }
                    }
                } else if (is_array($value) && is_array($oldValue)) {
                    foreach ($value as $index => $subValue) {
                        if (array_key_exists($index, $oldValue)) {
                            $isEqual = $this->isEqual($subValue, $oldValue[$index]);
                            if (!$isEqual) { // just check if equal or not, won't save nested path
                                array_push($result, "$key.$index");
                            }
                        } else {
                            array_push($result, "$key.$index");
                        }
                    }
                } else {
                    if ($value != $oldValue) {
                        array_push($result, $key);
                    }
                }
            } else {
                if ($value != "") { //if not auto generate column(empty string)
                    array_push($result, $key);
                }
            }
        }
        return $result;
    }

    private function isJsonString($string): bool
    {
        if (!is_string($string) || is_object($string) || is_numeric($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function isObject($target): bool
    {
        if (!is_array($target)) return false;
        if ($target === []) return false;
        return array_keys($target) !== range(0, count($target) - 1);
    }

    private function isEqual($value1, $value2): bool
    {
        if (!is_array($value1) || !is_array($value2)) {
            return $value1 == $value2;
        }
        foreach ($value1 as $key => $value1Value) {
            if (array_key_exists($key, $value2)) {
                $value2Value = $value2[$key];
                if ($this->isJsonString($value1Value) || $this->isJsonString($value2Value)) {
                    $value1Value = json_decode($value1Value, true);
                    $value2Value = json_decode($value2Value, true);
                }
                if ($this->isObject($value1Value) && $this->isObject($value2Value)) {
                    $subResult = $this->isEqual($value1Value, $value2Value);
                    if (!$subResult) {
                        return false;
                    }
                } else if (is_array($value1Value) && is_array($value2Value)) {
                    $subResult = $this->isEqual($value1Value, $value2Value);
                    if (!$subResult) {
                        return false;
                    }
                } else {
                    if ($value1Value != $value2Value) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
        return true;
    }
}