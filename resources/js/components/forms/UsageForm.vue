<template>
    <div class="form">
        <div class="flex h-full">
            <div class="column left-line">
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" v-text="'上階層'"/>
                            <taxon-name-select v-model="parentTaxonName" :errors="errors['parentTaxonNameId']"/>
                        </div>
                    </div>
                    <div class="column is-2">
                        <div class="field">
                            <label class="label is-marked">
                                地位
                            </label>
                            <status-select v-model="status" :errors="errors['status']"/>
                        </div>
                    </div>
                    <div class="column is-4">
                        <div class="field">
                            <label :class="{'is-marked': status=== 'misapplied' || status === 'undetermined' }"
                                   class="label"
                                   v-text="`標註`"/>
                            <indication-select v-model="properties.indications"
                                               :errors="errors['propertiesIndications']"
                                               :status="status"
                                               :type="taxonName.nomenclature.group"/>
                        </div>
                    </div>
                </div>
                <br/>

                <!-- 文獻 -->
                <section>
                    <div class="title is-5">
                        文獻
                        <div class="is-pulled-right buttons">
                            <button :disabled="!taxonName.reference"
                                    :title="!taxonName.reference ? `尚無歸檔文獻` : ''"
                                    class="button is-outlined is-small"
                                    v-on:click="onAddPublishReference"
                            >
                                帶入發表文獻
                            </button>
                        </div>
                    </div>
                    <hr/>

                    <!-- 其他引用文獻 -->
                    <draggable v-bind="dragOptions" :list="perUsages"
                               class="per-usage-container"
                               data-type="per-usage"
                               tag="div"
                    >
                        <template v-for="(perUsage, index) in perUsages">
                            <per-usage-simple-card v-if="perUsagesIsSimpleViews[index]"
                                                   :errors="errors"
                                                   :index="index"
                                                   :on-collapse-per-usage="() => onCollapsePerUsage(index, false)"
                                                   :on-remove-per-usage="() => onRemovePerUsage(index)"
                                                   :per-usage="perUsage"
                                                   :taxon-name="taxonName"
                            />
                            <per-usage-form v-else
                                            :errors="errors"
                                            :index="index"
                                            :on-collapse-per-usage="() => onCollapsePerUsage(index, true)"
                                            :on-remove-per-usage="() => onRemovePerUsage(index)"
                                            :per-usage="perUsage"
                            />
                        </template>
                    </draggable>
                    <button class="button is-text"
                            v-on:click="onAddReference">
                        <i class="fa fa-plus-circle"></i>
                        &nbsp;增加文獻
                    </button>
                </section>

                <!-- 模式 --->
                <section>
                    <!-- 模式標本 --->
                    <template v-if="taxonName.rank.order > 30">
                        <div class="title is-5">
                            模式標本
                            <div class="is-pulled-right buttons">
                            </div>
                        </div>
                        <hr/>
                        <template v-for="(typeSpecimen, index) in typeSpecimens">
                            <template v-if="typeSpecimensIsSimpleViews[index]">
                                <div :key="`${index}_${typeSpecimensIsSimpleViews[index]}`"
                                     :class="{'is-danger': Object.keys(errors).find(i => !!i.match(`typeSpecimens${index}`))}"
                                     class="box is-simple"
                                >
                                    <a class="close-button is-pulled-right"
                                       v-on:click="() => onRemoveTypeSpecimen(index)">
                                    </a>
                                    <a class="is-pulled-right g-button"
                                       v-on:click="() => $set(typeSpecimensIsSimpleViews, index, false)">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    &nbsp;
                                    <span
                                        :class="{'has-text-danger': Object.keys(errors).find(i => !!i.match(`typeSpecimens${index}`))}"
                                        v-text="Scombo([typeSpecimen])"
                                    />
                                </div>
                            </template>
                            <template v-else>
                                <div class="box">
                                    <div class="buttons is-right">
                                        <a class="g-button"
                                           v-on:click="() => $set(typeSpecimensIsSimpleViews, index, true)">
                                            <i class="fas fa-minus"></i>
                                        </a>
                                        <a class="close-button"
                                           v-on:click="() => onRemoveTypeSpecimen(index)">
                                        </a>
                                    </div>
                                    <type-specimen :ref="`typespecimen_${index}`"
                                                   v-model="typeSpecimens[index]"
                                                   :errors="errors"
                                                   :index="index"
                                    />
                                </div>
                            </template>
                        </template>

                        <button class="button is-text" v-on:click="onAddTypeSpecimens">
                            <i class="fa fa-plus-circle"></i>
                            &nbsp;&nbsp;{{ $t('forms.taxonName.specimenType') }}
                        </button>
                    </template>

                    <!-- 模式學名 --->
                    <template v-else>
                        <h3 class="title is-5">{{ $t('forms.taxonName.typeName') }}</h3>
                        <hr/>
                        <div class="columns is-multiline">
                            <div class="column is-12">
                                <taxon-name-select v-model="typeName"/>
                            </div>
                        </div>
                    </template>
                </section>

                <!-- 屬性資訊 -->
                <section v-if="status === 'accepted'">
                    <div class="title is-5">屬性資訊</div>
                    <hr/>
                    <usage-additional-info-form :preset="properties" v-on:input="onUpdateAdditionalInfo"/>
                </section>
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
                            :type-name="typeName"
                            :type-specimens="typeSpecimens"
                        ></usage-preview>
                    </div>
                </div>
                <br/>
                <br/>
                <div class="field">
                    <label class="label">
                        文獻發表寫法
                        <button class="button is-small is-pulled-right"
                                type="button"
                                v-on:click="onCopyNameRemark">
                            <i class="fas fa-arrow-down"></i>&nbsp;複製建議寫法至此
                        </button>
                    </label>
                    <div class="control">
                        <textarea v-model="customNameRemark" class="textarea"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { cloneDeep } from 'lodash';
    import StatusSelect from '../selects/StatusSelect';
    import IndicationSelect from '../selects/IndicationSelect';
    import TypeSpecimen from './TypeSpecimen';
    import { factory as rFactory } from '../../utils/preview/reference';
    import TaxonNameSelect from '../selects/TaxonNameSelect';
    import indicationOptions from '../selects/indications';
    import sexs from '../selects/sexs';
    import UsagePreview from '../UsagePreview';
    import { openNotify } from '../../utils';
    import { combo as Scombo } from '../../utils/preview/typeSpecimen';
    import PerUsageForm from './PerUsageForm';
    import PerUsageSimpleCard from '../cards/PerUsageSimpleCard';
    import draggable from 'vuedraggable';
    import UsageAdditionalInfoForm from './UsageAdditionalInfoForm';

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
            this.rCombo = rFactory(this.presetData?.taxonName?.nomenclature?.group);
        },
        computed: {
            taxonNameId() {
                return this.taxonName?.id;
            },
            parentTaxonNameId() {
                return this.parentTaxonName?.id;
            },
            formData() {
                return {
                    id: this.presetData.id,
                    taxonNameId: this.taxonNameId,
                    parentTaxonNameId: this.parentTaxonNameId,
                    status: this.status,
                    properties: {
                        ...this.properties,
                        indications: this.properties.indications?.map(i => i.abbreviation),
                        typeName: this.typeName?.id,
                    },
                    perUsages: cloneDeep(this.perUsages).map(r => {
                        return {
                            referenceId: r.target?.id,
                            showPage: r.showPage,
                            figure: r.figure,
                            nameInReference: r.nameInReference,
                            proParte: r.proParte,
                            isFromPublishedRef: r.isFromPublishedRef,
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
            dragOptions() {
                return {
                    animation: 0,
                    group: {
                        name: 'per-usage',
                        pull: false,
                        put: ['favorite-reference']
                    },
                    disabled: false,
                    threshold: 0,
                    multiDrag: false,
                    selectedClass: 'selected',

                };
            },
        },
        data() {
            const indications = indicationOptions
                .filter(i => i.status === this.presetData.status)
                .filter(i => this.presetData.properties.indications?.includes(i.abbreviation));

            return {
                isLoading: true,

                typeSpecimensIsSimpleViews: Object.keys(this.presetData?.typeSpecimens).map(() => true),
                perUsagesIsSimpleViews: Object.keys(this.presetData?.perUsages).map(() => true),

                taxonName: this.presetData.taxonName,
                parentTaxonName: this.presetData.parentTaxonName || this.presetData.taxonName.species,
                status: this.presetData.status ?? null,
                properties: this.presetData?.properties ? {
                    ...this.presetData.properties,
                    indications,
                } : {},
                description: '',
                perUsages: this.presetData.perUsages || [],
                typeSpecimens: this.presetData?.typeSpecimens ? this.presetData?.typeSpecimens.map((t) => {
                    t.sex = sexs.find(s => s.id === t.sexId);
                    return t;
                }) : [],
                typeName: this.presetData.typeName ?? null,
                errors: {},
                nameRemark: this.presetData.nameRemark ?? '',
                customNameRemark: this.presetData.customNameRemark ?? '',
            }
        },
        watch: {
            presetData: {
                deep: true,
                handler(value) {
                    this.taxonName = value.taxonName;
                },
            },
        },
        methods: {
            Scombo: Scombo,
            onUpdateAdditionalInfo(v) {
                this.properties = { ...this.properties, ... v };
            },
            onCopyNameRemark() {
                this.customNameRemark = this.$refs.nameRemark.$el.innerText;
            },
            onAddTypeSpecimens() {
                this.typeSpecimens.push({});
                this.typeSpecimensIsSimpleViews.push(false);
            },
            onAddPublishReference() {
                this.perUsages.unshift({
                    target: this.taxonName.reference,
                    figure: this.taxonName.properties.usage?.figure,
                    showPage: this.taxonName.properties.usage?.showPage,
                    nameInReference: this.taxonName.properties.usage?.nameInReference,
                    proParte: false,
                    isFromPublishedRef: true,
                });
                this.perUsagesIsSimpleViews.push(true);
            },
            onAddReference() {
                this.perUsages.push({
                    target: null,
                    showPage: '',
                    figure: '',
                    nameRemark: '',
                    proParte: false,
                    isFromPublishedRef: false,
                });
                this.perUsagesIsSimpleViews.push(false);
            },
            onRemovePerUsage(index) {
                this.perUsagesIsSimpleViews.splice(index, 1);
                this.perUsages.splice(index, 1);
            },
            onRemoveTypeSpecimen(index) {
                this.typeSpecimensIsSimpleViews.splice(index, 1);
                this.typeSpecimens.splice(index, 1);
            },
            onCollapsePerUsage(index, status) {
                this.$set(this.perUsagesIsSimpleViews, index, status);
            },
            submit() {
                const { id:namespaceId, usageId } = this.$route.params;

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
            UsageAdditionalInfoForm,
            PerUsageSimpleCard,
            PerUsageForm,
            UsagePreview,
            TaxonNameSelect,
            TypeSpecimen,
            IndicationSelect,
            StatusSelect,
            draggable,
        },
    }

</script>
<style lang="scss" scoped>
    .form {
        section {
            margin-bottom: 1.5rem;
        }
    }

    .box {
        &.is-simple {
            border: 1px solid $light-grey;
        }

        &.is-danger {
            border: 1px solid $danger;
        }

        &.is-marked {
            border-left: 5px solid $orange;
        }
    }

    .per-usage-container {
        margin-bottom: 1rem;
    }

    .left-line {
        border-right: 1px solid $light-grey;
        overflow-y: auto;
    }
</style>
