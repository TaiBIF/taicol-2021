<template>
    <div class="w-full h-full overflow-y-auto">
        <div class="container flex flex-col min-h-full pb-6">
            <div class="bg-white">

                <div class="py-3 flex items-center">
                    <p class="ml-4 font-bold text-3xl inline">{{ $t('header.adminMenu.usageCheck') }}</p>
                </div>
                <div class="flex items-center px-4 mb-4 gap-3">
                    <button :disabled="isLoading" class="button" v-on:click="onSubmit">開始檢查</button>
                    <p>上次檢查時間：{{ lastUpdated }}</p>
                    <p v-if="checkJobId" class="text-gray-500">檢查中（job #{{ checkJobId }}），完成後會自動更新</p>
                </div>

                <div class="px-4 mb-4">
                    <details class="bg-gray-50 border rounded-md p-3 text-sm">
                        <summary class="font-bold cursor-pointer text-gray-700">錯誤類型對照表</summary>
                        <div class="mt-2 max-h-[200px] overflow-y-auto border-t pt-2">
                            <table class="w-full text-xs text-left text-gray-600 border-collapse">
                                <thead>
                                    <tr class="border-b bg-gray-100">
                                        <th class="p-1.5 w-16 text-center">代號</th>
                                        <th class="p-1.5">說明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(desc, code) in errorTypeMap" :key="code" class="border-b hover:bg-gray-100">
                                        <td class="p-1.5 font-bold text-center border-r">{{ code }}</td>
                                        <td class="p-1.5">{{ desc }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>

                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p>尚未確認的 usage 👇（可直接勾選 is_checked 並更新表格）
                            <span class="ml-2 text-xs text-gray-500">灰底「已不再檢出」：上次檢查已沒有這個問題，確認後勾選即可</span>
                        </p>
                        <button :disabled="isLoading" class="button" v-on:click="saveChecked">確認完畢更新表格</button>
                    </div>
                    <div v-if="isLoading" class="max-h-[350px] border">
                        <loading-section></loading-section>
                    </div>
                    <div v-else class="max-h-[350px] overflow-auto shadow-sm border">
                        <table class="table text-[13px] has-text-grey whitespace-nowrap">
                            <thead class="font-bold sticky top-0 bg-white z-10">
                            <tr>
                                <th class="has-text-grey">Check ID</th>
                                <th class="has-text-grey">錯誤類型</th>
                                <th class="has-text-grey">reference_usage_id</th>
                                <th class="has-text-grey">accepted_taxon_name_id</th>
                                <th class="has-text-grey">accepted_name</th>
                                <th class="has-text-grey">taxon_name_id</th>
                                <th class="has-text-grey">name</th>
                                <th class="has-text-grey">reference_id</th>
                                <th class="has-text-grey">group</th>
                                <th class="has-text-grey">autonym_group</th>
                                <th class="has-text-grey">object_group</th>
                                <th class="has-text-grey">更新時間</th>
                                <th class="has-text-grey text-center">is_checked</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="usage in usages" :key="usage.id"
                                :class="{'bg-gray-100 text-gray-400': usage.isStale == 1}">
                                <td>
                                    {{ usage.id }}
                                    <span v-if="usage.isStale == 1"
                                          class="ml-1 px-1 rounded text-[11px] bg-gray-300 text-gray-700">已不再檢出</span>
                                </td>
                                <td :title="errorTypeMap[usage.errorType]">
                                    {{ usage.errorType }}
                                </td>
                                <td>{{ display(usage.referenceUsageId) }}</td>
                                <td>
                                    <a v-if="hasValue(usage.acceptedTaxonNameId)" :href="`https://nametool.taicol.tw/zh-tw/taxon-names/${usage.acceptedTaxonNameId}`"
                                       target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">{{ usage.acceptedTaxonNameId }}</a>
                                </td>
                                <td>{{ usage.acceptedName }}</td>
                                <td>
                                    <a v-if="hasValue(usage.taxonNameId)" :href="`https://nametool.taicol.tw/zh-tw/taxon-names/${usage.taxonNameId}`"
                                       target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">{{ usage.taxonNameId }}</a>
                                </td>
                                <td>{{ usage.name }}</td>
                                <td>
                                    <a v-if="hasValue(usage.referenceId)" :href="`https://nametool.taicol.tw/zh-tw/references/${usage.referenceId}`"
                                       target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">{{ usage.referenceId }}</a>
                                </td>
                                <td>{{ usage.group }}</td>
                                <td>{{ display(usage.autonymGroup) }}</td>
                                <td>{{ display(usage.objectGroup) }}</td>
                                <td>{{ usage.updatedAt }}</td>
                                <td class="text-center">
                                    <input type="checkbox"
                                        :checked="usage.isChecked == 1"
                                        :disabled="isLoading"
                                        @change="usage.isChecked = $event.target.checked ? 1 : 0">
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
<script>
import { openNotify } from '../../utils';
import page from '../Page.vue';
import LoadingSection from '../../components/LoadingSection.vue';

export default {
    data() {
        return {
            lastUpdated: null,
            isLoading: false,
            checkJobId: null,
            pollTimer: null,
            usages: [],
            errorTypeMap: { // 2 & 3 合併 , 6 & 9 合併, 5 & 10 合併 
                1: 'fixed usage_id 被刪除',
                2: 'autonym / 同模：同一篇文獻 不同分類群同時出現 accepted和not-acceped 或 多個not-accepted在不同分類群',
                3: 'autonym / 同模：同一篇文獻中有多個not-accepted在不同分類群',
                4: '同模（不包含autonym）：同一篇文獻中多個accepted',
                5: 'accepted_taxon_name_id, taxon_name_id, reference_id 對到多個status 或 usage_id',
                6: '學名在同一篇文獻中 被設定成兩個分類群的同物異名 (不是誤用)',
                7: '同一個分類群有一個以上的接受名',
                8: '同一個分類群裡面沒有任何接受名',
                9: '同一個學名出現在同一篇文獻中的兩個分類群(不同accepted_taxon_name_id) 且不是誤用',
                10: 'reference_id, accepted_taxon_name_id, taxon_name_id 對到多個 usage_id',
                12: '同學名/同模有多個reference_id且無法決定出最新文獻，需補充publish_date'
            }
        };
    },
    mounted() {
        this.getUncheckedUsage();
    },
    beforeDestroy() {
        this.stopPolling();
    },
    methods: {
        // 0 只是避免錯誤的填充值，前端不顯示
        hasValue(v) {
            return v !== null && v !== undefined && v !== '' && Number(v) !== 0;
        },
        display(v) {
            return this.hasValue(v) ? v : '';
        },
        getUncheckedUsage(){
            this.axios.get('/get-unchecked-usage', {
            })
            .then(({data}) => {
                this.lastUpdated = data['lastUpdated'];
                this.usages = data['usages'];
            });
        },
        onSubmit(){
            this.isLoading = true;
            this.axios.get('/update-usage-check', {
            })
            .then(({data}) => {

                // 更新服務：已排入（或已有 usage_check 在跑）→ 輪詢到結束
                if (data['message'] == 'queued' || (data['message'] == 'busy' && data['job'] == 'usage_check')) {
                    this.pollCheckJob(data['jobId']);
                    return;
                }

                this.isLoading = false;

                if (data['message'] == 'busy') {
                    openNotify(`已有其他更新任務執行中（${data['job']} #${data['jobId']}），請稍後再試`, 'is-danger');
                } else if ( data['message'] == 'done'){
                    this.lastUpdated = data['lastUpdated'];
                    openNotify(this.$t('common.checkSuccess'));
                    this.getUncheckedUsage();
                } else {
                    openNotify('發生錯誤', 'is-danger');
                }

            });
        },
        pollCheckJob(jobId) {
            this.checkJobId = jobId;
            this.stopPolling();
            const check = () => {
                this.axios.get(`/update-service/jobs/${jobId}`)
                    .then(({data}) => {
                        if (['queued', 'running'].includes(data.status)) {
                            return;
                        }
                        this.stopPolling();
                        this.checkJobId = null;
                        this.isLoading = false;
                        // blocked＝有未勾的問題，是正常的檢查結果
                        if (data.status == 'success' || data.status == 'blocked') {
                            openNotify(this.$t('common.checkSuccess'));
                        } else {
                            openNotify(`檢查失敗：${data.message || ''}`, 'is-danger');
                        }
                        this.getUncheckedUsage();
                    })
                    .catch(() => {
                        this.stopPolling();
                        this.checkJobId = null;
                        this.isLoading = false;
                        openNotify('無法取得檢查狀態', 'is-danger');
                    });
            };
            this.pollTimer = setInterval(check, 5000);
        },
        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        saveChecked(){
            const ids = this.usages.filter(u => u.isChecked == 1).map(u => u.id);
            if (ids.length === 0) {   // 沒勾任何列就只重抓
                this.getUncheckedUsage();
                return;
            }
            this.isLoading = true;
            this.axios.post('/update-usage-checked', { ids })
                .then(() => {
                    openNotify(this.$t('common.checkSuccess'));
                    this.getUncheckedUsage(); // 重抓，已確認的會消失
                })
                .catch(() => openNotify('更新失敗', 'is-danger'))
                .finally(() => this.isLoading = false);
        },
    },
    components: {
        page, openNotify, LoadingSection
    },
};
</script>