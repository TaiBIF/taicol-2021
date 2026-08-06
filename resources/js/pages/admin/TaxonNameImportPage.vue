<template>
    <div class="w-full h-full">
        <div class="container flex flex-col h-full">
            <div class="bg-white">
                <div class="py-3 flex items-center">
                    <p class="ml-4 font-bold text-3xl inline">{{ $t('header.adminMenu.taxonNameImport') }}</p>
                </div>
                <div class="flex px-4 mb-4 gap-1">
                    <div class="w-full">
                        <input
                            class="input is-fullwidth"
                            type="file"
                            v-on:change="onSetFile($event)"
                        />
                        <span v-for="message in errors.file" class="text-red-500" v-text="message"></span>
                    </div>

                    <button :disabled="isLoading" class="button" v-on:click="onSubmit">匯入</button>
                </div>

                <ul class="px-4 pb-2">
                    <li>1. 第一列請保留欄位名稱（表頭），系統以表頭比對，欄位順序可調整，但請勿更改欄名</li>
                    <li>2. 命名規約, 階層, name 必填</li>
                    <li>3. 作者欄位使用原母語完整名以「|」分隔；亦可改填對應的 person_id 欄位
                        （original_name_author_id 等），有填 id 時優先以 id 認定，可避免同名歧義</li>
                </ul>
                <div class="overflow-y-auto grow">
                <table class="narrow-table w-full">
                    <thead>
                    <tr>
                        <td>nomenclature</td>
                        <td>rank</td>
                        <td>latin_name</td>
                        <td>latin_genus</td>
                        <td>latin_s1</td>
                        <td>s2_rank</td>
                        <td>latin_s2</td>
                        <td>original_name</td>
                        <td>original_name_author</td>
                        <td>original_name_author_id</td>
                        <td>original_name_exauthor</td>
                        <td>original_name_exauthor_id</td>
                        <td>formatted_authors</td>
                        <td>name_authors</td>
                        <td>name_authors_id</td>
                        <td>name_ex_authors</td>
                        <td>name_ex_authors_id</td>
                        <td>reference_name</td>
                        <td>reference_id</td>
                        <td>page</td>
                        <td>cite_figure</td>
                        <td>year</td>
                        <td>note</td>
                        <td>kingdom_name</td>
                    </tr>
                    </thead>
                </table>
                </div>
                <!-- 處理中：階段 + 進度條 -->
                <div v-if="isLoading" class="px-4 pb-2">
                    <div class="text-sm mb-1">檔案：{{ currentFilename || '(未知)' }}</div>
                    <div class="text-blue-600 mb-1">
                        {{ phase === 'saving' ? '寫入中' : '驗證中' }}：{{ processedRows }} / {{ totalRows || '?' }}
                        （可離開本頁，回來會自動接續）
                    </div>
                    <div class="w-full bg-gray-200 rounded h-2">
                        <div class="bg-blue-500 h-2 rounded"
                            :style="{ width: totalRows ? (processedRows / totalRows * 100) + '%' : '0%' }"></div>
                    </div>
                    <button class="button is-small mt-2" :disabled="cancelling" v-on:click="onCancel">
                        {{ cancelling ? '取消中…' : '取消匯入' }}
                    </button>
                </div>

                <!-- 失敗卡片：檔名 + 時間 + 訊息 + 下載 + 取消 -->
                <div v-if="failedLog" class="px-4 pb-2 mt-2 border border-red-300 rounded p-3">
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

                <div v-if="cancelledLog" class="px-4 pb-2 mt-2 border border-gray-300 rounded p-3">
                    <div class="font-bold mb-1">已取消匯入</div>
                    <div class="text-sm">檔案：{{ cancelledLog.originalFilename || '(未知)' }}</div>
                    <div class="text-sm mb-2">{{ cancelledLog.errorMessage }}</div>
                    <button class="button is-small" v-on:click="onClearCancelled">知道了</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, inject, ref, onMounted, onBeforeUnmount } from '@vue/composition-api';
import LoadingSection from '../../components/LoadingSection.vue';
import { openNotify } from '../../utils';

export default defineComponent({
    setup() {
        const axios: any = inject('axios');
        const formData = new FormData();

        const errors = ref<any>({});        // 檔案格式等 422 錯誤

        const isLoading = ref<boolean>(false);   // 上傳後處理中／輪詢中
        const status = ref<string>('');
        const successCount = ref<number | null>(null);
        const totalRows = ref<number | null>(null);
        const errorMessage = ref<string>('');
        const logId = ref<number | null>(null);

        let timer: any = null;
        const clearTimer = () => { if (timer) { clearInterval(timer); timer = null; } };

        const phase = ref('');
        const processedRows = ref(0);
        const failedLog = ref<any>(null);   // 失敗時保留整包給下方顯示
        const currentFilename = ref('');
        const cancelling = ref(false);
        const cancelledLog = ref<any>(null);

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

        const onDismiss = () => {
            if (!failedLog.value) return;
            axios.post(`/import/logs/${failedLog.value.id}/dismiss`).then(() => {
                failedLog.value = null;
                resetStates();
            });
        };

        const onCancel = () => {
            if (!logId.value) return;
            cancelling.value = true;
            axios.post(`/import/logs/${logId.value}/cancel`)
                .catch(() => { cancelling.value = false; });
        };

        const onClearCancelled = () => {
            cancelledLog.value = null;
            resetStates();
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
            poll();                             // 立刻打一次，不用等 3 秒
            timer = setInterval(poll, 3000);
        };

        const resetStates = () => {
            errors.value = {};
            errorMessage.value = '';
            successCount.value = null;
            totalRows.value = null;
            status.value = '';
        };

        const onSetFile = (event) => { formData.set('file', event.target.files[0]); };

        const onSubmit = () => {
            resetStates();
            isLoading.value = true;

            axios.post('/import/taxon-names', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
                .then(({ data: { logId } }) => { startPolling(logId); })
                .catch(({ status: code, errors: e, message }) => {
                    isLoading.value = false;
                    if (code === 409) {
                        openNotify(message, 'is-danger');   // 已有處理中的匯入
                    } else if (code === 422) {
                        errors.value = e;
                    } else {
                        openNotify(message || '上傳失敗', 'is-danger');
                    }
                });
        };

        // 進頁補畫面：未結束就接續輪詢，已結束直接顯示
        onMounted(() => {
            axios.get('/import/logs/latest', { params: { type: 'taxon_name' } })
                .then(({ data: { data } }) => {
                    if (!data) return;
                    if (data.status === 'pending' || data.status === 'processing') {
                        startPolling(data.id);
                    } else {
                        applyLog(data);
                    }
                });
        });

        onBeforeUnmount(clearTimer);

        return {
            errors, isLoading, status,
            successCount, totalRows, errorMessage,
            onSetFile, onSubmit,
            phase, processedRows, failedLog, onDismiss,
            currentFilename, cancelling, onCancel,
            cancelledLog, onClearCancelled
        };
    },
    components: { LoadingSection },
});
</script>

<style lang="scss">
.narrow-table {
    td {
        padding-left: 2px;
        padding-right: 2px;
        border: 1px solid #dbdbdb;
        background-color: white;
        white-space: nowrap;
    }

    thead {
        td {
            background-color: #dbdbdb;
            border: 0px solid #dbdbdb;
            position: sticky;
            top: 0;
        }
    }
}
</style>
