<template>
    <div class="w-full h-full overflow-y-auto">
        <div class="container flex flex-col min-h-full pb-6">
            <div class="bg-white">
                <div class="py-3 flex items-center">
                    <p class="ml-4 font-bold text-3xl inline">{{ $t('header.adminMenu.updateService') }}</p>
                </div>

                <!-- 分頁：名錄更新／學名使用檢查 -->
                <div class="px-4 mb-4 flex border-b">
                    <button v-for="t in TABS" :key="t.key" v-on:click="setTab(t.key)"
                            :class="tab === t.key ? 'border-blue-600 text-blue-700 font-bold' : 'border-transparent text-gray-500'"
                            class="px-4 py-2 -mb-px border-b-2">
                        {{ t.key === 'usage-check' ? $t('header.adminMenu.usageCheck') : t.label }}
                        <span v-if="t.key === 'usage-check' && uncheckedCount" class="ml-1 px-1.5 rounded bg-red-100 text-red-700 text-xs">{{ uncheckedCount }}</span>
                    </button>
                </div>

                <usage-check-tab v-if="tab === 'usage-check'" v-on:changed="loadStatus"/>

                <template v-else>
                <!-- 目前狀態 -->
                <div class="px-4 mb-4">
                    <div class="grid md:grid-cols-3 gap-3 text-sm">
                        <div class="border rounded-md p-3">
                            <p class="text-gray-500 mb-1">最後一次增量更新成功</p>
                            <p v-if="status.incremental" class="font-bold">{{ status.incremental.at }}
                                <span class="font-normal text-gray-500">（{{ jobLabel(status.incremental.job) }} #{{ status.incremental.jobId }}）</span></p>
                            <p v-else class="text-gray-400">沒有紀錄</p>
                        </div>
                        <div class="border rounded-md p-3">
                            <p class="text-gray-500 mb-1">上一次版本名錄檢核</p>
                            <template v-if="status.audit">
                                <p><span class="font-bold">{{ status.audit.finishedAt }}</span>
                                    <span class="text-gray-500">（{{ jobLabel(status.audit.job) }} #{{ status.audit.jobId }}）</span></p>
                                <p>{{ auditText(status.audit) }}</p>
                                <p v-if="status.audit.stale" class="text-yellow-700">之後又跑過增量更新，檢核結果可能已過時</p>
                            </template>
                            <p v-else class="text-gray-400">還沒有檢核紀錄</p>
                        </div>
                        <div class="border rounded-md p-3">
                            <p class="text-gray-500 mb-1">學名使用檢查未確認</p>
                            <p v-if="status.usageCheck">
                                <a href="#" v-on:click.prevent="setTab('usage-check')"
                                   :class="status.usageCheck.unchecked ? 'text-red-600' : 'text-green-700'"
                                   class="font-bold hover:underline">{{ status.usageCheck.unchecked }} 筆</a>
                                <span class="text-gray-500">（上次檢查 {{ status.usageCheck.lastCheckedAt || '—' }}）</span>
                            </p>
                            <p v-if="status.usageCheck && status.usageCheck.unchecked" class="text-red-600">
                                未確認時，增量更新與產生版本名錄會停在學名使用檢查</p>
                        </div>
                    </div>
                    <p v-if="status.serviceError" class="text-red-600 text-sm mt-2">{{ status.serviceError }}</p>
                </div>

                <!-- 觸發 -->
                <div class="px-4 mb-2 flex flex-wrap items-center gap-3">
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('taxon')">增量更新</button>
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('audit')">版本名錄檢核</button>
                    <button :disabled="isBusy || submitting" class="button" v-on:click="onStart('release')">產生版本名錄</button>
                    <span :class="releaseHint.warn ? 'text-yellow-700' : 'text-gray-500'" class="text-sm">{{ releaseHint.text }}</span>
                </div>
                <div class="px-4 mb-4 text-sm text-gray-500 min-h-[1.25rem]">
                    <span v-if="activeJob">
                        執行中：{{ jobLabel(activeJob.job) }} #{{ activeJob.id }}（{{ activeJob.currentStep ? stepLabel(activeJob.currentStep) : '排隊中' }}）
                    </span>
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
                                            <template v-if="job.job === 'publish'">{{ job.result.month }}：</template>
                                            學名 {{ job.result.nameCount }}、物種 {{ job.result.taxonCount }}、臺灣存在 {{ job.result.taiwanSpeciesCount }} 種
                                            <template v-if="job.job === 'release' && job.result.fileSizes">（{{ formatSizes(job.result.fileSizes) }}）</template>
                                        </span>
                                        <span v-if="job.result && job.result.audit"
                                              :class="job.result.audit.blockingHigh ? 'text-yellow-700' : 'text-gray-500'">
                                            檢核：{{ auditText(job.result.audit) }}
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
                                                {{ exportLabel(job) }}
                                            </button>
                                            <button v-if="job.job === 'release' && job.status === 'success'" class="button is-small"
                                                    :disabled="isBusy || submitting" v-on:click="openPublish(job)">發佈到網站</button>
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
                </template>
            </div>
        </div>

        <!-- 發佈到網站：先看統計與這次檢核的 high 筆數再確認（high issue 的人工把關在這一步） -->
        <div v-if="publishModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-md shadow-lg w-full max-w-2xl p-6 text-sm">
                <p class="font-bold text-xl mb-3">發佈版本名錄到網站</p>
                <div v-if="!preview" class="py-6"><loading-section></loading-section></div>
                <template v-else>
                    <p class="mb-3">
                        版本名錄 job #{{ preview.releaseJobId }}　月份 <b>{{ preview.month }}</b>（檔名日期 {{ preview.date }}）
                        <span v-if="preview.target">　發佈到 <b>{{ preview.target }}</b></span>
                    </p>
                    <table class="w-full border-collapse mb-3">
                        <thead><tr class="border-b bg-gray-100"><th class="p-1.5 text-left">檔案</th><th class="p-1.5 text-right">大小</th></tr></thead>
                        <tbody>
                        <tr v-for="f in preview.files" :key="f.kind" class="border-b">
                            <td class="p-1.5">{{ f.name }}</td><td class="p-1.5 text-right">{{ formatBytes(f.size) }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <table class="w-full border-collapse mb-3">
                        <thead>
                        <tr class="border-b bg-gray-100">
                            <th class="p-1.5 text-left">統計</th>
                            <th class="p-1.5 text-right">本次</th>
                            <th class="p-1.5 text-right">上一次發佈<span v-if="preview.previous">（{{ preview.previous.month }}）</span></th>
                            <th class="p-1.5 text-right">差異</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="row in statRows" :key="row.key" class="border-b">
                            <td class="p-1.5">{{ row.label }}</td>
                            <td class="p-1.5 text-right font-bold">{{ formatNumber(row.current) }}</td>
                            <td class="p-1.5 text-right">{{ row.previous === null ? '—' : formatNumber(row.previous) }}</td>
                            <td class="p-1.5 text-right">{{ row.diff }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <p v-if="!preview.previous" class="text-gray-500 mb-2">沒有上一次發佈的紀錄可以比較。</p>
                    <div v-if="preview.audit" :class="preview.audit.blockingHigh ? 'bg-red-50 border-red-300 text-red-700' : 'bg-green-50 border-green-300 text-green-800'"
                         class="border rounded-md p-3 mb-3">
                        <p>這次檢核（{{ preview.audit.finishedAt }}）：<b>{{ auditText(preview.audit) }}</b></p>
                        <p v-if="preview.audit.blockingHigh" class="mt-1">
                            有未 ignore 的 high issue。請先確認 taxon_issues.csv（blocks_release=1 的列）可以接受，再發佈。
                            <button class="button is-small ml-1" :disabled="downloadingId === publishFor.id"
                                    v-on:click="onDownload(publishFor)">下載版本名錄檔（含 taxon_issues.csv）</button>
                        </p>
                    </div>
                    <p v-else class="text-gray-500 mb-2">這次版本名錄沒有檢核筆數紀錄（修訂前產生的）。</p>
                    <p v-if="preview.alreadyPublishedJobId" class="text-red-600 mb-2">這次版本名錄已發佈過（job #{{ preview.alreadyPublishedJobId }}），不能重複發佈。</p>
                    <p class="text-gray-600 mb-4">網站已有同月份的下載紀錄時不會覆蓋，任務會停下並寄信通知。</p>
                </template>
                <div class="flex justify-end gap-2">
                    <button class="button" :disabled="submitting" v-on:click="closePublish">取消</button>
                    <button class="button is-primary" :disabled="!preview || !!preview.alreadyPublishedJobId || submitting"
                            v-on:click="onPublish">確認發佈</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, inject, ref, computed, watch, onMounted, onBeforeUnmount } from '@vue/composition-api';
import Cookies from 'js-cookie';
import LoadingSection from '../../components/LoadingSection.vue';
import Pagination from '../../components/Pagination.vue';
import { openNotify } from '../../utils';
import UsageCheckTab from './UsageCheckTab.vue';

const TABS = [{ key: 'update', label: '名錄更新' }, { key: 'usage-check', label: '' }];

const STATUS = {
    queued: ['排隊中', 'bg-gray-200 text-gray-700'],
    running: ['執行中', 'bg-blue-100 text-blue-700'],
    success: ['完成', 'bg-green-100 text-green-700'],
    blocked: ['已中止', 'bg-yellow-100 text-yellow-800'],
    failed: ['失敗', 'bg-red-100 text-red-700'],
};

export default defineComponent({
    components: { LoadingSection, Pagination, UsageCheckTab },
    setup(props, { root }) {
        const axios: any = inject('axios');

        // 分頁：網址 ?tab=usage-check（舊的 /admin/usage-check 也轉到這裡）
        const tabOf = (q) => (q === 'usage-check' ? 'usage-check' : 'update');
        const tab = ref(tabOf(root.$route.query.tab));
        const setTab = (t) => {
            if (t === tab.value) return;
            tab.value = t;
            root.$router.replace({ query: { ...root.$route.query, tab: t === 'update' ? undefined : t } });
        };
        watch(() => root.$route.query.tab, (q) => { tab.value = tabOf(q); });

        // 目前狀態：最後一次增量更新、上一次檢核、學名使用檢查未確認筆數
        const status = ref<any>({});
        const uncheckedCount = computed(() => (status.value.usageCheck || {}).unchecked || 0);
        const loadStatus = () => axios.get('/update-service/status')
            .then(({ data }) => { status.value = data || {}; })
            .catch(() => { status.value = { serviceError: '無法取得目前狀態' }; });
        const auditText = (a) => (a
            ? `high ${a.high} 筆（未 ignore、原本會擋版本名錄 ${a.blockingHigh} 筆）、medium ${a.medium}、low ${a.low}`
            : '');
        // 「產生版本名錄」旁與確認視窗的提醒：沒跑過檢核、或檢核後又跑過增量更新
        const releaseHint = computed(() => {
            const a = status.value.audit;
            if (!a) return { warn: true, text: '還沒有版本名錄檢核紀錄，建議先跑「版本名錄檢核」並處理 high issue' };
            const base = `上一次檢核 ${a.finishedAt}：high ${a.high} 筆（未 ignore ${a.blockingHigh} 筆）`;
            if (a.stale) return { warn: true, text: `${base}；之後又跑過增量更新，結果可能已過時` };
            return { warn: !!a.blockingHigh, text: base };
        });

        const jobs = ref<any[]>([]);
        const activeJob = ref<any>(null);
        // 任務與步驟的名稱、說明由後端提供（與通知信同一份）
        const labels = ref<any>({ jobs: [], steps: [] });
        const isLoading = ref(false);
        const loadError = ref('');
        const submitting = ref(false);
        const openLogId = ref<number | null>(null);
        const logText = ref('');
        const downloadingId = ref<number | null>(null);

        const jobFilter = ref('');
        const currentPage = ref(1);
        const perPage = 20;
        const total = ref(0);
        const pageKey = ref(0);   // 改篩選時讓分頁元件回到第 1 頁

        const isBusy = computed(() => !!activeJob.value);

        // 發佈到網站（Step 10）
        const publishModal = ref(false);
        const publishFor = ref<any>(null);
        const preview = ref<any>(null);
        const STAT_LABELS = [['nameCount', '學名筆數'], ['taxonCount', '物種筆數'], ['taiwanSpeciesCount', '臺灣存在種數']];
        const formatNumber = (n) => (typeof n === 'number' ? n.toLocaleString() : '—');
        const formatBytes = (n) => (typeof n === 'number' ? `${(n / 1024 / 1024).toFixed(1)} MB` : '—');
        const formatSizes = (sizes) => ['name', 'taxon', 'namecode']
            .filter((k) => sizes[k] !== undefined).map((k) => `${k} ${formatBytes(sizes[k])}`).join('、');
        const statRows = computed(() => {
            if (!preview.value) return [];
            const cur = preview.value.stats || {};
            const prev = preview.value.previous;
            return STAT_LABELS.map(([key, label]) => {
                const p = prev ? prev[key] : null;
                const d = typeof p === 'number' && typeof cur[key] === 'number' ? cur[key] - p : null;
                return {
                    key, label, current: cur[key], previous: typeof p === 'number' ? p : null,
                    diff: d === null ? '—' : `${d > 0 ? '+' : ''}${d.toLocaleString()}`,
                };
            });
        });
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
                    const wasBusy = isBusy.value;
                    jobs.value = data.jobs || [];
                    total.value = data.total || 0;
                    activeJob.value = data.active || null;
                    if (wasBusy && !isBusy.value) loadStatus(); // 任務剛結束：更新目前狀態
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

        const START_NOTES = {
            taxon: '步驟：文獻 → 學名 → 學名使用檢查 → 名錄。',
            audit: '只跑全表檢核，結果寫入 api_taxon_issue 並匯出 taxon_issues.csv；有 high issue 也不會擋下。',
            release: '步驟：增量更新 → 全表檢核（不擋）→ 產生三個下載檔。high issue 在「發佈到網站」時再確認。',
        };
        const onStart = (job) => {
            const label = jobLabel(job);
            const lines = [`確定要執行「${label}」？`, START_NOTES[job]];
            if (job === 'release') lines.push('', releaseHint.value.text);
            if (!window.confirm(lines.join('\n'))) return;
            submitting.value = true;
            axios.post('/update-service/jobs', { job })
                .then(({ data }) => {
                    openNotify(`已排入 ${label}（job #${data.id}）`);
                    return loadJobs();
                })
                .catch(({ status, message }) => {
                    if (status !== 403 && status !== 422) openNotify(message || '觸發失敗', 'is-danger');
                    loadJobs();
                })
                .finally(() => { submitting.value = false; });
        };

        const openPublish = (job) => {
            publishFor.value = job;
            preview.value = null;
            publishModal.value = true;
            axios.get(`/update-service/jobs/${job.id}/publish-preview`)
                .then(({ data }) => { preview.value = data; })
                .catch(({ message }) => {
                    publishModal.value = false;
                    openNotify(message || '無法取得發佈資訊', 'is-danger');
                });
        };

        const closePublish = () => { publishModal.value = false; };

        const onPublish = () => {
            submitting.value = true;
            axios.post('/update-service/jobs', { job: 'publish', release_job_id: publishFor.value.id })
                .then(({ data }) => {
                    openNotify(`已排入版本名錄發佈（job #${data.id}）`);
                    publishModal.value = false;
                    return loadJobs();
                })
                .catch(({ status, message }) => {
                    if (status !== 403 && status !== 422) openNotify(message || '發佈失敗', 'is-danger');
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

        const exportLabel = (job) => {
            if (job.status !== 'success') return '下載 issue CSV';
            return job.job === 'audit' ? '下載檢核結果' : '下載版本名錄檔';
        };

        onMounted(() => { loadJobs(); loadStatus(); });
        onBeforeUnmount(clearTimer);

        return {
            TABS, tab, setTab, status, uncheckedCount, loadStatus, auditText, releaseHint, exportLabel, publishFor,
            jobs, activeJob, labels, isLoading, loadError, submitting, openLogId, logText, downloadingId,
            jobFilter, currentPage, perPage, total, pageKey, isBusy,
            loadJobs, onFilter, onChangePage, onStart, toggleLog, onDownload,
            publishModal, preview, statRows, openPublish, closePublish, onPublish, formatNumber, formatBytes, formatSizes,
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
