<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4">
                <div class="py-3 flex items-center">
                    <p class="ml-3 font-bold text-3xl inline">{{ $t('taxonName.bulkCreate') }}</p>
                </div>

                <table class="table is-fullwidth is-hoverable has-text-left">
                    <thead>
                    <tr>
                        <th class="w-[200px] is-marked">{{ $t('taxonName.nomenclature') }}</th>
                        <th>{{ $t('taxonName.kingdom') }}</th>
                        <th class="w-[100px] is-marked">{{ $t('taxonName.rank') }}</th>
                        <th class="is-marked">{{ $t('taxonName.name') }}</th>
                        <th>{{ $t('taxonName.latinGenus') }}</th>
                        <th>{{ $t('taxonName.latinS1') }}</th>
                        <th>{{ $t('taxonName.sRank',{s: $t('taxonName.s')}) }}</th>
                        <th>{{ $t('taxonName.sLatin',{s: $t('taxonName.s')}) }}</th>
                        <th>{{ $t('taxonName.authors') }}</th>
                        <th class="w-[200px]">選擇其他學名</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="p in presetData">
                        <td>
                            <nomenclature-select
                                v-model="p._nomenclature"
                                @input="onNomenclatureChange(p)"
                            />
                        </td>
                        <td>
                            <kingdom-select
                                v-if="p._rank && p._rank.order > kingdomRank.order"
                                v-model="p._kingdom"
                                :options="p._kingdomOptions"
                            />
                            <kingdom-select
                                v-else
                                v-model="p._kingdom"
                                :options="p._kingdomOptions"
                            />
                        </td>
                        <td>
                            <rank-select
                            v-model="p._rank"
                            :options="p._rankOptions"
                            />

                        </td>
                        <td><general-input v-model="p.latinName"/></td>
                        <td><general-input v-model="p.latinGenus"/></td>
                        <td><general-input v-model="p.latinS1"/></td>
                        <td><general-input v-model="p.s2Rank"/></td>
                        <td><general-input v-model="p.latinS2"/></td>
                        <td><general-input v-model="p.formattedAuthors"/></td>
                        <td><taxon-name-select v-model="p._selectedName"/></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button m-0"
                            :class="{ 'is-loading': isLoading }"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button class="button m-0"
                            :class="{ 'is-loading': isLoading }"
                            v-on:click="onSubmit(true)"
                            v-text="$t('namespace.addNameAndContinueToImportChecklist')"/>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
import { mapGetters } from 'vuex';
import TaxonNameSelect from '../components/selects/TaxonNameSelect.vue';
import NomenclatureSelect from '../components/selects/NomenclatureSelect.vue';
import RankSelect from '../components/selects/RankSelect.vue';
import KingdomSelect from '../components/selects/KingdomSelect.vue';
import Page from './Page.vue';
import GeneralInput from '../components/GeneralInput.vue';
import { openNotify } from '../utils';

export default {
    components: {
        Page,
        TaxonNameSelect,
        NomenclatureSelect,
        RankSelect,
        KingdomSelect,
        GeneralInput
    },
    data() {
        return {
            formStatus: this.$c.PAGE_IS_LOADING,
            presetData: null,
            isLoading: false,
            nomenclatures: null,
        };
    },
    computed: {
        ...mapGetters({
            genusRank: 'rank/getGenusRank',
            speciesRank: 'rank/getSpeciesRank',
            kingdomRank: 'rank/getKingdomRank',
        }),

    },
    methods: {
        onNomenclatureChange(row) {
            const n = row._nomenclature

            // 清值（跟單筆頁一致）
            row._rank = null
            row._kingdom = null

            // 重新給 options
            // row._rankOptions = n?.ranks || []
            row._rankOptions = n?.ranks.filter(rank => rank.id !== 47) || [],
            row._kingdomOptions = n?.kingdoms || []
        },
        goBack(){
            this.$router.push({ name: 'namespace-list' });
        },
        onSubmit() {

            this.isLoading = true;

            const submitData = this.presetData.map(row => ({
                // index: row.index,
                original_name: row.originalName,
                nomenclature: row._nomenclature?.id,
                kingdom: row._kingdom?.name || null,
                rank: row._rank?.key || null,
                latin_name: row.latinName,
                latin_genus: row.latinGenus,
                latin_s1: row.latinS1,
                s2_rank: row.s2Rank,
                latin_s2: row.latinS2,
                formatted_authors: row.formattedAuthors,
                selected_name: row._selectedName?.id || null, // 加上這個欄位
            }));

            console.log(submitData);

            // 驗證必填欄位
            const invalidRows = [];
            submitData.forEach((row, index) => {
                // 如果沒有 selectedName，就需要檢查必填欄位
                if (!row.selected_name) {
                    const missingFields = [];
                    
                    if (!row.latin_name) missingFields.push('學名');
                    if (!row.nomenclature) missingFields.push('命名法規');
                    if (!row.rank) missingFields.push('階層');
                    
                    if (missingFields.length > 0) {
                        invalidRows.push({
                            rowIndex: index + 1,
                            // originalIndex: row.index,
                            missingFields: missingFields
                        });
                    }
                }
            });

            // 如果有未通過驗證的 row，停止提交
            if (invalidRows.length > 0) {
                this.isLoading = false;
                
                const errorMessage = invalidRows.map(row => 
                    `第 ${row.rowIndex} 筆資料缺少必填欄位: ${row.missingFields.join('、')}`
                ).join('\n');
                
                openNotify(errorMessage, 'is-danger');
                return; // 停止執行
            }


            this.axios.post(`namespaces/${this.$route.params.id}/import/nameandusage`, submitData)
            .then(({ data }) => {
                if (data.success) {
                    this.$router.push({ name: 'namespace-usage-list', params: { id: this.$route.params.id } });
                }
            })
            .catch((error) => {
                console.log(error);
                this.isLoading = false;
                
                const message = error?.message || '處理失敗，請聯絡管理員';
                openNotify(message, 'is-danger');
            });
        },
        async onPreload() {
            try {
                const { data: {data, nomenclatures, finished} } = await this.axios.get(`/namespaces/${this.$route.params.id}/names`);

                if (finished) {

                    this.$router.push({ name: 'namespace-usage-list', params: { id: this.$route.params.id} });

                } 

                this.presetData = data;
                this.nomenclatures = nomenclatures;

                this.presetData = data.map(p => {
                const nomenclature = nomenclatures.find(n => n.id === p.nomenclature)
                const rank = nomenclature.ranks.find(n => n.key === p.rank)
                const kingdom = nomenclature.kingdoms.find(n => n.name === p.kingdom) ?? null;

                return {
                    ...p,


                    // v-model 用
                    _selectedName: null,
                    _nomenclature: nomenclature || null,
                    _rank: rank || null,
                    _kingdom: kingdom || null,

                    _rankOptions: nomenclature?.ranks.filter(rank => rank.id !== 47) || [],
                    _kingdomOptions: nomenclature?.kingdoms || [],
                }
                })

                return 200;
            } catch ({ status }) {
                return status;
            }
        },
    },
};
</script>
<style lang="scss" scoped>
.form-body {
    height: calc(100vh - #{$navbar-height} - 7rem);
}

.is-marked::before {
    content: '*';
    color: red;
}
</style>
