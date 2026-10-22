<template>
    <div class="w-full h-full overflow-y-auto">
        <div class="container flex flex-col min-h-full pb-6">
            <div class="bg-white">
                <div class="py-3 flex items-center">
                    <p class="ml-4 font-bold text-3xl inline">{{ $t('header.adminMenu.updateService') }}</p>
                </div>

                <!-- 觸發 -->
                <div class="px-4 mb-4 flex flex-wrap items-center gap-4">
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('taxon')">執行名錄更新</button>
                    <span class="text-gray-300">|</span>
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('release')">產生版本名錄</button>
                    <label class="flex items-center gap-1 text-sm">
                        <input type="checkbox" v-model="force" :disabled="isBusy || submitting">
                        force（略過 high issue 把關）
                    </label>
                    <span v-if="activeJob" class="text-sm text-gray-500">
                        執行中：{{ jobLabel(activeJob.job) }} #{{ activeJob.id }}（{{ activeJob.currentStep || '排隊中' }}）
                    </span>
                </div>
                <ul class="px-4 mb-4 text-sm text-gray-600">
                    <li>名錄更新：usage check → 文獻 → 學名 → 名錄（issue 不擋更新）。usage check 有未勾的問題時會停下。</li>
                    <li>版本名錄：usage check → 全表檢核 → 產生三個下載檔。有未處理的 high issue 時會停下，可下載 high_issues.csv；確認可略過時勾 force 再執行。</li>
                    <li>停下（blocked）或失敗（failed）時會寄信給所有管理員。</li>
                </ul>

                <!-- 執行紀錄 -->
                <div class="px-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-bold">執行紀錄</p>
                        <button :disabled="isLoading" class="button is-small" v-on:click="loadJobs">重新整理</button>
                    </div>
                    <div v-if="isLoading && !jobs.length" class="border">
                        <loading-section></loading-section>
                    </div>
                    <div v-else class="overflow-auto shadow-sm border">
                        <table class="table text-[13px] has-text-grey whitespace-nowrap w-full">
                            <thead class="font-bold sticky top-0 bg-white z-10">
                            <tr>
                                <th>#</th>
                                <th>job</th>
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
                                    <td>{{ jobLabel(job.job) }}<span v-if="job.params && job.params.force" class="text-red-500"> force</span></td>
                                    <td><span :class="statusClass(job.status)" class="px-1.5 rounded">{{ statusLabel(job.status) }}</span></td>
                                    <td>{{ job.currentStep }}</td>
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
                                        <span v-else-if="job.notifyError" class="text-red-500" :title="job.notifyError">寄送失敗</span>
                                    </td>
                                    <td class="flex gap-1">
                                        <button class="button is-small" v-on:click="toggleLog(job)">{{ openLogId === job.id ? '收合 log' : 'log' }}</button>
                                        <button v-if="job.exportPath && job.status !== 'running'" class="button is-small"
                                                :disabled="downloadingId === job.id" v-on:click="onDownload(job)">
                                            {{ job.status === 'success' ? '下載檔' : '下載 CSV' }}
                                        </button>
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
import { openNotify } from '../../utils';

const JOB_LABELS = {
    base: '每日文獻／學名更新',
    taxon: '名錄更新',
    release: '版本名錄',
    usage_check: 'usage check',
    ref: '文獻更新',
    name: '學名更新',
    taxon_tool: '名錄更新（管理工具）',
};
const STATUS = {
    queued: ['排隊中', 'bg-gray-200 text-gray-700'],
    running: ['執行中', 'bg-blue-100 text-blue-700'],
    success: ['完成', 'bg-green-100 text-green-700'],
    blocked: ['已中止', 'bg-yellow-100 text-yellow-800'],
    failed: ['失敗', 'bg-red-100 text-red-700'],
};

export default defineComponent({
    components: { LoadingSection },
    setup() {
        const axios: any = inject('axios');

        const jobs = ref<any[]>([]);
        const isLoading = ref(false);
        const loadError = ref('');
        const submitting = ref(false);
        const force = ref(false);
        const openLogId = ref<number | null>(null);
        const logText = ref('');
        const downloadingId = ref<number | null>(null);

        const activeJob = computed(() => jobs.value.find((j) => ['queued', 'running'].includes(j.status)));
        const isBusy = computed(() => !!activeJob.value);

        let timer: any = null;
        const clearTimer = () => { if (timer) { clearInterval(timer); timer = null; } };

        const loadLog = (id) => axios.get(`/update-service/jobs/${id}/log`, { params: { tail: 300 } })
            .then(({ data }) => { if (openLogId.value === id) logText.value = data.log; })
            .catch(() => { logText.value = '（無法取得 log）'; });

        const loadJobs = () => {
            isLoading.value = true;
            return axios.get('/update-service/jobs', { params: { limit: 30 } })
                .then(({ data }) => {
                    jobs.value = data.jobs || [];
                    loadError.value = '';
                    // 有任務在跑：每 10 秒更新；展開中的 log 一起更新
                    if (isBusy.value && !timer) timer = setInterval(loadJobs, 10000);
                    if (!isBusy.value) clearTimer();
                    if (openLogId.value && jobs.value.some((j) => j.id === openLogId.value
                        && ['queued', 'running'].includes(j.status))) loadLog(openLogId.value);
                })
                .catch(({ message }) => { loadError.value = message || '無法取得執行紀錄'; clearTimer(); })
                .finally(() => { isLoading.value = false; });
        };

        const onStart = (job) => {
            const label = JOB_LABELS[job];
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
            jobs, isLoading, loadError, submitting, force, openLogId, logText, downloadingId,
            activeJob, isBusy,
            loadJobs, onStart, toggleLog, onDownload,
            jobLabel: (j) => JOB_LABELS[j] || j,
            statusLabel: (s) => (STATUS[s] || [s])[0],
            statusClass: (s) => (STATUS[s] || ['', ''])[1],
        };
    },
});
</script>
