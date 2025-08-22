<?php

namespace App\Http\Services\UsagePreview\Traits;

trait ArrayConversionTrait
{
    /**
     * 確保輸入是 array 格式
     * 
     * @param mixed $data
     * @return array
     */
    protected function ensureArray($data)
    {
        if (empty($data)) {
            return [];
        }

        // 如果已經是 array，直接返回
        if (is_array($data)) {
            return $data;
        }

        // 處理 Laravel 特定的類型
        if (is_object($data)) {
            // Laravel ResourceCollection 或 JsonResource
            if ($data instanceof \Illuminate\Http\Resources\Json\ResourceCollection || 
                $data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                try {
                    return $data->toArray(request());
                } catch (\Exception $e) {
                    // 如果 request() 失敗，嘗試創建空的 request
                    try {
                        return $data->toArray(new \Illuminate\Http\Request());
                    } catch (\Exception $e2) {
                        // 如果還是失敗，嘗試使用 resolve() 方法
                        try {
                            if (method_exists($data, 'resolve')) {
                                $resolved = $data->resolve(new \Illuminate\Http\Request());
                                return is_array($resolved) ? $resolved : (array) $resolved;
                            }
                        } catch (\Exception $e3) {
                            // 最後 fallback
                        }
                        return (array) $data;
                    }
                }
            }

            // 特殊處理 TaxonNameCollection 或類似的自定義 Collection
            $className = get_class($data);
            if (strpos($className, 'Collection') !== false && method_exists($data, 'toArray')) {
                try {
                    return $data->toArray(request());
                } catch (\ArgumentCountError $e) {
                    // 如果需要 request 參數但失敗了
                    try {
                        return $data->toArray(new \Illuminate\Http\Request());
                    } catch (\Exception $e2) {
                        return (array) $data;
                    }
                } catch (\Exception $e) {
                    return (array) $data;
                }
            }

            // Eloquent Collection
            if ($data instanceof \Illuminate\Database\Eloquent\Collection) {
                return $data->toArray();
            }

            // Support Collection
            if ($data instanceof \Illuminate\Support\Collection) {
                return $data->toArray();
            }

            // 其他有 toArray 方法的 object
            if (method_exists($data, 'toArray')) {
                try {
                    // 嘗試不帶參數調用
                    return $data->toArray();
                } catch (\ArgumentCountError $e) {
                    // 如果需要參數，嘗試傳入 request
                    try {
                        return $data->toArray(request());
                    } catch (\Exception $e2) {
                        return $data->toArray(new \Illuminate\Http\Request());
                    }
                } catch (\Exception $e) {
                    // 其他錯誤，轉換為 array
                    return (array) $data;
                }
            }

            // 沒有 toArray 方法的 object，直接轉換
            return (array) $data;
        }

        // 其他情況，嘗試轉換
        return (array) $data;
    }

    /**
     * 安全地獲取陣列中的值
     * 
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function safeGet($array, $key, $default = null)
    {
        if (!is_array($array)) {
            $array = $this->ensureArray($array);
        }
        
        return $array[$key] ?? $default;
    }

    /**
     * 處理可能為 Collection 或 array 的資料
     * 
     * @param mixed $data
     * @param callable $callback
     * @return array
     */
    protected function mapEnsureArray($data, callable $callback)
    {
        $array = $this->ensureArray($data);
        return array_map($callback, $array);
    }

    /**
     * 檢查資料是否為空（支援 Collection 和 array）
     * 
     * @param mixed $data
     * @return bool
     */
    protected function isDataEmpty($data)
    {
        if (empty($data)) {
            return true;
        }

        if (is_object($data) && method_exists($data, 'isEmpty')) {
            return $data->isEmpty();
        }

        if (is_array($data)) {
            return empty($data);
        }

        return false;
    }
}