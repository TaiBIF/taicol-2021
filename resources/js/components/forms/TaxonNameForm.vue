<template>
    <div class="form">
        <div class="hidden">
            <taxon-name-label class="" ref="preGenerateName" :taxon-name="{
                properties: {
                    latinName,
                    latinGenus,
                    latinS1,
                    isHybridFormula,
                },
                rank: targetRank,
                hybridParents,
                nomenclature: targetNomenclature,
                authors: targetAuthors,
                exAuthors: targetExAuthors,
                species,
                speciesLayers,
                originalTaxonName: targetOriginalTaxonName,
            }" />
        </div>

        <div class="columns">
            <!-- 命名規約 -->
            <div class="column is-4">
                <div class="field">
                    <label class="label is-marked">
                        {{ $t('forms.taxonName.nomenclature') }}
                        <b-tooltip multilined
                                   position="is-bottom">
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:content>
                                <template v-for="nomenclature in $store.state.nomenclature.items">
                                    {{ nomenclature.display['zh-tw'] }} = {{ nomenclature.name }}<br/>
                                </template>
                            </template>
                        </b-tooltip>
                    </label>
                    <nomenclature-select ref="nomenclatureSelect"
                                         v-model="targetNomenclature"
                                         :disabled="isEdit"
                                         :errors="errors.nomenclatureId"
                    />
                </div>
            </div>

            <!-- 階層 -->
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked">
                        {{ $t('forms.taxonName.rank') }}

                        <!-- 是否雜交 -->
                        <label v-if="isNeedHybridFormulaCheck" class="label is-pulled-right"
                               for="isHybridFormula">
                            <input id="isHybridFormula" v-model="isHybridFormula" class="checkbox"
                                   type="checkbox"/>
                            <span v-if="targetRank"
                                  v-text="targetRank.display ? $t('forms.taxonName.isHybridFormula') + targetRank.display['zh-tw'] : ''"/>
                        </label>
                    </label>
                    <rank-select ref="rankSelect"
                                 v-model="targetRank"
                                 :errors="errors.rankId"
                                 :options="rankOptions"
                                 :disabled="isEdit"
                    />
                </div>
            </div>

            <!-- 雜交親代 -->
            <div v-if="isNeedHybridFormula" class="column is-6">
                <div class="field ">
                    <label class="label"
                           :class="{'is-marked': targetRank && targetRank.key === 'hybrid-formula'}"
                           v-text="$t('forms.taxonName.hybridFormula')"/>
                    <div class="field has-addons">
                        <p class="control is-expanded">
                            <taxon-name-select v-model="hybridParents[0]" :errors="errors['hybridParents0']" :disabled="isEdit"/>
                        </p>
                        <div class="control">
                            &nbsp;
                            <i class="fa fa-times"></i>
                            &nbsp;
                        </div>
                        <p class="control is-expanded">
                            <taxon-name-select v-model="hybridParents[1]" :errors="errors['hybridParents1']" :disabled="isEdit"/>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="columns">

            <!--  若為「種」以上 -->
            <div v-if="isOverSpecies" class="column is-4">
                <div class="field">
                    <label class="label is-marked">
                        {{ targetRank.display ? targetRank.display['zh-tw'] : '' }}{{ $t('forms.taxonName.name') }}
                    </label>
                    <general-input v-model="latinName" :errors="errors['latinName']" :disabled="isEdit"/>
                </div>
            </div>

            <!--  若為「種」     -->
            <div v-else-if="isSpecies" class="column is-6">
                <div class="box">
                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('forms.taxonName.latinGenus')"/>
                                <general-input v-model="latinGenus" :errors="errors['latinGenus']" :disabled="isEdit"/>
                            </div>
                        </div>

                        <div v-if="targetRank && speciesRank && targetRank.order === speciesRank.order"
                             class="column is-6">
                            <div class="field">
                                <label class="label is-marked">
                                    {{ $t('forms.taxonName.latinS1') }}
                                </label>
                                <general-input v-model="latinS1" :errors="errors['latinS1']" :disabled="isEdit"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--  若為「種以下」  -->
            <div v-else-if="isUnderSpecies && targetRank && targetRank.key !== 'hybrid-formula'" class="column is-6">
                <div class="box">
                    <div class="columns">
                        <div class="column">
                            <label class="label is-marked" v-text="$t('forms.taxonName.species')"/>
                            <taxon-name-select v-model="species"
                                               :errors="errors['speciesId']"
                                               :disabled="isEdit" />
                        </div>
                    </div>
                    <div v-for="(speciesLayer, index) in speciesLayers" class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked"
                                       v-text="$t('forms.taxonName.sRank', {s: $t('forms.taxonName.s').repeat(index + 1)})"/>
                                <rank-select v-model="speciesLayer.rank"
                                             :errors="errors[`speciesLayers${index}RankAbbreviation`]"
                                             :options="rankOptions.filter(rank => rank.order > speciesRank.order && rank.order <= targetRank.order)"
                                             :disabled="isEdit"/>
                            </div>
                        </div>

                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked"
                                       v-text="$t('forms.taxonName.sLatin',{s: $t('forms.taxonName.s').repeat(index + 1)}) "/>
                                <general-input v-model="speciesLayer.latinName"
                                               :errors="errors[`speciesLayers${index}LatinName`]"
                                               :disabled="isEdit"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column">
                            <div class="button is-small" v-if="!isEdit"
                                 v-on:click="onAddSpeciesLayer">
                                <i class="fa fa-plus"></i>&nbsp;
                                {{
                                    $t('forms.taxonName.addSName', { s: $t('forms.taxonName.s').repeat(speciesLayers.length + 1) })
                                }}
                            </div>
                        </div>
                        <div class="column">
                            <div v-if="speciesLayers.length && !isEdit"
                                 class="button is-small is-pulled-right"
                                 v-on:click="onDeleteSpeciesLayer">
                                <i class="fa fa-trash"></i>&nbsp;
                                {{
                                    $t('forms.taxonName.deleteSName', { s: $t('forms.taxonName.s').repeat(speciesLayers.length) })
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--  學名發表日期 -->
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.reference.publishYear')"/>
                    <general-input v-model="publishYear" :errors="errors.publishYear"/>
                </div>
            </div>
        </div>

        <!--  增加原始組合名 -->
        <div class="columns">
            <div v-if="isNeedOriginal" class="column is-4">
                <div class="field">
                    <label class="label">
                        {{ $t(`forms.taxonName.originalName.${targetNomenclature.settings.keyOfOriginalName}`) }}
                    </label>
                    <div class="control">
                        <taxon-name-select v-model="targetOriginalTaxonName"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label">
                        {{
                            $t(`forms.taxonName.author.${targetNomenclature ? targetNomenclature.settings.keyOfAuthors :
                                'authors'}`)
                        }}
                    </label>
                    <person-select
                        v-model="targetAuthors"
                        :errors="errors.authors"
                        :group="targetNomenclature ? targetNomenclature.group : ''"
                    />
                </div>
            </div>
        </div>

        <!-- 前述者/提出此名者 -->
        <div class="columns" v-if="isNeedExAuthors">
            <div class="column is-6">
                <div class="field">
                    <label class="label">
                        {{ $t('forms.taxonName.exAuthor') }}
                        <b-tooltip
                            :label="$t('forms.taxonName.authorInfo')"
                            class="is-pulled-right"
                            position="is-bottom">
                            <i class="fas fa-info-circle"></i>
                        </b-tooltip>
                    </label>

                    <person-select
                        v-model="targetExAuthors"
                        :errors="errors.exAuthors"
                        :group="targetNomenclature ? targetNomenclature.group : ''"
                    />
                </div>
            </div>
        </div>

        <!-- 文獻 -->
        <div>
            <p class="subtitle is-4">
                發表文獻
                <span class="ml-2 text-base">(擇一填入)</span>
            </p>
            <hr/>
        </div>
        <reference-container
            ref="publishedUsage"
            :errors="errors"
            :reference-name.sync="referenceName"
            :type="targetNomenclature ? targetNomenclature.group : ''"
            :usage.sync="usage">
        </reference-container>

        <!-- 模式標本 -->
        <br/>
        <template v-if="targetRank && targetRank.order > genusRank.order">
            <template v-if="typeSpecimens.length">
                <h3 class="subtitle is-4">模式標本</h3>
                <hr/>
                <div class="columns is-multiline">
                    <div v-for="(typeSpecimen, index) in typeSpecimens" class="column is-6">
                        <div class="box">
                            <a class="close-button is-pulled-right"
                               v-on:click="(index) => typeSpecimens.splice(index, 1)">
                            </a>
                            <br/><br/>
                            <type-specimen :ref="`typespecimen_${index}`"
                                           v-model="typeSpecimens[index]"
                                           :errors="errors"
                                           :index="index"
                                           :is-show-is-pointed="false"
                            />
                        </div>
                    </div>
                </div>
            </template>
            <div class="columns">
                <div class="column is-12">
                    <button class="button is-text" v-on:click="onAddTypeSpecimens">
                        <i class="fa fa-plus-circle"></i>
                        &nbsp;&nbsp;{{ $t('forms.taxonName.specimenType') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- 模式學名 --->
        <template v-else>
            <h3 class="subtitle is-4">&nbsp;&nbsp;{{ $t('forms.taxonName.typeName') }}</h3>
            <hr/>
            <div class="columns is-multiline">
                <div class="column is-6">
                    <taxon-name-select v-model="typeName"></taxon-name-select>
                </div>
            </div>
        </template>

        <!-- 備註 -->
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label id="note" class="label">
                        備註
                    </label>
                    <textarea v-model="note" class="textarea" for="note"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import PersonSelect from '../selects/PersonSelect';
    import NomenclatureSelect from '../selects/NomenclatureSelect';
    import GeneralInput from '../GeneralInput';
    import RankSelect from '../selects/RankSelect';
    import TaxonNameSelect from '../selects/TaxonNameSelect';
    import ReferenceSelect from '../selects/ReferenceSelect';
    import TypeSpecimen from './TypeSpecimen';
    import { debounce } from 'lodash';
    import { openNotify } from './../../utils';
    import SimpleReferenceView from '../views/SimpleReferenceView';
    import sexs from '../selects/sexs';
    import { factory } from '../../utils/preview/person';
    import ReferenceContainer from '../ReferenceContainer';
    import { mapGetters } from 'vuex';
    import TaxonNameLabel from "../views/TaxonNameLabel";

    export default {
        props: {
            presetData: Object,
            onAfterSubmit: {
                type: Function,
                required: true,
            },
        },
        data() {
            const presetData = this.presetData;
            const typeSpecimens = this.presetData?.typeSpecimens.map((t) => {
                return {
                    ...t,
                    sex: sexs.find(s => s.id === t.sexId),
                }
            });

            return {
                id: presetData?.id || null,
                targetNomenclature: presetData?.nomenclature || null,
                targetRank: presetData?.rank || null,
                targetReference: presetData?.usage?.reference || null,
                targetAuthors: presetData?.authors || [],
                targetExAuthors: presetData?.exAuthors || [],
                errors: {},
                species: presetData?.species || null,
                publishYear: presetData?.publishYear || '',
                rankOptions: this.$store.state.nomenclature.items.find(n => n.id === presetData?.nomenclature.id)?.ranks ?? [],
                targetOriginalTaxonName: presetData?.originalTaxonName,

                latinName: presetData?.properties?.latinName || '',
                latinGenus: presetData?.properties?.latinGenus || '',
                latinS1: presetData?.properties?.latinS1 || '',

                isHybridFormula: presetData?.properties?.isHybridFormula,
                hybridParents: presetData?.hybridParents || [],
                typeSpecimens: typeSpecimens || [],
                typeName: presetData?.typeName,
                speciesLayers: presetData?.speciesLayers || [],
                exAuthorYear: presetData?.exAuthorYear,
                note: presetData?.note || '',

                // 發表文獻
                usage: {
                    target: presetData?.reference,
                    nameInReference: presetData?.properties?.usage?.nameInReference,
                    showPage: presetData?.properties?.usage?.showPage,
                    figure: presetData?.properties?.usage?.figure,
                },
                referenceName: presetData?.properties?.referenceName || '',
            }
        },
        computed: {
            ...mapGetters({
                genusRank: 'rank/getGenusRank',
                speciesRank: 'rank/getSpeciesRank',
            }),
            isEdit() {
                return !!this.id;
            },
            isNeedHybridFormulaCheck() {
                // 階層: (種以上)「屬」,(種)「種」有勾選 Hybrid
                if (!this.targetRank) {
                    return false;
                }

                return this.targetRank.key === this.speciesRank.key || this.targetRank.key === this.genusRank.key;
            },
            isNeedHybridFormula() {
                // 階層: (種以上)「屬」有勾選 Hybrid, (種)「種」有勾選 Hybrid, (雜交組合)
                return this.targetRank?.key === 'hybrid-formula' || this.isHybridFormula;
            },
            isNeedOriginal() {
                if (!this.targetRank) {
                    return false;
                }

                return this.targetRank.order >= this.speciesRank.order;
            },
            isNeedExAuthors() {
                return this.targetNomenclature?.group === 'plant';
            },
            isOverSpecies() {
                return this.targetRank?.order < this.speciesRank.order;
            },
            isSpecies() {
                return this.targetRank?.key === 'species';
            },
            isUnderSpecies() {
                return this.targetRank?.order > this.speciesRank.order;
            },
            formattedAuthors() {
                if (!this.targetNomenclature) {
                    return '';
                }
                const group = this.targetNomenclature.group;

                if (group === 'animal') {
                    return factory(group)(this.targetAuthors);
                } else if (group === 'plant') {
                    /**
                     * 植物--前述者/提出此名者(原始組合名)
                     * name_ex_authors.[author_name_abbr] ex name_authors.[author_name_abbr]
                     */
                    if (this.targetOriginalTaxonName === null) {
                        return [
                            factory(group)(this.targetExAuthors),
                            factory(group)(this.targetAuthors),
                        ].filter(Boolean).join(' ex ');
                    } else {
                        /**
                         * 植物--括號內的前述者/提出此名者(非原始組合名)
                         * (基礎名的name_ex_authors.[author_name_abbr] ex name_authors.[author_name_abbr]) author
                         */
                        const originalAuthors = [
                            factory(group)(this.targetOriginalTaxonName?.authors || []),
                            factory(group)(this.targetOriginalTaxonName?.exAuthors || []),
                        ].filter(Boolean).join(' ex ');

                        return [
                            originalAuthors ? `(${originalAuthors})` : '',
                            factory(group)(this.targetAuthors),
                        ].filter(Boolean).join(' ')
                    }
                }
            },
            formData() {
                return {
                    id: this.presetData?.id || null,
                    name: this.$refs['preGenerateName'].$el.innerText,
                    formattedAuthors: this.formattedAuthors,
                    formattedExAuthors: this.formattedExAuthors,
                    latinName: this.latinName,
                    latinGenus: this.latinGenus,
                    latinS1: this.latinS1,
                    nomenclatureId: this.targetNomenclature?.id || null,
                    rankId: this.targetRank?.id || null,
                    authors: this.targetAuthors.map(a => a.id),
                    exAuthors: this.targetExAuthors.map(a => a.id),
                    speciesLayers: this.speciesLayers.map(t => ({
                        rankAbbreviation: t.rank?.abbreviation || null,
                        latinName: t.latinName,
                    })),
                    speciesId: this.species?.id,
                    isHybridFormula: this.isHybridFormula,
                    typeName: this.typeName ? this.typeName.id : null,
                    typeSpecimens: this.typeSpecimens.map(t => {
                        if (t.sex) {
                            t.sexId = t.sex.id;
                        }

                        if (t.collectors.length) {
                            t.collectorsId = t.collectors.map(c => c.id);
                        }

                        if (t.country) {
                            t.countryId = t.country.numericCode;
                        }

                        return { ...t };
                    }),
                    hybridParents: [
                        this.hybridParents[0] ?? null,
                        this.hybridParents[1] ?? null,
                    ],
                    publishYear: this.publishYear,
                    originalTaxonNameId: this.targetOriginalTaxonName?.id || null,
                    exAuthorYear: this.exAuthorYear || '',
                    note: this.note,

                    // 發布文獻
                    usage: {
                        referenceId: this.usage?.target?.id,
                        showPage: this.usage.showPage,
                        figure: this.usage.figure,
                        nameInReference: this.usage.nameInReference,
                    },
                    referenceName: this.$refs.publishedUsage.referenceName,
                }
            },
        },
        watch: {
            targetOriginalTaxonName(taxonName) {
                if (this.targetRank === 'species') {
                    this.latinS1 = taxonName.latinS1;
                    this.latinGenus = taxonName.latinGenus;
                }
            },
            targetRank() {
                this.latinGenus = '';
                this.latinS1 = '';
            },
            targetNomenclature: {
                handler(nomenclature) {
                    this.targetRank = null;
                    this.rankOptions = nomenclature?.ranks;
                },
                deep: true,
            },
        },
        methods: {
            onAddTypeSpecimens() {
                const index = this.typeSpecimens.length;
                this.typeSpecimens.push({});
                this.$nextTick()
                    .then(() => {
                        const element = this.$refs['typespecimen_' + index];
                        this.typeSpecimens[index] = element[0].$data.form;
                        element[0].$el.scrollIntoView({
                            behavior: 'smooth',
                            inline: 'start',
                        });
                    })
            },
            onAddSpeciesLayer() {
                return this.speciesLayers.push({
                    rank: null,
                    latinName: '',
                });
            },
            onDeleteSpeciesLayer() {
                return this.speciesLayers.splice(-1);
            },
            submit: debounce(function (isPublish) {
                const isEdit = !!this.presetData?.id;
                this.axios({
                    method: isEdit ? 'PUT' : 'POST',
                    url: isEdit ? `/taxon-names/${this.presetData.id}` : '/taxon-names',
                    data: { ...this.formData, isPublish },
                }).then(({ data }) => {
                    this.onAfterSubmit(data);
                    openNotify(this.$t('forms.saveSuccess'));
                }).catch(({ status, errors }) => {
                    this.errors = errors;
                });
            }),
        },
        components: {
            TaxonNameLabel,
            ReferenceContainer,
            SimpleReferenceView,
            ReferenceSelect,
            TaxonNameSelect,
            RankSelect,
            GeneralInput,
            NomenclatureSelect,
            PersonSelect,
            TypeSpecimen,
        },
    }
</script>
