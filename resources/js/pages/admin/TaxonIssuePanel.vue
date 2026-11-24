<!-- 名錄更新頁「版本名錄」分頁：版本名錄檢核結果（api_taxon_issue）依 issue_code 統計，可下載錯誤資料、批次標記解決／忽略 -->
<template>
    <div class="px-4 mb-6">
        <div class="flex items-center justify-between mb-2">
            <p class="font-bold">檢核項目（未解決、未忽略）</p>
            <button :disabled="isLoading" class="button is-small" v-on:click="load">重新整理</button>
        </div>
        <div v-if="isLoading && !issues.length" class="border">
            <loading-section></loading-section>
        </div>
        <div v-else class="overflow-auto shadow-sm border">
            <table class="table text-[13px] w-full">
                <thead class="font-bold bg-white">
                <tr>
                    <th>錯誤類型</th>
                    <th class="text-right">錯誤資料筆數</th>
                    <th>下載</th>
                    <th>
                        解決名單
                        <i class="fas fa-info-circle text-gray-400 cursor-help" :title="idHint"></i>
                    </th>
                    <th>
                        忽略名單
                        <i class="fas fa-info-circle text-gray-400 cursor-help" :title="idHint"></i>
                    </th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="issue in issues" :key="`${issue.issueCode}-${issue.severity}`">
                    <td class="align-middle">
                        <span :class="severityClass(issue.severity)" :title="issue.severity"
                              class="inline-block px-2 py-0.5 rounded border">{{ issue.issueCode }}</span>
                    </td>
                    <td class="text-right align-middle">{{ issue.count.toLocaleString() }}</td>
                    <td class="align-middle">
                        <button :disabled="downloading === issue.issueCode" class="button is-small"
                                v-on:click="onDownload(issue)">下載錯誤資料</button>
                    </td>
                    <td class="align-middle">
                        <textarea v-model="inputs[issue.issueCode].solve" :disabled="savingCode === issue.issueCode"
                                  class="textarea is-small" rows="1" placeholder="例：12,15,30"></textarea>
                    </td>
                    <td class="align-middle">
                        <textarea v-model="inputs[issue.issueCode].ignore" :disabled="savingCode === issue.issueCode"
                                  class="textarea is-small" rows="1" placeholder="例：12,15,30"></textarea>
                    </td>
                    <td class="align-middle">
                        <button :disabled="!!savingCode || !hasInput(issue)" class="button is-small"
                                :class="{ 'is-loading': savingCode === issue.issueCode }"
                                v-on:click="onUpdate(issue)">更新</button>
                    </td>
                </tr>
                <tr v-if="!issues.length">
                    <td colspan="6" class="text-center text-gray-400">{{ loadError || '沒有未處理的檢核項目' }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-500 mt-1">
            顏色：<span class="text-red-700">high</span>／<span class="text-yellow-700">medium</span>／<span class="text-green-700">low</span>。
            筆數為目前資料庫的狀態，標記後會即時更新；上方「上一次版本名錄檢核」為當次檢核時的筆數。
        </p>
    </div>
</template>
<script>
import Cookies from 'js-cookie';
import { openNotify } from '../../utils';
import LoadingSection from '../../components/LoadingSection.vue';

const SEVERITY_CLASS = {
    high: 'bg-red-100 border-red-400 text-red-700',
    medium: 'bg-yellow-50 border-yellow-500 text-yellow-700',
    low: 'bg-green-50 border-green-600 text-green-800',
};

export default {
    components: { LoadingSection },
    data() {
        return {
            issues: [],
            inputs: {},          // issueCode => { solve, ignore }
            isLoading: false,
            loadError: '',
            downloading: null,
            savingCode: null,
            idHint: '輸入 api_taxon_issue 的 id，多個時以「,」分隔',
        };
    },
    mounted() {
        this.load();
    },
    methods: {
        severityClass(s) {
            return SEVERITY_CLASS[s] || 'bg-gray-100 border-gray-300 text-gray-700';
        },
        load() {
            this.isLoading = true;
            return this.axios.get('/taxon-issues')
                .then(({ data }) => {
                    this.issues = data.issues || [];
                    // 保留已輸入的內容，新出現的 issue_code 補空白欄位
                    const inputs = {};
                    this.issues.forEach(({ issueCode }) => {
                        inputs[issueCode] = this.inputs[issueCode] || { solve: '', ignore: '' };
                    });
                    this.inputs = inputs;
                    this.loadError = '';
                })
                .catch(({ message }) => { this.loadError = message || '無法取得檢核項目'; })
                .finally(() => { this.isLoading = false; });
        },
        hasInput(issue) {
            const v = this.inputs[issue.issueCode];
            return !!v && !!(v.solve.trim() || v.ignore.trim());
        },
        onUpdate(issue) {
            const { solve, ignore } = this.inputs[issue.issueCode];
            const lines = [`確定要更新「${issue.issueCode}」？`];
            if (solve.trim()) lines.push(`解決：${solve.trim()}`);
            if (ignore.trim()) lines.push(`忽略：${ignore.trim()}`);
            if (!window.confirm(lines.join('\n'))) return;

            this.savingCode = issue.issueCode;
            this.axios.post('/taxon-issues/update', {
                issueCode: issue.issueCode,
                solveIds: solve,
                ignoreIds: ignore,
            })
                .then(({ data }) => {
                    let text = `已更新：解決 ${data.solved} 筆、忽略 ${data.ignored} 筆`;
                    if (data.skipped.length) {
                        text += `；不屬於 ${issue.issueCode} 或不存在、未更新：${data.skipped.join(', ')}`;
                    }
                    openNotify(text, data.skipped.length ? 'is-warning' : undefined);
                    this.inputs[issue.issueCode] = { solve: '', ignore: '' };
                    return this.load();
                })
                .catch(({ status, message }) => {
                    if (status === 422 && message) openNotify(message, 'is-danger');
                    else if (status !== 403) openNotify(message || '更新失敗', 'is-danger');
                })
                .finally(() => { this.savingCode = null; });
        },
        // 下載走 fetch：axios 的 response 攔截器會把內容轉 camelCase，不適合檔案
        async onDownload(issue) {
            this.downloading = issue.issueCode;
            try {
                const res = await fetch(`/api/taxon-issues/export?issue_code=${encodeURIComponent(issue.issueCode)}`, {
                    headers: { Authorization: `Bearer ${Cookies.get('taicol_tk')}`, 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    openNotify('下載失敗', 'is-danger');
                    return;
                }
                const match = /filename="?([^";]+)"?/.exec(res.headers.get('Content-Disposition') || '');
                const url = window.URL.createObjectURL(await res.blob());
                const a = document.createElement('a');
                a.href = url;
                a.download = match ? match[1] : `taxon_issue_${issue.issueCode}.csv`;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                openNotify('下載失敗', 'is-danger');
            } finally {
                this.downloading = null;
            }
        },
    },
};
</script>
