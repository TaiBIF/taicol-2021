<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto overflow-x-auto px-10 py-4 flex-1"">
                <div class="py-3">
                    <p class="ml-3 font-bold text-3xl inline">{{ $t('taxonName.bulkCreate') }}</p>
                    <br>
                    <i class="mt-8 ml-3 fas fa-info-circle"></i>
                    <span v-html="$t('taxonName.bulkCreateNote1')"></span>
                    <br>
                    <i class="ml-3 fas fa-info-circle"></i>
                    <span v-html="$t('taxonName.bulkCreateNote2')"></span>
                    <br>
                    <i class="ml-3 fas fa-info-circle"></i>
                    <span v-html="$t('taxonName.bulkCreateNote3')"></span>
                </div>
                <table class="mt-1 table is-fullwidth is-hoverable has-text-left sticky-table">
                    <thead>
                    <tr>
                        <th class="w-[60px]">
                            <div class="flex flex-col items-center justify-center">
                                <span>{{ $t('taxonName.skip') }}</span>
                                <input type="checkbox" v-model="isAllSkip" class="mt-1" />
                            </div>
                        </th>
                        <th class="w-[115px] is-marked">
                            {{ $t('taxonName.nomenclature') }}
                            <nomenclature-select
                                v-model="headerNomenclature"
                                @input="onHeaderNomenclatureChange"
                            />
                        </th>

                        <th>{{ $t('taxonName.kingdom') }}
                            <kingdom-select
                                v-model="headerKingdom"
                                :options="headerKingdomOptions"
                                @input="onHeaderKingdomChange"
                                placeholder="批次設定"
                            />
                        </th>
                        <th class="w-[100px] is-marked">{{ $t('taxonName.rank') }}</th>
                        <th class="w-[200px] is-marked">{{ $t('taxonName.name') }}</th>
                        <th>{{ $t('taxonName.latinGenus') }}</th>
                        <th>{{ $t('taxonName.latinS1') }}</th>
                        <th>{{ $t('taxonName.sRank',
                                {s: $t('taxonName.s').repeat(1)},1) }}</th>
                        <th>{{ $t('taxonName.sLatin',
                                {s: $t('taxonName.s').repeat(1)},1)}}</th>
                        <th>{{ $t('taxonName.authors') }}</th>
                        <th class="w-[200px]">{{ $t('taxonName.selectAnotherScientificName') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="p in presetData">
                        <td>
                            <input type="checkbox" v-model="p._skip" />
                        </td>
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
            headerNomenclature: null,
            headerKingdom: null,
            headerKingdomOptions: [],
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
        isAllSkip: {
                get() {
                    // 當 presetData 還沒載入或為空時回傳 false
                    if (!this.presetData || this.presetData.length === 0) return false;
                    // 檢查是否每一筆資料的 _skip 都為 true
                    return this.presetData.every(p => p._skip);
                },
                set(val) {
                    // 當表頭勾選/取消勾選時，同步將所有 presetData 的 _skip 設定為該數值 (true/false)
                    if (this.presetData) {
                        this.presetData.forEach(p => {
                            p._skip = val;
                        });
                    }
                }
            }
    },
    methods: {
        onHeaderNomenclatureChange(value) {
            // 更新表頭 kingdom 的選項
            this.headerKingdomOptions = value?.kingdoms || [];
            this.headerKingdom = null;

            // 同步更新所有資料列的 nomenclature（但不清空 rank）
            this.presetData.forEach(p => {
                p._nomenclature = value;
                
                // 只更新 options，不清空值
                p._rankOptions = value?.ranks.filter(rank => rank.id !== 47) || [];
                p._kingdomOptions = value?.kingdoms || [];
                
                // 只清空 kingdom（因為選項變了）
                p._kingdom = null;
                
                // rank 保留原值，但檢查是否還在新的選項中
                if (p._rank && !p._rankOptions.find(r => r.key === p._rank.key)) {
                    p._rank = null;  // 只有當原本的 rank 不在新選項中才清空
                }
            });
        },
        onHeaderKingdomChange(value) {
            this.presetData.forEach(p => {
                const found = p._kingdomOptions.find(k => k.name === value?.name);
                if (found) {
                    p._kingdom = found;
                }
            });
        },        
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
                skip: row._skip || false, 
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

                // 設定表頭預設值為第一筆的 nomenclature
                if (this.presetData.length > 0) {
                    // this.headerNomenclature = this.presetData[0].nomenclature;
                    this.headerNomenclature = nomenclatures.find(n => n.id === this.presetData[0].nomenclature);
                    this.headerKingdomOptions = this.headerNomenclature?.kingdoms || [];
                }

                return {
                    ...p,
                    _skip: false, 

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

.sticky-table {
    border-collapse: collapse;
    
    thead {
        position: sticky;
        top: 0;
        background: white;
        z-index: 1;
    }
    
    th, td {
        min-width: 150px;
        white-space: nowrap;
    }
    
    // 特定欄位寬度
    th:nth-child(1), td:nth-child(1) {
        min-width: 60px; // 學名欄位
    }

    th:nth-child(5), td:nth-child(5) {
        min-width: 200px; // 學名欄位
    }
    
    th:nth-child(11), td:nth-child(11) {
        min-width: 200px; // 選擇其他學名欄位
    }

    .box.flex-1 {
        min-height: 0; // 讓 flex-1 在 flex container 中正常運作
    }
}
</style>
