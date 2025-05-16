<template>
    <div class="form">
        <div class="columns">
            <div class="column is-12">
                <i class="fas fa-info-circle"></i>
                說明:本功能用於文戲發表前，讓使用者快速整理、建立自己的分類觀，包含彙整資料庫中已有之相關分類群、相關文獻的學名使用，也可透過外部API以地區篩選分類群等，使用者可選擇納入或不納入非存在於臺灣的分類群，也可選擇要納入哪些文獻分類觀等，本工具會依照納入的文獻優先性決定學名有效、無效等，產出名錄後匯入到「我的名錄」供使用者後續編輯。
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <p class="subtitle is-4">{{ $t(`classification.filterTaxon`) }}</p>
            </div>
        </div>
        <div class="columns" >
            <div class="column is-3">
                <p class="label">{{ $t('classification.filterMethod') }}</p>
            </div>
            <div class="column is-6">
                <div class="field">
                    <classification-select v-model="targetMethod" :disabled="isReferenceAdded"/>
                </div>
            </div>
        </div>
        <div class="columns" v-if="returnTargetMethod(targetMethod)==1">
            <div class="column is-3">
                <label class="label">
                {{ $t('classification.higherTaxa') }}
                <tooltip>
                    <i class="fas fa-info-circle"></i>
                    <template v-slot:body>
                        <div class="fit-content">
                            <template  class="w-[100px]">
                                最高可選擇階層到科
                            </template>
                        </div>
                    </template>
                </tooltip>
                </label>

            </div>
            <div class="column is-6">
                <div class="field">
                    <higher-taxa-select v-model="higherTaxa" :disabled="isReferenceAdded" />
                </div>
            </div>
        </div>
        <div class="columns" v-if="returnTargetMethod(targetMethod)==3">
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

        <div v-if="returnTargetMethod(targetMethod)" class="columns">
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

        <div v-if="returnTargetMethod(targetMethod)" class="columns">
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

        <div v-if="returnTargetMethod(targetMethod)" class="columns">
            <div class="column is-3">
                <label class="label">
                    {{ $t('classification.includeReferences')  }}
                    <tooltip>
                        <i class="fas fa-info-circle"></i>
                        <template v-slot:body>
                            <div class="w-[200px]">
                                <template>
                                    僅能納入具有學名使用的文獻
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

        <div v-if="returnTargetMethod(targetMethod)" class="columns">
            <div class="min-h-3/5 reference-list">
                <div v-if="isLoading" class="flex w-full items-center justify-center">
                    <loading></loading>
                </div>
                <div class="bg-gray-100 ">
                    <ul>
                        <li v-for="reference in references">
                            <label class="label ref p-1 px-4 my-1 hover:bg-gray-200 cursor-pointer">
                                <input v-model="referenceIds" checked :value="reference.id" type="checkbox"/>
                                &nbsp;&nbsp;
                                <span v-text="reference.title"></span>
                                <span class="help has-text-grey-light" v-text="reference.subtitle"></span>
                            </label>
                        </li>
                    </ul>
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
            higherTaxa: [],
            excludeCultured: 'yes',
            onlyInTaiwan: 'yes',
            isLoading: false,
            taxonIds: [],
            isReferenceAdded: false,
            isReferenceSelected: false
        };
    },
    computed: {
        formData() {
            return {
                references: this.referenceIds,
                onlyInTaiwan: this.onlyInTaiwan,
                excludeCultured: this.excludeCultured,
                method: this.targetMethod.id,
                higherTaxa: this.higherTaxa,
                taxonIds: this.taxonIds,
                county: this.targetCounty?.name,
                municipality: this.targetMunicipality?.name,

            }
        },
    },
    watch: {
        targetMethod() {
            this.references = []
        },
        isReferenceSelected(newVal) {
            this.$emit('update:isReferenceSelected', newVal);
        },
        referenceIds(){
            if (this.referenceIds.length > 0){
                this.isReferenceSelected = true;
            } else {
                this.isReferenceSelected = false;
            }
        }
    },
    methods: {
        reset(){
            this.references = [],
            this.referenceIds = [],
            this.errors = {},
            this.targetMethod = null,
            this.targetCounty = null,
            this.higherTaxa = [],
            this.excludeCultured = 'yes',
            this.onlyInTaiwan = 'yes'
        },
        onSelectReference(selectedReference){
            if (selectedReference)
                if (!this.referenceIds.includes(selectedReference.id)) {
                    this.references = [...this.references, selectedReference]
                    this.referenceIds =  [...this.referenceIds, selectedReference.id]
                    this.references = this.references.sort((a, b) => a.publishYear - b.publishYear);
            }
        },
        onAddRelatedReferences(){

            if ((this.targetMethod.id == 1 && this.higherTaxa.length > 0 )|| (this.targetMethod.id == 3 && this.targetCounty != null )){
                this.isLoading = true;
                this.axios.get('/selected-references', {
                    params: {
                        higherTaxa: this.higherTaxa.map((f) => (f.taxonId)),
                        onlyInTaiwan: this.onlyInTaiwan,
                        excludeCultured: this.excludeCultured,
                        county: this.targetCounty?.name,
                        municipality: this.targetMunicipality?.name,
                        method: this.targetMethod.id
                    },
                })
                .then(({ data: { data, hasBackbone, taxonIds }}) => {

                    if (data.length > 0){

                        const existingIds = new Set(this.references.map(ref => ref.id))
                        const newData = data.filter(ref => !existingIds.has(ref.id))
                        this.references = [...this.references, ...newData]
                        this.referenceIds =  [...this.referenceIds, ...newData.map((d)=>d.id)]

                        if (hasBackbone==true && !this.referenceIds.includes(0)){
                            this.references = [{'id': 0, 'title': 'TaiCOL Backbone'},...this.references]
                            this.referenceIds =  [0, ...this.referenceIds]
                        }

                        this.references = this.references.sort((a, b) => a.publishYear - b.publishYear);
                        this.taxonIds = taxonIds;
                        this.isLoading = false;
                        this.isReferenceAdded = true;
                    } else {
                        this.isLoading = false;
                        openNotify('無對應文獻，請重新設置篩選條件', 'is-danger');
                    }
                });
            } else {
                openNotify('請設置篩選條件', 'is-danger');
                this.isLoading = false;
            }

            
        },
        returnTargetMethod(targetMethod){
            if (targetMethod){
                return targetMethod.id
            }
        }
    },
    components: {
        ClassificationSelect,
        CountySelect,
        MunicipalitySelect,
        GeneralInput,
        ReferenceSelect,
        HigherTaxaSelect,
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