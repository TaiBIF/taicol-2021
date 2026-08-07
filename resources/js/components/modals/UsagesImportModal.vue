<template>
    <div>
        <div class="px-16 py-6 min-w-400">
            <div>
                <p class="title text-center">{{ $t('namespace.importUsages') }}</p>
            </div>
            <div class="py-4 min-h-3/5">
                <div class="w-full">
                    <input
                        class="input is-fullwidth"
                        type="file"
                        :disabled="isLoading"
                        v-on:change="onSetFile($event)"
                    />
                    <span v-for="message in errors.file" class="text-red-500" v-text="message"></span>
                </div>

                <!-- 處理中：階段 + 進度條 + 取消 -->
                <div v-if="isLoading" class="mt-3">
                    <div class="text-sm mb-1">檔案：{{ currentFilename || '(未知)' }}</div>
                    <div v-if="phase === 'creating_names'" class="text-blue-600 mb-1">
                        新增學名中…（關閉視窗仍會在背景處理，重開會自動接續）
                    </div>
                    <div v-else-if="phase === 'importing_usages'" class="text-blue-600 mb-1">
                        匯入中…（關閉視窗仍會在背景處理，重開會自動接續）
                    </div>
                    <div v-else class="text-blue-600 mb-1">
                        {{ phase === 'saving' ? '寫入中' : '驗證中' }}：{{ processedRows }} / {{ totalRows || '?' }}
                        （關閉視窗仍會在背景處理，重開會自動接續）
                    </div>
                    <div class="w-full bg-gray-200 rounded h-2">
                        <div class="bg-blue-500 h-2 rounded"
                            :style="{ width: totalRows ? (processedRows / totalRows * 100) + '%' : '0%' }"></div>
                    </div>
                    <button class="button is-small mt-2" :disabled="cancelling" v-on:click="onCancel">
                        {{ cancelling ? '取消中…' : '取消匯入' }}
                    </button>
                </div>

                <!-- 失敗卡片：檔名 + 時間 + 訊息 + 下載錯誤檔 -->
                <div v-if="failedLog" class="mt-3 border border-red-300 rounded p-3">
                    <div class="text-red-600 font-bold mb-1">匯入失敗</div>
                    <div class="text-sm">檔案：{{ failedLog.originalFilename || '(未知)' }}</div>
                    <div class="text-sm">時間：{{ failedLog.completedAt || failedLog.createdAt }}</div>
                    <div class="text-sm mb-2">訊息：{{ failedLog.errorMessage }}</div>
                    <div class="flex gap-2">
                        <a v-if="failedLog.errorFileUrl" :href="failedLog.errorFileUrl"
                        class="button is-small" download>下載錯誤檔</a>
                        <button class="button is-small" v-on:click="onDismiss">取消 / 不再顯示</button>
                    </div>
                </div>

                <!-- 待新增學名卡片 -->
                <div v-if="awaitingLog" class="mt-3 border border-amber-300 rounded p-3">
                    <div class="text-amber-600 font-bold mb-1">有一批匯入待新增學名</div>
                    <div class="text-sm">檔案：{{ awaitingLog.originalFilename || '(未知)' }}</div>
                    <div class="text-sm mb-2">此份匯入有未比對到的學名，需先完成批次新增才能繼續匯入。</div>
                    <div class="flex gap-2">
                        <button class="button is-small is-link" v-on:click="onGoCreateName">前往新增</button>
                        <button class="button is-small" :disabled="discarding" v-on:click="onDiscardAwaiting">
                            {{ discarding ? '處理中…' : '放棄' }}
                        </button>
                    </div>
                </div>

                <!-- 取消結果卡片 -->
                <div v-if="cancelledLog" class="mt-3 border border-gray-300 rounded p-3">
                    <div class="font-bold mb-1">已取消匯入</div>
                    <div class="text-sm">檔案：{{ cancelledLog.originalFilename || '(未知)' }}</div>
                    <div class="text-sm mb-2">{{ cancelledLog.errorMessage }}</div>
                    <button class="button is-small" v-on:click="onClearCancelled">知道了</button>
                </div>

                <div class="p-2">
                    <ol class="list-decimal ml-2">
                        <li>
                            欄位內容請依照範本填寫
                            <a class="text-blue-700 underline" download="usage-import.xlsx"
                               href="/example/usage-import.xlsx">{{ $t('namespace.exampleDownload') }}</a>
                        </li>
                        <li>支援檔案格式 xlsx, xls</li>
                        <li>命名規約、階層、學名為必填
                            <ul class="description-list">
                                <li>命名規約項目：ICZN、ICN、ICNP、ICVCN</li>
                            </ul>
                        </li>
                        <li>階層：kingdom ~ species、subspecies、variety
                            <ul class="description-list">
                                <li>階層全名，且字首不大寫</li>
                            </ul>
                        </li>
                        <li>地位：accepted、not-accepted、misapplied、undetermined</li>
                        <li>外來屬性：native、naturalized、invasive、cultured</li>
                        <li>標註：多個時以「,」分隔，並僅能匯入目前系統有的</li>
                        <li>文獻格式：reference_id,show_page,figure,pro_parte|reference_id,show_page,figure,pro_parte
                            <ul class="description-list">
                                <li>多筆時以「|」分隔</li>
                                <li>reference_id為必填，其他任何一欄位為空值時仍需要留空並保留逗號</li>
                                <li>figure內容下如有逗號「,」時前後需加上雙引號「"」</li>
                                <li>pro_parte：填入true或false，false可省略留空</li>
                                <li>範例：128,294,"fig. 1, 2",false|141,10,,false</li>
                            </ul>
                        </li>
                        <li>自訂欄位支援custom_fields1至custom_fields5，將欲使用於自訂欄位欄位名的名稱放在欄位內容的最前方，如：
                            <ul class="description-list">
                                <li>IUCN Red List Category:The author considers V. hsuii to be Threatened (NT) following the IUCN Red List criteria (IUCN 2003).</li>
                            </ul>
                        </li>
                        <li>俗名格式：common_name(language,area)|common_name(language,area)
                            <ul class="description-list">
                                <li>多個時以「|」分隔</li>
                                <li>common_name和language為必填，area如為空值需要保留前方逗點，如為臺灣使用建議填入Taiwan</li>
                                <li>language語言項目：英文、繁體中文、日文、簡體中文、德文、法文、拉丁文、其他</li>
                                <li>範例：構樹(繁體中文,Taiwan)|鹿仔樹(繁體中文,)</li>
                            </ul>
                        </li>
                        <li>是否選項：是 = 1, 否 = 0, 不選擇 = (留空值)</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="sticky bottom-0 p-4 bg-white border-t">
            <div class="buttons is-right">
                <button class="button mr-2" :disabled="isLoading" v-on:click="onImportExcel">
                    {{ $t('common.import') }}
                </button>
                <button class="button mr-2" v-on:click="close">{{ $t('common.close') }}</button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, inject, ref, onMounted, onBeforeUnmount, PropType } from '@vue/composition-api';
