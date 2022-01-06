<template>
    <div class="container py-2">
        <router-link :to="referenceUrl" class="button is-text is-pulled-right">檢視簡易異名表</router-link>
        <div class="py-5 columns">
            <div class="column is-1 flex justify-center items-center">
                <label class="title is-5">文獻</label>
            </div>
            <div class="column is-11">
                <span class="has-text-weight-bold is-5">{{ reference.title }}</span>
                <br/>
                <span class="has-text-weight-normal is-5">{{ reference.subtitle }}</span>
            </div>
        </div>
        <div class="body py-3">
            <div class="usage-container">
                <template v-for="(usages, index) in usageGroups">
                    <div v-if="(usages[0].status === '' || usages[0].status === 'accepted') && usages[0].taxonName.rank.order < speciesRank.order"
                         class="inline-block border-l"
                    >
                        <div class="trapezoid absolute w-full"></div>
                        <p class="px-4 mr-5 text-gray-400">{{ usages[0].taxonName.rank.display['en-us'] }}</p>
                    </div>
                    <div class="flex">
                        <div class="group bg-gray-100 mb-3 border border-gray-300 w-full border-b-0">
                            <template v-for="(usage, index) in usages">
                                <div :class="{'is-title': usage.isTitle}" class="usage-row py-2 px-8"
                                >
                                    <div :class="{'is-indent': usage.isIndent}" class="usage-content">
                                        <template v-if="usage.isTitle">
                                            <span class="utitle" v-html="usage.taxonName.name"></span>
                                            <span v-if="!usage.isTitle" class="status is-pulled-right">{{
                                                    usage.status
                                                }}</span>
                                        </template>
                                        <template v-else>
                                            <div class="usage-content">
                                                <div v-if="usage.customNameRemark"
                                                     v-html="usage.customNameRemark"/>
                                                <div v-else-if="usage.nameRemark">
                                                    <usage-preview
                                                        ref="nameRemark"
                                                        :indications="getIndications(usage.properties.indications)"
                                                        :per-usages="usage.perUsages"
                                                        :status="usage.status"
                                                        :taxon-name="usage.taxonName"
                                                        :type-specimens="usage.typeSpecimens"
                                                        :type-name="usage.typeName"
                                                        class="inline-block"
                                                    />
                                                </div>
                                                <div v-else>
                                                    <span class="taxon-name">{{ usage.taxonName.name }}</span>
                                                    <span class="usubtitle">{{ usage.taxonName.formattedAuthors }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div class="border-b border-gray-300 py-4 px-5"
                                 v-if="usages[0] && showProperties(usages[0].properties)">
                                <div class="tags">
                                    <span v-if="usages[0].properties.isFossil" class="bg-gray-50 px-3">化石種</span>
                                    <span v-if="usages[0].properties.isTerrestrial" class="bg-gray-50 px-3 ml-2">陸生</span>
                                    <span v-if="usages[0].properties.isFreshwater" class="bg-gray-50 px-3 ml-2">淡水</span>
                                    <span v-if="usages[0].properties.isBrackish" class="bg-gray-50 px-3 ml-2">半鹹水域</span>
                                    <span v-if="usages[0].properties.isMarine" class="bg-gray-50 px-3 ml-2">海洋</span>
                                </div>
                                <table v-if="usages[0].properties.commonNames" class="text-center">
                                    <tr v-for="name in usages[0].properties.commonNames">
                                        <td class="px-4">{{ name.name }}</td>
                                        <td class="px-4">{{ $t(`forms.reference.languages.${name.language}`) }}</td>
                                        <td class="px-4">{{ name.area }}</td>
                                    </tr>
                                </table>
                                <p class="px-4">
                                    {{ usages[0].properties.note }}
                                </p>
                            </div>
                        </div>
                        <div class="px-2 flex items-center group bg-gray-100 mb-3 border border-gray-300 border-l-0">
                            <favorite-button :type="1" :id="usages[0].id" />
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import { combo as Scombo } from '../utils/preview/typeSpecimen';
import { factory as rFactory, subTitle, title } from '../utils/preview/reference';
import FavoriteButton from "../components/FavoriteButton";
import { mapGetters } from "vuex";
import UsagePreview from "../components/UsagePreview";
import indications from "../components/selects/indications";

export default {
    components: {UsagePreview, FavoriteButton},
    data() {
        return {
            usageGroups: [],
            reference: {},
            referenceUrl: `/references/${ this.$route.params.id }`,
        }
    },
    mounted() {
        this.axios.get(`/references/${ this.$route.params.id }`)
            .then(({data: {data}}) => {
                this.reference = data;
                this.reference.id = parseInt(this.$route.params.id);
                this.reference.language = data.language ? {id: data.language} : null;
                this.reference.title = title(data);
                this.reference.subtitle = subTitle(data);

                this.formStatus = this.$c.PAGE_IS_SUCCESS;
                this.$store.commit('breadcrumb/SET_ITEMS', [{
                    url: this.referenceUrl,
                    name: `文獻: ${ this.reference.title }`,
                }, {
                    url: '#',
                    name: `詳細異名表`,
                }]);
            });

        this.axios.get(`/references/${ this.$route.params.id }/usages`)
            .then(({data: {data}}) => {
                this.usageGroups = data;
            });


    },
    methods: {
        showProperties(p) {
            return (p.isFossil || p.isTerrestrial || p.isFreshwater || p.isBrackish || p.isMarine || p.commonNames?.length || p.note);
        },
        specimenCombo: (t) => Scombo(t),
        refPreview(usage) {
            const rCombo = rFactory(usage.taxonName.nomenclature.group);
            const result = [
                usage.taxonName.properties.referenceName,
                rCombo(usage.perUsages)
            ].filter(Boolean).join('; ');
            return result ? `${ result }. ` : '';
        },
        getIndications(indicationArray) {
            return indicationArray?.map((abbreviation) => {
                return indications.find(i => i.abbreviation === abbreviation);
            }).filter(Boolean);
        },
    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
        }),
    }
}
</script>

<style lang="scss" scoped>
.trapezoid {
    border-color:  transparent transparent #f4f4f5 transparent;
    border-width: 0 2rem 2rem 0;
}

.group {
    .usage-row {
        border-bottom: 1px solid lightgrey;

        .usage-content {
            &.is-indent {
                margin-left: 2rem;
            }
        }
    }
}
</style>
