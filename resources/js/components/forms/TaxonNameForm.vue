<template>
    <div class="form">
        <div class="hidden">
            <taxon-name-label v-if="targetRank && targetNomenclature" ref="preGenerateName" :taxon-name="{
                properties: {
                    latinName,
                    latinGenus,
                    latinS1,
                    isHybrid,
                },
                rank: targetRank,
                hybridParents,
                nomenclature: targetNomenclature,
                authors: targetAuthors,
                exAuthors: targetExAuthors,
                species,
                speciesLayers,
                originalTaxonName: targetOriginalTaxonName,
            }"/>
        </div>

        <div class="columns">
            <!-- 命名規約 -->
            <div class="column is-4">
                <div class="field">
                    <label class="label is-marked inline-block">
                        {{ $t('taxonName.nomenclature') }}

                        <tooltip>
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:body>
                                <div class="w-[220px]">
                                    <template v-for="nomenclature in $store.state.nomenclature.items">
                                        {{ nomenclature.display[$i18n.locale()] }} = {{ nomenclature.name }}<br/>
                                    </template>
                                </div>
                            </template>
                        </tooltip>
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
                        {{ $t('taxonName.rank') }}

                        <!-- 是否雜交 -->
                        <label v-if="isNeedHybridFormulaCheck" class="label is-pulled-right"
                               for="isHybrid">
                            <input id="isHybrid" v-model="isHybrid" class="checkbox"
                                   type="checkbox"/>
                            <span v-if="targetRank"
                                  v-text="
                                  targetRank.display ?
                                    $t('taxonName.isHybridFormula', {rank: $i18n.locale === 'zh-tw' ? targetRank.display['zh-tw'] : ''}) : ''
                                  "
                            />
                        </label>
                    </label>
                    <rank-select ref="rankSelect"
                                 v-model="targetRank"
                                 :disabled="isEdit"
                                 :errors="errors.rankId"
                                 :options="rankOptions"
                    />
                </div>
            </div>

            <!-- 雜交親代 -->
            <div v-if="isNeedHybridFormula" class="column is-6">
                <div class="field ">
                    <label :class="{'is-marked': targetRank && targetRank.key === 'hybrid-formula'}"
                           class="label"
                           v-text="$t('taxonName.hybridFormula')"/>
                    <div class="field has-addons">
                        <p class="control is-expanded">
                            <taxon-name-select v-model="hybridParents[0]"
                                               :disabled="isEdit && targetRank.key === 'hybrid-formula'"
                                               :errors="errors['hybridParentsId0']"/>
                        </p>
                        <div class="control">
                            &nbsp;
                            <i class="fa fa-times"></i>
                            &nbsp;
                        </div>
                        <p class="control is-expanded">
                            <taxon-name-select v-model="hybridParents[1]"
                                               :disabled="isEdit && targetRank.key === 'hybrid-formula'"
                                               :errors="errors['hybridParentsId1']"/>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isNeedApprovedList" class="columns">
            <div class="column is-4">
                <label class="ml-3">
                    <input v-model="isApprovedList" class="checkbox" type="checkbox"/>
                    {{ $t('taxonName.isApprovedList') }}
                </label>
            </div>
        </div>
        <div class="columns">

            <!--  若為「種」以上 -->
            <div v-if="isOverSpecies" class="column is-4">
                <div class="field">
                    <label class="label is-marked">
                        {{ targetRank.display ? targetRank.display[$i18n.locale()] : '' }} {{ $t('taxonName.name') }}
                    </label>
                    <general-input v-model="latinName" :disabled="isEdit" :errors="errors['latinName']"/>
                </div>
            </div>

            <!--  若為「種」     -->
            <div v-else-if="isSpecies" class="column is-6">
                <div class="box">
                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('taxonName.latinGenus')"/>
                                <general-input v-model="latinGenus" :disabled="isEdit" :errors="errors['latinGenus']"/>
                            </div>
                        </div>

                        <div v-if="targetRank && speciesRank && targetRank.order === speciesRank.order"
                             class="column is-6">
                            <div class="field">
                                <label class="label is-marked">
                                    {{ $t('taxonName.latinS1') }}
                                </label>
                                <general-input v-model="latinS1" :disabled="isEdit" :errors="errors['latinS1']"/>
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
                            <label class="label is-marked" v-text="$t('taxonName.species')"/>
                            <taxon-name-select v-model="species"
                                               :disabled="isEdit"
                                               :errors="errors['speciesId']"/>
                        </div>
                    </div>
                    <div v-for="(speciesLayer, index) in speciesLayers" class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked"
                                       v-text="$t(
                                           'taxonName.sRank',
                                           {s: $t('taxonName.s').repeat(index + 1)},
                                           index >= 1 ? 0 : 1,
                                           )"
                                />
                                <rank-select v-model="speciesLayer.rank"
                                             :disabled="isEdit"
                                             :errors="errors[`speciesLayers${index}RankAbbreviation`]"
                                             :options="rankOptions
                                                .filter(rank =>
                                                    rank.order > speciesRank.order && rank.order >= targetRank.order
                                                )
                                "/>
                            </div>
                        </div>

                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked"
                                       v-text="$t(
                                           'taxonName.sLatin',
                                           {s: $t('taxonName.s').repeat(index + 1)},
                                           index >= 1 ? 0 : 1,
                                       ) "/>
                                <general-input v-model="speciesLayer.latinName"
                                               :disabled="isEdit"
                                               :errors="errors[`speciesLayers${index}LatinName`]"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column">
                            <div v-if="!isEdit && !isOverSpeciesLayer" class="button is-small"
                                 v-on:click="onAddSpeciesLayer">
                                <i class="fa fa-plus"></i>&nbsp;
                                {{
                                    $t(
                                        'taxonName.addSName',
                                        {s: $t('taxonName.s').repeat(speciesLayers.length + 1)},
                                        speciesLayers.length >= 1 ? 0 : 1,
                                    )
                                }}
                            </div>
                        </div>
                        <div class="column">
                            <div v-if="speciesLayers.length && !isEdit"
                                 class="button is-small is-pulled-right"
                                 v-on:click="onDeleteSpeciesLayer">
                                <i class="fa fa-trash"></i>&nbsp;
                                {{
                                    $t(
                                        'taxonName.deleteSName',
                                        {s: $t('taxonName.s').repeat(speciesLayers.length)},
                                        speciesLayers.length >= 2 ? 0 : 1,
                                    )
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--  學名發表日期 -->
            <div v-if="isNeedPublishYear" class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('reference.publishYear')"/>
                    <general-input v-model="publishYear" :errors="errors.publishYear"/>
                </div>
            </div>
        </div>

        <!-- 作者 -->
        <template v-if="isNeedOriginal || isNeedAuthors || isNeedExAuthors || isNeedInitialYear">
            <div>
                <p class="subtitle is-4">
                    {{
                        targetNomenclature ?
                            $t(`taxonName.author.${targetNomenclature.settings.keyOfAuthors}`) :
                            $t(`taxonName.authors`)
                    }}
                    <span class="ml-2 text-base">{{ $t('taxonName.referenceChoseOneFillIn') }}</span>
                </p>
                <hr/>
            </div>
            <p class="subtitle mb-3"> • {{ $t('taxonName.linkToAuthors') }}</p>
            <div class="box">
                <!--  增加原始組合名 -->
                <div class="columns">
                    <div v-if="isNeedOriginal" class="column is-4">
                        <div class="field">
                            <label class="label">
                                {{ $t(`taxonName.originalName.${targetNomenclature.settings.keyOfOriginalName}`) }}
                            </label>
                            <div class="control">
                                <taxon-name-select v-model="targetOriginalTaxonName"
                                                   :errors="errors['originalTaxonNameId']"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isNeedAuthors" class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label">
                                {{
                                    targetNomenclature ?
                                        $t(`taxonName.author.${targetNomenclature.settings.keyOfAuthors}`) :
                                        $t(`taxonName.authors`)
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
                <div v-if="isNeedExAuthors" class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label">
                                {{ $t('taxonName.exAuthor') }}
                                <tooltip class="float-right">
                                    <i class="fas fa-info-circle"></i>
                                    <template v-slot:body>
                                        <div class="w-[300px]">{{ $t('taxonName.authorInfo') }}</div>
                                    </template>
                                </tooltip>
                            </label>

                            <person-select
                                v-model="targetExAuthors"
                                :errors="errors.exAuthors"
                                :group="targetNomenclature ? targetNomenclature.group : ''"
                            />
                        </div>
                    </div>

                    <div v-if="isNeedInitialYear" class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('taxonName.exAuthorYear')"/>
                            <general-input v-model="initialYear" :errors="errors.initialYear"/>
                        </div>
                    </div>
                </div>
            </div>

            <p class="subtitle mb-3"> • {{ $t('taxonName.saveAsPlainText') }}</p>
            <div class="box">
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label">{{ $t('taxonName.authors') }}
                                <small>{{ $t('taxonName.authorNotApplyInfo') }}</small>
                            </label>
                            <general-input v-model="authorsNameAuto"
                                           v-if="!!(targetAuthors.length || targetExAuthors.length || !!targetOriginalTaxonName)"
                                           :disabled="true"
                            />
                            <general-input v-model="authorsName"
                                           v-else
                                           :errors="errors.authorsName"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- 文獻 -->
        <template v-if="isNeedReference">
            <div>
                <p class="subtitle is-4">
                    {{ $t('taxonName.publishedIn') }}
                    <span class="ml-2 text-base">{{ $t('taxonName.referenceChoseOneFillIn') }}</span>
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
        </template>

        <!-- 模式標本 -->
        <template v-if="isNeedType">
            <br/>
            <template v-if="targetRank && targetRank.order >= speciesRank.order">
                <template v-if="typeSpecimens.length">
                    <h3 class="subtitle is-4">{{ $t('taxonName.specimenType') }}</h3>
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
                            &nbsp;&nbsp;{{ $t('taxonName.specimenType') }}
                        </button>
                    </div>
                </div>
            </template>

            <!-- 模式學名 --->
            <template v-else>
                <h3 class="subtitle is-4">&nbsp;&nbsp;{{ $t('taxonName.typeName') }}</h3>
                <hr/>
                <div class="columns is-multiline">
                    <div class="column is-6">
                        <taxon-name-select v-model="typeName"></taxon-name-select>
                    </div>
                </div>
            </template>
        </template>

        <div v-if="isNeedVirusColumns" class="columns">
            <!--  遺傳組成(病毒) -->
            <div class="column is-4">
                <div class="field">
                    <label class="label" v-text="$t('taxonName.genomeComposition')"/>
                    <genome-composition-select v-model="genomeComposition"
                                               :errors="errors.genomeComposition"></genome-composition-select>
                </div>
            </div>

            <!--  寄主/來源(病毒) -->
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('taxonName.host')"/>
                    <general-input v-model="host" :errors="errors.host"/>
                </div>
            </div>
        </div>

        <!-- 備註 -->
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label id="note" class="label">
                        {{ $t('taxonName.note') }}
                    </label>
                    <textarea v-model="note" class="textarea" for="note"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { debounce } from 'lodash';
import { mapGetters } from 'vuex';
import PersonSelect from '../selects/PersonSelect.vue';
import NomenclatureSelect from '../selects/NomenclatureSelect.vue';
import GeneralInput from '../GeneralInput.vue';
import RankSelect from '../selects/RankSelect.vue';
import TaxonNameSelect from '../selects/TaxonNameSelect.vue';
import ReferenceSelect from '../selects/ReferenceSelect.vue';
import TypeSpecimen from './TypeSpecimen.vue';
import { openNotify } from '../../utils';
import SimpleReferenceView from '../views/SimpleReferenceView.vue';
import sexs from '../selects/sexs';
import { authorNameStringFactory, factory } from '../../utils/preview/person';
import ReferenceContainer from '../ReferenceContainer.vue';
import TaxonNameLabel from '../views/TaxonNameLabel.vue';
import Tooltip from '../Tooltip.vue';
import GenomeCompositionSelect from '../selects/genomeCompositionSelect.vue';

export default {
    props: {
        presetData: Object,
        onAfterSubmit: {
            type: Function,
            required: true,
        },
    },
    data() {
        const { presetData } = this;
        const typeSpecimens = this.presetData?.typeSpecimens.map((t) => ({
            ...t,
            sex: sexs.find((s) => s.id === t.sexId),
        }));

        return {
            id: presetData?.id || null,
            targetNomenclature: presetData?.nomenclature || null,
            targetRank: presetData?.rank || null,
            targetReference: presetData?.usage?.reference || null,
            targetAuthors: presetData?.authors || [],
            targetExAuthors: presetData?.exAuthors || [],
            isApprovedList: presetData?.properties?.isApprovedList || false,
            errors: {},
            species: presetData?.species || null,
            publishYear: presetData?.publishYear || '',
            initialYear: presetData?.properties?.initialYear || '',
            rankOptions: this.$store.state.nomenclature.items
                .find((n) => n.id === presetData?.nomenclature.id)?.ranks ?? [],
            targetOriginalTaxonName: presetData?.originalTaxonName,

            latinName: presetData?.properties?.latinName || '',
            latinGenus: presetData?.properties?.latinGenus || '',
            latinS1: presetData?.properties?.latinS1 || '',

            isHybrid: presetData?.properties?.isHybrid,
            hybridParents: presetData?.hybridParents || [],
            typeSpecimens: typeSpecimens || [],
            typeName: presetData?.typeName,
            speciesLayers: presetData?.speciesLayers || [],
            note: presetData?.note || '',

            // 發表文獻
            usage: {
                target: presetData?.reference,
                nameInReference: presetData?.properties?.usage?.nameInReference,
                showPage: presetData?.properties?.usage?.showPage,
                figure: presetData?.properties?.usage?.figure,
            },
            referenceName: presetData?.properties?.referenceName || '',
            authorsName: presetData?.properties?.authorsName || '',

            genomeComposition: presetData?.properties?.genomeComposition || '',
            host: presetData?.properties?.host || '',
        };
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
            if (this.targetNomenclature?.group === 'bacteria' || this.targetNomenclature?.group === 'virus') {
                return false;
            }

            // 階層: (種以上)「屬」,(種)「種」有勾選 Hybrid
            if (!this.targetRank) {
                return false;
            }

            return this.targetRank.key === this.speciesRank.key || this.targetRank.key === this.genusRank.key;
        },
        isNeedHybridFormula() {
            if (this.targetNomenclature?.group === 'bacteria') {
                return false;
            }

            // 階層: (種以上)「屬」有勾選 Hybrid, (種)「種」有勾選 Hybrid, (雜交組合)
            return this.targetRank?.key === 'hybrid-formula' || this.isHybrid;
        },
        isNeedOriginal() {
            if (!this.targetRank) {
                return false;
            }

            if (this.targetNomenclature?.group === 'virus') {
                return false;
            }

            return this.targetRank.order >= this.speciesRank.order;
        },
        isNeedApprovedList() {
            return this.targetNomenclature?.group === 'bacteria';
        },
        isNeedInitialYear() {
            return this.targetNomenclature?.group === 'bacteria';
        },
        isNeedAuthors() {
            return this.targetNomenclature?.group !== 'virus';
        },
        isNeedExAuthors() {
            return this.targetNomenclature?.group === 'plant' || this.targetNomenclature?.group === 'bacteria';
        },
        isNeedReference() {
            return this.targetNomenclature?.group !== 'virus';
        },
        isNeedType() {
            return this.targetNomenclature?.group !== 'virus';
        },
        isOverSpecies() {
            if (this.targetNomenclature?.group === 'virus' && this.targetRank?.key === 'species') {
                return true;
            }

            return this.targetRank?.order < this.speciesRank.order;
        },
        isSpecies() {
            return this.targetRank?.key === 'species';
        },
        isUnderSpecies() {
            return this.targetRank?.order > this.speciesRank.order;
        },
        isOverSpeciesLayer() {
            if (this.targetNomenclature?.group === 'bacteria' && this.speciesLayers.length === 1) {
                return true;
            }
            return this.speciesLayers.length >= 4;
        },
        isNeedVirusColumns() {
            return this.targetNomenclature?.group === 'virus';
        },
        isNeedPublishYear() {
            return this.targetNomenclature?.group !== 'virus';
        },
        authorNames() {
            return this.targetAuthors.map((a) => a.name);
        },
        formattedAuthors() {
            if (!this.targetNomenclature) {
                return '';
            }
            const { group } = this.targetNomenclature;

            if (group === 'animal') {
                return factory(group)(this.targetAuthors);
            }
            if (group === 'plant') {
                /**
                 * 植物--前述者/提出此名者(原始組合名)
                 * name_ex_authors.[author_name_abbr] ex name_authors.[author_name_abbr]
                 */
                if (this.targetOriginalTaxonName === null) {
                    return [
                        factory(group)(this.targetExAuthors),
                        factory(group)(this.targetAuthors),
                    ].filter(Boolean).join(' ex ');
                }
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
                ].filter(Boolean).join(' ');
            }
            return '';
        },
        authorsNameAuto() {
            if (this.targetAuthors.length === 0 && this.targetExAuthors.length === 0 && !this.targetOriginalTaxonName) {
                return '';
            }

            return this.targetNomenclature?.group ? authorNameStringFactory(
                this.targetNomenclature?.group,
                this.targetAuthors,
                this.targetExAuthors,
                this.targetOriginalTaxonName,
                {
                    species: this.species,
                    properties: {
                        latinGenus: this.latinGenus,
                    },
                },
                this.publishYear,
            ) : '';
        },
        formData() {
            return {
                id: this.presetData?.id || null,
                name: this.$refs.preGenerateName.$el.innerText,
                formattedAuthors: this.formattedAuthors,
                formattedExAuthors: this.formattedExAuthors,
                latinName: this.latinName,
                latinGenus: this.latinGenus,
                latinS1: this.latinS1,
                nomenclatureId: this.targetNomenclature?.id || null,
                rankId: this.targetRank?.id || null,
                authors: this.targetAuthors.map((a) => a.id),
                exAuthors: this.targetExAuthors.map((a) => a.id),
                isApprovedList: this.isApprovedList || false,
                speciesLayers: this.speciesLayers.map((t) => ({
                    rankAbbreviation: t.rank?.abbreviation || null,
                    latinName: t.latinName,
                })),
                speciesId: this.species?.id,
                isHybrid: this.isHybrid,
                typeName: this.typeName ? this.typeName.id : null,
                typeSpecimens: this.typeSpecimens.map((t) => {
                    if (t.sex) {
                        t.sexId = t.sex.id;
                    }

                    if (t.collectors.length) {
                        t.collectorsId = t.collectors.map((c) => c.id);
                    }

                    if (t.country) {
                        t.countryId = t.country.numericCode;
                    }

                    return { ...t };
                }),
                hybridParentsId: [
                    this.hybridParents[0]?.id ?? null,
                    this.hybridParents[1]?.id ?? null,
                ],
                publishYear: this.publishYear,
                initialYear: this.initialYear,
                originalTaxonNameId: this.targetOriginalTaxonName?.id || null,
                note: this.note,

                // 發布文獻
                usage: {
                    referenceId: this.usage?.target?.id,
                    showPage: this.usage.showPage,
                    figure: this.usage.figure,
                    nameInReference: this.usage.nameInReference,
                },
                referenceName: this.$refs.publishedUsage?.referenceName ?? '',
                authorsName: this.authorsNameAuto ? '' : this.authorsName,

                genomeComposition: this.genomeComposition || '',
                host: this.host || '',
            };
        },
    },
    watch: {
        targetOriginalTaxonName(taxonName) {
            if (this.targetRank === 'species') {
                this.latinS1 = taxonName.latinS1;
                this.latinGenus = taxonName.latinGenus;
            }
        },
        targetRank(v) {
            this.latinGenus = '';
            this.latinS1 = '';

            if (v.key === 'hybrid-formula') {
                this.isHybrid = true;
            }
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
                    const element = this.$refs[`typespecimen_${index}`];
                    this.typeSpecimens[index] = element[0].$data.form;
                    element[0].$el.scrollIntoView({
                        behavior: 'smooth',
                        inline: 'start',
                    });
                });
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
                openNotify(this.$t('common.saveSuccess'));
            }).catch(({ status, errors }) => {
                if (status === 409) {
                    openNotify('學名已存在', 'is-danger');
                } else {
                    this.errors = errors;
                }
            });
        }),
    },
    components: {
        GenomeCompositionSelect,
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
        Tooltip,
    },
};
</script>
