<template>
    <div class="form">
        <div class="columns">
            <div class="column is-12">
                <i class="fas fa-info-circle"></i>
                {{ $t(`classification.info`) }}
            </div>
        </div>

        <div class="columns">
            <div class="column is-5">
                <div class="columns">
                    <div class="column is-12">
                        <p class="subtitle is-4">{{ $t(`classification.filterTaxon`) }}</p>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-3">
                        <p class="label">{{ $t('classification.classificationView') }}</p>
                    </div>
                    <div class="column is-9">
                        <label class="mr-3">
                            <input type="radio" v-model="classificationView" value="custom" :disabled="isReferenceAdded">
                            {{ $t('classification.customView') }}
                        </label>
                        <label>
                            <input type="radio" v-model="classificationView" value="taicol" :disabled="isReferenceAdded">
                            {{ $t('classification.taicolView') }}
                        </label>
                    </div>
                </div>
                <div class="columns" >
                    <div class="column is-3">
                        <p class="label">{{ $t('classification.filterMethod') }}</p>
                    </div>
                    <div class="column is-9">
                        <div class="field">
                            <classification-select 
                                v-model="targetMethod" 
                                :disabled="isReferenceAdded"
                                :classification-view="classificationView"
                            />
                        </div>
                    </div>
                </div>
            </div>


            <div class="column is-7">
                <div class="columns">
                    <div class="column is-12">
                        <p class="subtitle is-4">{{ $t(`classification.synonymySettings`) }}</p>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-3">
                        <p class="label">{{ $t('classification.usageReferences') }}</p>
                    </div>
                    <div class="column is-9">
                        <label class="mr-3">
                            <input type="radio" v-model="usageReferences" value="all" :disabled="isReferenceAdded">
                            {{ $t('classification.allUsageReference') }}
                        </label>
                        <label class="mr-3" v-if="this.classificationView !== 'taicol'">
                            <input type="radio" v-model="usageReferences" value="originalincluded" :disabled="isReferenceAdded">
                            {{ $t('classification.originalAndIncludedUsageReference') }}
                        </label>
                        <label class="mr-3">
                            <input type="radio" v-model="usageReferences" value="original" :disabled="isReferenceAdded">
                            {{ $t('classification.originalUsageReference') }}
                        </label>
                        <label>
                            <input type="radio" v-model="usageReferences" value="none" :disabled="isReferenceAdded">
                            {{ $t('classification.noUsageReference') }}
                        </label>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-3">
                        <p class="label">{{ $t('classification.completeness') }}</p>
                    </div>
                    <div class="column is-9">
                        <label class="mr-3">
                            <input type="radio" v-model="completeness" value="full" :disabled="isReferenceAdded">
                            {{ $t('classification.fullSynonymy') }}
                        </label>
                        <label class="mr-3" v-if="this.classificationView !== 'taicol'">
                            <input type="radio" v-model="completeness" value="concise" :disabled="isReferenceAdded">
                            {{ $t('classification.conciseSynonymy') }}
                        </label>
                        <label>
                            <input type="radio" v-model="completeness" value="none" :disabled="isReferenceAdded">
                            {{ $t('classification.noSynonymy') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column is-12">


            <div class="columns bg-gray-50" v-if="returnTargetMethod(targetMethod)==1">
                <div class="column is-2">
                    <label class="label">
                    {{ $t('classification.higherTaxa') }}
                    <!-- <tooltip>
                        <i class="fas fa-info-circle"></i>
                        <template v-slot:body>
                            <div class="fit-content">
                                <template  class="w-[100px]">
                                    {{ $t(`classification.rankInfo`) }}
                                </template>
                            </div>
                        </template>
                    </tooltip> -->
                    </label>
                </div>
                <div class="column is-6">
                    <div class="field">
                        <higher-taxa-select v-model="higherTaxa" :disabled="isReferenceAdded" />
                    </div>
                </div>
            </div>
            <div class="columns bg-gray-50" v-if="returnTargetMethod(targetMethod)==3">
                <div class="column is-3">
                    <p class="label">{{ $t('classification.filterRegion') }}</p>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <county-select v-model="targetCounty" :disabled="isReferenceAdded"/>
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <municipality-select v-model="targetMunicipality" :county="targetCounty" :disabled="isReferenceAdded"/>
                    </div>
                </div>
            </div>
            <div class="columns bg-gray-50" v-if="returnTargetMethod(targetMethod)==3">
                <div class="column is-3">
                    <p class="label">{{ $t('classification.filterBioGroup') }}</p>
                </div>
                <div class="column is-4">
                    <div class="field">
                        <bio-group-select v-model="targetBioGroup" :disabled="isReferenceAdded"/>
                    </div>
                </div>
            </div>
            <div v-if="returnTargetMethod(targetMethod)" class="columns bg-gray-50">
                <div class="column is-3">
                    <label class="label" v-text="$t('classification.onlyInTaiwan')"></label>
                </div>
                <div class="column is-6">
                    <input type="radio" v-model="onlyInTaiwan" value="yes" :disabled="isReferenceAdded">
                    {{ $t('usage.yes') }}
                    <input type="radio" v-model="onlyInTaiwan" value="no" :disabled="isReferenceAdded">
                    {{ $t('usage.no') }}
                </div>
            </div>
            <div v-if="returnTargetMethod(targetMethod)" class="columns bg-gray-50">
                <div class="column is-3">
                    <label class="label" v-text="$t('classification.excludeCultured')"></label>
                </div>
                <div class="column is-6">
                    <input type="radio" v-model="excludeCultured" value="yes" :disabled="isReferenceAdded">
                    {{ $t('usage.yes') }}
                    <input type="radio" v-model="excludeCultured" value="no" :disabled="isReferenceAdded">
                    {{ $t('usage.no') }}
                </div>
            </div>
            <div v-if="returnTargetMethod(targetMethod) && classificationView !== 'taicol'" class="columns bg-gray-50">
                <div class="column is-3">
                    <label class="label">
                        {{ $t('classification.includeReferences')  }}
                        <tooltip>
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:body>
                                <div class="w-[200px]">
                                    <template>
                                    {{ $t(`classification.referenceInfo`) }}
                                    </template>
                                </div>
                            </template>
                        </tooltip>
                    </label>
                </div>
                <div class="column is-6">
                    <button  v-if="returnTargetMethod(targetMethod)==1 || returnTargetMethod(targetMethod)==3" class="button is-outlined is-small"
                            v-on:click="onAddRelatedReferences" :disabled="isReferenceAdded">
                        {{ $t('classification.addRelatedReferences') }}
                    </button>
                    <reference-select
                        v-if="returnTargetMethod(targetMethod)==2"
                        :hide-create="true"
                        v-on:input="onSelectReference"
                    />
                </div>
            </div>
            <div v-if="returnTargetMethod(targetMethod) && classificationView !== 'taicol'" class="columns bg-gray-50">
                <div class="min-h-3/5 reference-list bg-white">
                    <div v-if="isLoading" class="flex w-full items-center justify-center">
                        <loading></loading>
                    </div>
                    <div class="bg-gray-100 ">
                        <ul>
                            <li v-if="references.length > 0" >
                            <label class="label ref p-1 px-4 my-1 hover:bg-gray-200 cursor-pointer">
                                <input 
                                v-model="selectAll" 
                                @change="toggleSelectAll"
                                type="checkbox"
                                />
                                &nbsp;&nbsp;
                                <span >
                                {{ $t('classification.selectAll') }}
                                </span>
                            </label>

                            </li>
                            <li v-for="reference in references">
                                <label class="label ref p-1 px-4 my-1 hover:bg-gray-200 cursor-pointer">
                                    <input v-model="referenceIds" :value="reference.id" type="checkbox"/>
                                    &nbsp;&nbsp;
                                    <span v-text="reference.title"></span>
                                    <i v-if="reference.type"
                                    class="ml-1 far fa-eye cursor-pointer"
                                    @click.prevent.stop="openUsagePreview(reference.id)"></i>
                                    <span class="help has-text-grey-light" v-text="reference.subtitle"></span>
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div v-if="classificationView === 'taicol' && isLoading" class="columns">
                <div class="column is-12 flex items-center justify-center py-4">
                    <loading></loading>
                </div>
            </div>
        </div>
    </div>

    </div>
</template>

<script>
import { debounce } from 'lodash';
import { openNotify } from '../../utils';
import ClassificationSelect from '../selects/ClassificationSelect.vue';
import GeneralInput from '../GeneralInput.vue';
import CountySelect from '../selects/CountySelect.vue';
import MunicipalitySelect from '../selects/MunicipalitySelect.vue';
import ReferenceSelect from '../selects/ReferenceSelect.vue';
import HigherTaxaSelect from '../selects/HigherTaxaSelect.vue';
import BioGroupSelect from '../selects/BioGroupSelect.vue';
import Tooltip from '../Tooltip.vue';
import Loading from '../Loading.vue';

export default {
    mounted() {
    },
    data() {
        return {
            references: [],
            referenceIds: [],
            errors: {},
            targetMethod: null,
            targetCounty: null,
            targetMunicipality: null,
            targetBioGroup: null,
            higherTaxa: [],
            excludeCultured: 'yes',
            onlyInTaiwan: 'yes',
            isLoading: false,
            taxonIds: [],
            isReferenceAdded: false,
            classificationView: 'custom',
            usageReferences: 'all',
            completeness: 'full',
        };
    },
    computed: {
        // TaiCOL 篩選條件是否齊全（用於觸發自動載入）
        taicolFilterReady() {
            if (this.classificationView !== 'taicol' || !this.targetMethod) return false;
            if (this.targetMethod.id === 1) return this.higherTaxa.length > 0;
            if (this.targetMethod.id === 3) return this.targetCounty != null && this.targetBioGroup != null;
            return false;
        },
        // 是否可按下產生名錄
        canGenerate() {
            if (this.isLoading) return false;   // 載入中暫時禁用產生名錄按鈕
            if (!this.targetMethod) return false;
            const id = this.targetMethod.id;

            if (this.classificationView === 'taicol') {
                return this.taicolFilterReady;
            }
            // 自訂分類觀
            if (id === 1 || id === 3) return this.isReferenceAdded;
            if (id === 2) return this.referenceIds.length > 0;
            return false;
        },
        selectAll: { // 判斷是否全選
            get() {
                return this.references.length > 0 && this.referenceIds.length === this.references.length;
            },
            set(value) {
                // 這個 setter 主要是為了 v-model，實際邏輯在 toggleSelectAll 中處理
            }
        },
        formData() {
            return {
                classificationView: this.classificationView,
                usageReferences: this.usageReferences,
                completeness: this.completeness,
                references: this.referenceIds,
                onlyInTaiwan: this.onlyInTaiwan,
                excludeCultured: this.excludeCultured,
                method: this.targetMethod.id,
                higherTaxa: this.higherTaxa,
                taxonIds: this.taxonIds,
                county: this.targetCounty?.name,
                municipality: this.targetMunicipality?.name,
                bioGroup: this.targetBioGroup?.name,
            }
        },
        // taicol 查詢條件的簽章，任一改變代表需要重新抓文獻
        taicolQuerySignature() {
            return JSON.stringify({
                method: this.targetMethod?.id,
                higherTaxa: this.higherTaxa.map(f => f.taxonId),
                county: this.targetCounty?.name,
                municipality: this.targetMunicipality?.name,
                bioGroup: this.targetBioGroup?.name,
                onlyInTaiwan: this.onlyInTaiwan,
                excludeCultured: this.excludeCultured,
            });
        },
    },
    watch: {
        targetMethod() {
            this.references = []
        },
        classificationView(newVal) {
            // 切換分類觀時，把不該存在於該視圖的 method 清掉
            if (newVal === 'custom' && this.targetMethod?.id === 3) this.targetMethod = null;
            if (newVal === 'taicol' && this.targetMethod?.id === 2) this.targetMethod = null;
            // 重置文獻相關狀態，讓 TaiCOL/自訂切換不會殘留
            this.references = [];
            this.referenceIds = [];
            this.taxonIds = [];
            this.isReferenceAdded = false;
        },
        canGenerate(newVal) {
            this.$emit('update:canGenerate', newVal);
        },
        taicolQuerySignature() {
            // 只處理 taicol，且已載入過才需要清掉重抓；custom view 行為不變
            if (this.classificationView !== 'taicol' || !this.isReferenceAdded) return;
            this.references = [];
            this.referenceIds = [];
            this.taxonIds = [];
            this.isReferenceAdded = false;
        },
    },
    methods: {
        toggleSelectAll() {
        if (this.referenceIds.length === this.references.length) {
            // 如果已經全選，則取消全選
            this.referenceIds = [];
        } else {
            // 否則選中所有項目
            this.referenceIds = this.references.map(ref => ref.id);
        }
        },
        reset(){
            this.references = [],
            this.referenceIds = [],
            this.errors = {},
            this.targetMethod = null,
            this.targetCounty = null,
            this.targetBioGroup = null,
            this.higherTaxa = [],
            this.excludeCultured = 'yes',
            this.onlyInTaiwan = 'yes',
            this.classificationView = 'custom',
            this.usageReferences = 'all',
            this.completeness = 'full'
        },
        onSelectReference(selectedReference){
            if (selectedReference)
                if (!this.referenceIds.includes(selectedReference.id)) {
                    this.references = [...this.references, selectedReference]
                    this.referenceIds =  [...this.referenceIds, selectedReference.id]

                    this.references = this.references.sort((a, b) => {
                        if (a.id === 0) return -1;  // backbone 永遠在最前
                        if (b.id === 0) return 1;
                        return b.publishYear - a.publishYear;  // DESC
                    });
            }
        },
        async onAddRelatedReferences(){
            if (!((this.targetMethod.id == 1 && this.higherTaxa.length > 0) || (this.targetMethod.id == 3 && this.targetCounty != null && this.targetBioGroup != null))) {
                openNotify('請完整設置篩選條件', 'is-danger');
                return false;
            }

            this.isLoading = true;
            try {
                const { data: { data, hasBackbone, taxonIds } } = await this.axios.get('/selected-references', {
                    params: {
                        classificationView: this.classificationView,
                        higherTaxa: this.higherTaxa.map((f) => (f.taxonId)),
                        onlyInTaiwan: this.onlyInTaiwan,
                        excludeCultured: this.excludeCultured,
                        county: this.targetCounty?.name,
                        municipality: this.targetMunicipality?.name,
                        bioGroup: this.targetBioGroup?.name,
                        method: this.targetMethod.id
                    },
                });

                if (data.length === 0) {
                    openNotify('無對應文獻，請重新設置篩選條件', 'is-danger');
                    return false;
                }

                const existingIds = new Set(this.references.map(ref => ref.id))
                const newData = data.filter(ref => !existingIds.has(ref.id))
                this.references = [...this.references, ...newData]

                if (hasBackbone == true && !this.referenceIds.includes(0)) {
                    this.references = [{ 'id': 0, 'title': 'TaiCOL Backbone' }, ...this.references]
                }

                this.references = this.references.sort((a, b) => {
                    if (a.id === 0) return -1;  // backbone 永遠在最前
                    if (b.id === 0) return 1;
                    return b.publishYear - a.publishYear;  // DESC
                });

                this.taxonIds = taxonIds;

                // TaiCOL 視圖下使用者看不到清單，自動全選讓 referenceIds 帶入 form
                if (this.classificationView === 'taicol') {
                    this.referenceIds = this.references.map(r => r.id);
                }

                this.isReferenceAdded = true;
                return true;
            } finally {
                this.isLoading = false;
            }
        },
        returnTargetMethod(targetMethod){
            if (targetMethod){
                return targetMethod.id
            }
        },
        openUsagePreview(referenceId) {
            this.$store.commit('openModal', {
                component: () => import('../modals/UsagePreviewModal.vue'),
                props: {
                    referenceId: referenceId,
                }
            });
        },
    },
    components: {
        ClassificationSelect,
        CountySelect,
        MunicipalitySelect,
        GeneralInput,
        ReferenceSelect,
        HigherTaxaSelect,
        BioGroupSelect,
        Tooltip,
        Loading
    },
};
</script>
<style lang="scss" scoped>
.label {
    font-size: 1.2rem;
    font-weight: normal;
    line-height: 1.6;
}

.reference-list {
    border: 1px solid $light-grey;
    width: 100%;
    margin: 1.5rem;
}

.help {
    margin-left: 30px;
}

.ref {
    font-size: 1rem;
    font-weight: normal;
}


</style>