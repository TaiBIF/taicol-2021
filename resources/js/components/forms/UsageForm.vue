<template>
    <div class="form">
        <div class="columns form-inner-container">
            <div class="column left-line">
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" v-text="'上階層'"/>
                            <taxon-name-select v-model="parentTaxonName" :errors="errors['parent']"/>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked">
                                地位
                            </label>
                            <status-select v-model="status"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label is-marked">
                                標註
                            </label>
                            <indication-select v-model="properties.indications" :status="status"
                                               :type="taxonName.nomenclature.group"/>
                        </div>
                    </div>
                </div>
                <br/>
                <p class="title is-5">文獻</p>
                <hr/>

                <!-- 其他引用文獻 -->
                <div v-for="(perUsage, index) in perUsages" class="box">
                    <a class="close-button"
                       v-on:click="() => perUsages.splice(index, 1)">
                    </a>
                    <div class="columns is-small">
                        <div class="column is-6">
                            <div class="columns">
                                <div class="column">
                                    <div class="field">
                                        <label class="label is-marked">
                                            文獻
                                            <label class="label is-pulled-right">
                                                <input id="proParte" v-model="perUsage.proParte" class="checkbox"
                                                       type="checkbox"/>
                                                <span v-text="'部分引用/排除'"/>
                                            </label>
                                        </label>
                                        <reference-select v-model="perUsage.target" :errors="errors[`perUsages${index}ReferenceId`]"/>
                                    </div>
                                </div>
                            </div>
                            <div class="columns">
                                <div class="column is-6">
                                    <div class="field">
                                        <label class="label">學名出現頁碼</label>
                                        <general-input v-model="perUsage.showPage"
                                                       :errors="errors[`perUsages${index}ShowPage`]"/>
                                    </div>
                                </div>
                                <div class="column is-6">
                                    <div class="field">
                                        <label class="label">圖號</label>
                                        <general-input v-model="perUsage.figure"
                                                       :errors="errors[`perUsages${index}Figure`]"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="field">
                                <label class="label">文獻中學名寫法</label>
                                <general-input v-model="perUsage.customNameRemark"
                                               :errors="errors[`perUsages${index}.customNameRemark`]"/>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="button is-text"
                        v-on:click="onAddReference">
                    <i class="fa fa-plus-circle"></i>
                    &nbsp;增加文獻
                </button>
                <br/>
                <br/>
                <p class="title is-5">模式標本</p>
                <hr/>
                <template v-for="(typeSpecimen, index) in typeSpecimens">
                    <div class="box" style="padding-top: 2.5rem">
                        <a class="close-button"
                           v-on:click="() => typeSpecimens.splice(index, 1)">
                        </a>
                        <type-specimen :ref="`typespecimen_${index}`"
                                       v-model="typeSpecimens[index]"
                                       :errors="errors"
                                       :index="index"
                        />
                    </div>
                </template>
                <button class="button is-text" v-on:click="onAddTypeSpecimens">
                    <i class="fa fa-plus-circle"></i>
                    &nbsp;&nbsp;{{ $t('forms.taxonName.specimenType') }}
                </button>
                <br/>
                <br/>
                <br/>
            </div>
            <div class="column is-3">
                <div class="field">
                    <label class="label">建議寫法</label>
                    <div>
                        <usage-preview
                            ref="nameRemark"
                            :indications="properties.indications"
                            :per-usages="perUsages"
                            :status="status"
                            :taxon-name="taxonName"
                            :type-specimens="typeSpecimens"></usage-preview>
                    </div>
                </div>
                <br/>
                <br/>
                <div class="field">
                    <label class="label">文獻發表寫法</label>
                    <div class="control">
                        <textarea v-model="customNameRemark" class="textarea"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import BookSelect from '../selects/BookSelect';
    import GeneralInput from '../GeneralInput';
    import tSelect from '../Select';
    import ReferenceTypeSelect from '../selects/ReferenceTypeSelect';
    import PersonSelect from '../selects/PersonSelect';
    import LanguageSelect from '../selects/LanguageSelect';
    import { debounce, cloneDeep } from 'lodash';
    import StatusSelect from '../selects/StatusSelect';
    import ReferenceSelect from '../selects/ReferenceSelect';
    import IndicationSelect from '../selects/IndicationSelect';
    import TypeSpecimen from './TypeSpecimen';
    import { factory as rFactory } from '../../utils/preview/reference';
    import TaxonNameSelect from '../selects/TaxonNameSelect';
    import indicationOptions from '../selects/indications';
    import sexs from '../selects/sexs';
    import UsagePreview from '../UsagePreview';
    import { openNotify } from '../../utils';

    export default {
        props: {
            presetData: {
                type: Object,
            },
            onAfterSubmit: {
                type: Function,
                required: true,
            },
        },
        created() {
            this.taxonName = this.presetData.taxonName;
            this.parentTaxonName = this.presetData.parentTaxonName;
            this.status = this.presetData.status;
            this.nameRemark = this.presetData.nameRemark;
            this.customNameRemark = this.presetData.customNameRemark;
            this.properties = {
                ...this.presetData.properties,
                indications: indicationOptions.filter(i => this.presetData.properties?.indications?.includes(i.abbreviation)),
            };
            this.perUsages = this.presetData.perUsages || [];
            this.typeSpecimens = this.presetData?.typeSpecimens ? this.presetData?.typeSpecimens.map((t) => {
                t.sex = sexs.find(s => s.id === t.sexId);
                return t;
            }) : [];
            this.rCombo = rFactory(this.presetData?.taxonName?.nomenclature?.group);

            if (this.presetData.status === '') {
                this.status = 'accepted';
            }
        },
        computed: {
            taxonNameId() {
                return this.taxonName?.id;
            },
            parentTaxonNameId() {
                return this.parentTaxonName?.id;
            },
            tempCoverUrl() {
                return this.reference.cover ? window.URL.createObjectURL(this.reference.cover) : '';
            },
            formData() {
                return {
                    id: this.presetData.id,
                    taxonNameId: this.taxonNameId,
                    parentTaxonNameId: this.parentTaxonNameId,
                    status: this.status,
                    properties: {
                        indications: this.properties.indications.map(i => i.abbreviation),
                    },
                    perUsages: cloneDeep(this.perUsages).map(r => {
                        return {
                            referenceId: r.target?.id,
                            showPage: r.showPage,
                            figure: r.figure,
                            customNameRemark: r.customNameRemark,
                            proParte: r.proParte,
                        }
                    }),
                    typeSpecimens: cloneDeep(this.typeSpecimens).map(t => {
                        return {
                            ...t,
                            sexId: t.sex?.id,
                            countryId: t.country?.numericCode,
                            collectorIds: t.collectors.map(c => c.id),
                        };
                    }),
                    customNameRemark: this.customNameRemark,
                    nameRemark: this.$refs.nameRemark.$el.innerHTML,
                }
            },
        },
        data() {
            return {
                isLoading: true,
                taxonName: this.presetData.taxonName,
                parentTaxonName: this.presetData.parentTaxonName,
                status: null,
                properties: {
                    indications: [],
                },
                description: '',
                perUsages: [],
                typeSpecimens: [],
                errors: {},
                nameRemark: '',
                customNameRemark: '',
            }
        },
        methods: {
            onAddTypeSpecimens() {
                this.typeSpecimens.push({});
            },
            onAddReference() {
                this.perUsages.push({
                    target: null,
                    showPage: '',
                    figure: '',
                    nameRemark: '',
                    proParte: false,
                });
            },
            submit() {
                const { namespaceId, usageId } = this.$route.params;

                this.axios
                    .put(`namespaces/${namespaceId}/usages/${usageId}`, this.formData)
                    .then(() => {
                        this.isLoading = false;
                        openNotify(this.$t('forms.saveSuccess'));
                        this.onAfterSubmit(this.formData);
                    })
                    .catch(({ errors }) => {
                        this.errors = errors;
                    });
            },
        },
        components: {
            UsagePreview,
            TaxonNameSelect,
            TypeSpecimen,
            IndicationSelect,
            ReferenceSelect,
            LanguageSelect,
            GeneralInput,
            BookSelect,
            tSelect,
            ReferenceTypeSelect,
            PersonSelect,
            StatusSelect,
        },
    }

</script>
<style lang="scss" scoped>
    .form {
        height: 100%;

        .form-inner-container {
            margin-top: 0;
            height: calc(100% - 3rem);
        }
    }

    .left-line {
        border-right: 1px solid $light-grey;
        min-height: 100%;
        overflow-y: scroll;
    }
</style>
