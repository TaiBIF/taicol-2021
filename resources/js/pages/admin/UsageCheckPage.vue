<template>
    <div class="w-full h-full">
        <div class="container flex flex-col h-full">
            <div class="bg-white">
                <div class="pt-2 flex items-center">
                    <p class="ml-4 font-bold text-3xl inline">{{ $t('header.adminMenu.usageCheck') }}</p>
                </div>

                <div class="flex p-4 gap-1">
                    <div class="w-full">
                    <button :disabled="isLoading" class="button" v-on:click="onSubmit">開始檢查</button>
                    </div>
                </div>

                <!-- 列出is_checked=0的資料 -->
                <div class="p-4">
                    <p class="mb-3">上次檢查時間：{{ lastUpdated }}</p>
                    <p class="mb-3">尚未確認usage👇(請至api_usage_check確認詳細資訊)
</p>
                   
                    <table class="table text-[14px] is-fullwidth max-w-full has-text-grey">
                        <thead class="font-bold">
                        <tr>
                            <th class="w-[80px] has-text-grey" v-text="'Check ID'"/>
                            <th class="w-[80px] has-text-grey" v-text="'錯誤類型'"/>
                            <th class="w-[270px] has-text-grey" v-text="'更新時間'"/>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="usage in usages">
                            <td>{{ usage.id }}</td>
                            <td>{{ erroTypeMap[usage.errorType] }}</td>
                            <td>{{ usage.updatedAt }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <button :disabled="isLoading" class="button" v-on:click="getUncheckedUsage">確認完畢更新表格</button>

                </div>

            </div>

            <div v-if="isLoading" class="grow">
                <loading-section></loading-section>
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
            usages: [],
            erroTypeMap: {
                1: 'fixed usage_id 被刪除',
                2: 'autonym / 同模：同一篇文獻 在不同分類群 同時出現 accepted和not-acceped',
                3: 'autonym / 同模：同一篇文獻中有多個not-accepted在不同分類群',
                4: '同模（不包含autonym）：同一篇文獻中多個accepted',
                5: 'accepted_taxon_name_id, taxon_name_id, reference_id 對到多個status',
                6: '學名在同一篇文獻中 被設定成兩個分類群的同物異名',
                7: '同一個分類群有一個以上的接受名',
                8: '同一個分類群裡面沒有任何接受名',
                9: '同一個學名出現在同一篇文獻中的兩個分類群(不同accepted_taxon_name_id) 且不是誤用',
                10: 'reference_id, accepted_taxon_name_id, taxon_name_id 對到多個 usage_id',
            }
        };
    },
    mounted() {
        this.getUncheckedUsage()
    },
    methods: {
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

                this.isLoading = false;

                if ( data['message'] == 'done'){
                    this.lastUpdated = data['lastUpdated'];
                    openNotify(this.$t('common.saveSuccess'));
                    this.getUncheckedUsage();
                } else {
                    openNotify('發生錯誤', 'is-danger');
                }

            });


        }
    },
    components: {
        page, openNotify, LoadingSection
    },
};
</script>
