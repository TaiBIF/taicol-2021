<template>
    <div class="w-full h-full overflow-y-auto">
        <div class="container flex flex-col min-h-full pb-6">
            <div class="bg-white">
                <div class="py-3 flex items-center">
                    <p class="ml-4 font-bold text-3xl inline">{{ $t('header.adminMenu.updateService') }}</p>
                </div>

                <!-- 觸發 -->
                <div class="px-4 mb-2 flex items-center gap-4">
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('taxon')">執行名錄更新</button>
                    <span v-if="activeJob" class="text-sm text-gray-500">
                        執行中：{{ jobLabel(activeJob.job) }} #{{ activeJob.id }}（{{ activeJob.currentStep ? stepLabel(activeJob.currentStep) : '排隊中' }}）
                    </span>
                </div>
                <div class="px-4 mb-4 flex items-center gap-4">
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('release')">產生版本名錄</button>
                    <label class="flex items-center gap-1 text-sm">
                        <input type="checkbox" v-model="force" :disabled="isBusy || submitting">
                        force：略過 high issue 把關
                    </label>
                </div>

                <!-- job 與步驟說明 -->
                <div class="px-4 mb-4">
                    <details class="bg-gray-50 border rounded-md p-3 text-sm">
                        <summary class="font-bold cursor-pointer text-gray-700">任務與步驟說明</summary>
                        <div class="mt-2 border-t pt-2 grid md:grid-cols-2 gap-4">
                            <table class="w-full text-xs text-left text-gray-600 border-collapse">
                                <thead><tr class="border-b bg-gray-100"><th class="p-1.5 w-40">任務</th><th class="p-1.5">說明</th></tr></thead>
                                <tbody>
                                <tr v-for="j in labels.jobs" :key="j.key" class="border-b">
                                    <td class="p-1.5 font-bold border-r">{{ j.label }}</td>
                                    <td class="p-1.5">{{ j.description }}</td>
                                </tr>
                                </tbody>
                            </table>
                            <table class="w-full text-xs text-left text-gray-600 border-collapse">
                                <thead><tr class="border-b bg-gray-100"><th class="p-1.5 w-32">步驟</th><th class="p-1.5">說明</th></tr></thead>
                                <tbody>
                                <tr v-for="s in labels.steps" :key="s.key" class="border-b">
                                    <td class="p-1.5 font-bold border-r">{{ s.label }}</td>
                                    <td class="p-1.5">{{ s.description }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>

                <!-- 執行紀錄 -->
                <div class="px-4">
                    <div class="flex items-center justify-between mb-2 gap-3">
                        <p class="font-bold">執行紀錄</p>
                        <div class="flex items-center gap-2">
                            <select v-model="jobFilter" class="border rounded px-2 py-1 text-sm" v-on:change="onFilter">
                                <option value="">全部任務</option>
                                <option v-for="j in labels.jobs" :key="j.key" :value="j.key">{{ j.label }}</option>
                            </select>
                            <button :disabled="isLoading" class="button is-small" v-on:click="loadJobs">重新整理</button>
                        </div>
                    </div>
                    <div v-if="isLoading && !jobs.length" class="border">
                        <loading-section></loading-section>
                    </div>
                    <div v-else class="overflow-auto shadow-sm border">
                        <table class="table text-[13px] has-text-grey whitespace-nowrap w-full">
                            <thead class="font-bold sticky top-0 bg-white z-10">
                            <tr>
                                <th>#</th>
                                <th>任務</th>
                                <th>狀態</th>
                                <th>步驟</th>
                                <th>訊息</th>
                                <th>觸發</th>
                                <th>開始</th>
                                <th>結束</th>
                                <th>通知</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <template v-for="job in jobs">
                                <tr :key="job.id">
                                    <td>{{ job.id }}</td>
                                    <td :title="jobDescription(job.job)" class="cursor-help">
                                        {{ jobLabel(job.job) }}<span v-if="job.params && job.params.force" class="text-red-500"> force</span>
                                    </td>
                                    <td><span :class="statusClass(job.status)" class="px-1.5 rounded">{{ statusLabel(job.status) }}</span></td>
                                    <td :title="job.status === 'success' ? '' : stepDescription(job.currentStep)"
                                        :class="{'cursor-help': job.status !== 'success' && job.currentStep}">
                                        {{ job.status === 'success' ? '—' : stepLabel(job.currentStep) }}
                                    </td>
                                    <td class="whitespace-normal min-w-[240px]">
                                        {{ job.message }}
                                        <span v-if="job.result && job.result.nameCount !== undefined" class="text-gray-500">
                                            學名 {{ job.result.nameCount }}、物種 {{ job.result.taxonCount }}、臺灣存在 {{ job.result.taiwanSpeciesCount }} 種
                                        </span>
                                    </td>
                                    <td>{{ job.triggerBy === 'cron' ? '排程' : '手動' }}</td>
                                    <td>{{ job.startedAt }}</td>
                                    <td>{{ job.finishedAt }}</td>
                                    <td>
                                        <span v-if="job.notifiedAt" :title="job.notifiedAt">已寄</span>
                                        <span v-else-if="job.notifyError" class="text-red-500 cursor-help" :title="job.notifyError">寄送失敗</span>
                                    </td>
                                    <td>
                                        <!-- 按鈕包在 div 裡：td 本身改成 flex 會讓這格的格線斷掉 -->
                                        <div class="flex gap-1">
                                            <button class="button is-small" v-on:click="toggleLog(job)">{{ openLogId === job.id ? '收合 log' : 'log' }}</button>
                                            <button v-if="job.exportPath && job.status !== 'running'" class="button is-small"
                                                    :disabled="downloadingId === job.id" v-on:click="onDownload(job)">
                                                {{ job.status === 'success' ? '下載版本名錄檔' : '下載 issue CSV' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="openLogId === job.id" :key="`log-${job.id}`">
                                    <td colspan="10" class="bg-gray-50">
                                        <pre class="text-xs whitespace-pre-wrap max-h-[400px] overflow-auto">{{ logText || '（沒有 log）' }}</pre>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="!jobs.length">
                                <td colspan="10" class="text-center text-gray-400">{{ loadError || '沒有執行紀錄' }}</td>
                            </tr>
                            <tr v-if="total > 0">
                                <td class="text-center has-text-weight-bold has-text-grey" colspan="10">
                                    <pagination :key="`${jobFilter}-${pageKey}`"
                                                :current-page="currentPage"
                                                :per-page="perPage"
                                                :total="total"
                                                v-on:change="onChangePage"/>
                                    <span class="caption" v-text="$t('common.totalRowsNumber', { total })"></span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, inject, ref, computed, onMounted, onBeforeUnmount } from '@vue/composition-api';
import Cookies from 'js-cookie';
import LoadingSection from '../../components/LoadingSection.vue';
import Pagination from '../../components/Pagination.vue';
import { openNotify } from '../../utils';

const STATUS = {
    queued: ['排隊中', 'bg-gray-200 text-gray-700'],
    running: ['執行中', 'bg-blue-100 text-blue-700'],
    success: ['完成', 'bg-green-100 text-green-700'],
    blocked: ['已中止', 'bg-yellow-100 text-yellow-800'],
    failed: ['失敗', 'bg-red-100 text-red-700'],
};

export default defineComponent({
    components: { LoadingSection, Pagination },
    setup() {
        const axios: any = inject('axios');

        const jobs = ref<any[]>([]);
        const activeJob = ref<any>(null);
        // 任務與步驟的名稱、說明由後端提供（與通知信同一份）
        const labels = ref<any>({ jobs: [], steps: [] });
        const isLoading = ref(false);
        const loadError = ref('');
        const submitting = ref(false);
        const force = ref(false);
        const openLogId = ref<number | null>(null);
        const logText = ref('');
        const downloadingId = ref<number | null>(null);

        const jobFilter = ref('');
        const currentPage = ref(1);
        const perPage = 20;
        const total = ref(0);
        const pageKey = ref(0);   // 改篩選時讓分頁元件回到第 1 頁

        const isBusy = computed(() => !!activeJob.value);
        const find = (list, key) => (list || []).find((x) => x.key === key);

        let timer: any = null;
        const clearTimer = () => { if (timer) { clearInterval(timer); timer = null; } };

        const loadLog = (id) => axios.get(`/update-service/jobs/${id}/log`, { params: { tail: 300 } })
            .then(({ data }) => { if (openLogId.value === id) logText.value = data.log; })
            .catch(() => { logText.value = '（無法取得 log）'; });

        const loadJobs = () => {
            isLoading.value = true;
            return axios.get('/update-service/jobs', {
                params: { page: currentPage.value, per_page: perPage, job: jobFilter.value || undefined },
            })
                .then(({ data }) => {
                    jobs.value = data.jobs || [];
                    total.value = data.total || 0;
                    activeJob.value = data.active || null;
                    if (data.labels) labels.value = data.labels;
                    loadError.value = '';
                    // 有任務在跑：每 10 秒更新；展開中的 log 一起更新
                    if (isBusy.value && !timer) timer = setInterval(loadJobs, 10000);
                    if (!isBusy.value) clearTimer();
                    if (activeJob.value && openLogId.value === activeJob.value.id) loadLog(openLogId.value);
                })
                .catch(({ message }) => { loadError.value = message || '無法取得執行紀錄'; clearTimer(); })
                .finally(() => { isLoading.value = false; });
        };

        const onFilter = () => {
            currentPage.value = 1;
            pageKey.value += 1;
            openLogId.value = null;
            loadJobs();
        };

        const onChangePage = (p) => {
            if (p === currentPage.value) return;
            currentPage.value = p;
            openLogId.value = null;
            loadJobs();
        };

        const jobLabel = (j) => (find(labels.value.jobs, j) || {}).label || j;

        const onStart = (job) => {
            const label = jobLabel(job);
            const forceText = job === 'release' && force.value ? '（force：略過 high issue 把關）' : '';
            if (!window.confirm(`確定要執行「${label}」${forceText}？`)) return;
            submitting.value = true;
            const body: any = { job };
            if (job === 'release') body.force = force.value;
            axios.post('/update-service/jobs', body)
                .then(({ data }) => {
                    openNotify(`已排入 ${label}（job #${data.id}）`);
                    force.value = false;
                    return loadJobs();
                })
                .catch(({ status, message }) => {
                    if (status !== 403 && status !== 422) openNotify(message || '觸發失敗', 'is-danger');
                    loadJobs();
                })
                .finally(() => { submitting.value = false; });
        };

        const toggleLog = (job) => {
            if (openLogId.value === job.id) {
                openLogId.value = null;
                return;
            }
            openLogId.value = job.id;
            logText.value = '讀取中…';
            loadLog(job.id);
        };

        // 下載走 fetch：axios 的 response 攔截器會把內容轉 camelCase，不適合二進位檔
        const onDownload = async (job) => {
            downloadingId.value = job.id;
            try {
                const res = await fetch(`/api/update-service/jobs/${job.id}/export`, {
                    headers: { Authorization: `Bearer ${Cookies.get('taicol_tk')}`, 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    openNotify(res.status === 404 ? '此 job 沒有匯出檔' : '下載失敗', 'is-danger');
                    return;
                }
                const match = /filename="?([^";]+)"?/.exec(res.headers.get('Content-Disposition') || '');
                const url = window.URL.createObjectURL(await res.blob());
                const a = document.createElement('a');
                a.href = url;
                a.download = match ? match[1] : `job_${job.id}.zip`;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                openNotify('下載失敗', 'is-danger');
            } finally {
                downloadingId.value = null;
            }
        };

        onMounted(loadJobs);
        onBeforeUnmount(clearTimer);

        return {
            jobs, activeJob, labels, isLoading, loadError, submitting, force, openLogId, logText, downloadingId,
            jobFilter, currentPage, perPage, total, pageKey, isBusy,
            loadJobs, onFilter, onChangePage, onStart, toggleLog, onDownload,
            jobLabel,
            jobDescription: (j) => (find(labels.value.jobs, j) || {}).description || '',
            stepLabel: (s) => (s ? (find(labels.value.steps, s) || {}).label || s : ''),
            stepDescription: (s) => (find(labels.value.steps, s) || {}).description || '',
            statusLabel: (s) => (STATUS[s] || [s])[0],
            statusClass: (s) => (STATUS[s] || ['', ''])[1],
        };
    },
});
</script>