import { debounce } from 'lodash';
import { openNotify } from '../../utils';

export default defineComponent({
    props: {
        namespaceId: { type: Number, required: true },
        refresh: { type: Function as PropType<() => void>, default: () => {} },
        initialLogId: { type: Number, default: null },   // ← 新增
    },
    setup(props, context) {
        const axios: any = inject('axios');
        const app: any = context.root;
        const store = app.$store;

        const { namespaceId, refresh, initialLogId } = props;

        const formData = new FormData();
        const errors = ref<any>({});

        const isLoading = ref<boolean>(false);
        const status = ref<string>('');
        const phase = ref<string>('');
        const totalRows = ref<number | null>(null);
        const processedRows = ref<number>(0);
        const successCount = ref<number | null>(null);
        const errorMessage = ref<string>('');
        const currentFilename = ref<string>('');
        const logId = ref<number | null>(null);
        const cancelling = ref<boolean>(false);
        const failedLog = ref<any>(null);
        const cancelledLog = ref<any>(null);
        const awaitingLog = ref<any>(null);
        const discarding = ref<boolean>(false);

        let timer: any = null;
        const clearTimer = () => { if (timer) { clearInterval(timer); timer = null; } };

        const resetStates = () => {
            errors.value = {};
            errorMessage.value = '';
            successCount.value = null;
            totalRows.value = null;
            processedRows.value = 0;
            phase.value = '';
            status.value = '';
        };

        const applyLog = (log) => {
            status.value = log.status;
            phase.value = log.phase || '';
            currentFilename.value = log.originalFilename || '';
            cancelling.value = !!log.cancelRequestedAt;
            totalRows.value = log.totalRows;
            processedRows.value = log.processedRows || 0;
            successCount.value = log.successCount;
            errorMessage.value = log.errorMessage || '';

            if (log.status === 'completed') {
                isLoading.value = false;
                failedLog.value = null;
                clearTimer();
                openNotify(`成功匯入 ${log.successCount} 筆`);
                refresh();
                store.commit('closeModal');   // ← 成功後自動關閉 modal
            } else if (log.status === 'awaiting_names') {
                isLoading.value = false;
                clearTimer();
                store.commit('closeModal');   // ← 先關 modal
                app.$router.push({
                    name: 'namespace-name-create-page',
                    params: { id: namespaceId },
                    query: { import_log_id: log.id },
                });
            } else if (log.status === 'failed') {
                isLoading.value = false;
                failedLog.value = log;
                clearTimer();
                openNotify(log.errorMessage || '匯入失敗', 'is-danger');
            } else if (log.status === 'cancelled') {
                isLoading.value = false;
                cancelling.value = false;
                failedLog.value = null;
                cancelledLog.value = log;
                clearTimer();
            } else {
                isLoading.value = true;
            }
        };

        const poll = () => {
            if (!logId.value) return;
            axios.get(`/import/logs/${logId.value}`)
                .then(({ data: { data } }) => { if (data) applyLog(data); })
                .catch(() => { /* 單次失敗就等下一輪 */ });
        };

        const startPolling = (id) => {
            logId.value = id;
            isLoading.value = true;
            clearTimer();
            poll();                         // 立刻打一次
            timer = setInterval(poll, 3000);
        };

        const onSetFile = (event) => { formData.set('file', event.target.files[0]); };

        const onImportExcel = debounce(() => {
            resetStates();
            failedLog.value = null;
            cancelledLog.value = null;
            isLoading.value = true;

            axios.post(`/import/namespaces/${namespaceId}/usages`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
                .then(({ data: { logId: id } }) => { startPolling(id); })
                .catch(({ status: code, errors: e, message }) => {
                    isLoading.value = false;
                    if (code === 409) {
                        openNotify(message, 'is-danger');
                    } else if (code === 422) {
                        errors.value = e;
                    } else {
                        openNotify(message || '上傳失敗', 'is-danger');
                    }
                });
        });

        const onCancel = () => {
            if (!logId.value) return;
            cancelling.value = true;
            axios.post(`/import/logs/${logId.value}/cancel`)
                .catch(() => { cancelling.value = false; });
        };

        const onDismiss = () => {
            if (!failedLog.value) return;
            axios.post(`/import/logs/${failedLog.value.id}/dismiss`).then(() => {
                failedLog.value = null;
                resetStates();
            });
        };

        const onClearCancelled = () => {
            if (cancelledLog.value) {
                axios.post(`/import/logs/${cancelledLog.value.id}/dismiss`).catch(() => {});
            }
            cancelledLog.value = null;
            resetStates();
        };
        const close = () => { store.commit('closeModal'); };

        // 開 modal 就撈 latest：未結束接續輪詢，已結束顯示結果卡片
        onMounted(() => {
            if (initialLogId) {
                startPolling(initialLogId);   // 直接輪詢指定 log；已完成也會回 completed → 跳成功訊息
                return;
            }
            axios.get('/import/logs/latest', {
                params: { type: 'namespace_usage', namespace_id: namespaceId },
            })
            .then(({ data: { data } }) => {
                if (!data) return;
                if (data.status === 'pending' || data.status === 'processing') {
                    startPolling(data.id);
                } else if (data.status === 'awaiting_names') {
                    awaitingLog.value = data;
                } else {
                    applyLog(data);
                }
            });
        });

        onBeforeUnmount(clearTimer);

        const onGoCreateName = () => {
            store.commit('closeModal');   // 先關 modal
            app.$router.push({
                name: 'namespace-name-create-page',
                params: { id: namespaceId },
                query: { import_log_id: awaitingLog.value.id },
            });
        };

        const onDiscardAwaiting = () => {
            if (!awaitingLog.value) return;
            discarding.value = true;
            axios.post(`/import/logs/${awaitingLog.value.id}/dismiss`)
                .then(() => { awaitingLog.value = null; resetStates(); })
                .finally(() => { discarding.value = false; });
        };

        return {
            errors,
            isLoading,
            status,
            phase,
            totalRows,
            processedRows,
            currentFilename,
            cancelling,
            failedLog,
            cancelledLog,
            onSetFile,
            onImportExcel,
            onCancel,
            onDismiss,
            onClearCancelled,
            close,
            awaitingLog,
            discarding,
            onGoCreateName,
            onDiscardAwaiting,
        };
    },
});
</script>
<style lang="scss" scoped>
.description-list {
    margin-left: 1rem;
    list-style: circle;
}
</style>