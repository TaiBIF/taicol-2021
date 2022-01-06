<template>
    <div>
        <div v-for="usages in group">
            <div
                v-if="(usages[0].status === '' || usages[0].status === 'accepted') && usages[0].taxonName.rank.order < speciesRank.order"
                class="inline-block border-l"
            >
                <div class="trapezoid absolute w-full"></div>
                <p class="px-4 mr-5 text-gray-400">{{ usages[0].taxonName.rank.display['en-us'] }}</p>
            </div>
            <div class="flex">
                <div class="group bg-gray-100 mb-3 border border-gray-300 w-full border-b-0">
                    <template v-for="(usage, index) in usages">
                        <div :class="{'is-title': usage.isTitle}" class="usage-row"
                        >
                            <div :class="{'is-indent': usage.isIndent}" class="usage-content">
                                <template v-if="usage.nameRemark && !isSimple && !usage.isTitle">
                                    <span v-if="usage.customNameRemark"
                                          v-html="usage.customNameRemark"/>

                                    <usage-preview
                                        v-else
                                        ref="nameRemark"
                                        :indications="getIndications(usage.properties.indications)"
                                        :per-usages="usage.perUsages"
                                        :status="usage.status"
                                        :taxon-name="usage.taxonName"
                                        :type-specimens="usage.typeSpecimens"
                                        :type-name="usage.typeName"
                                    />
                                </template>
                                <template v-else>
                                    <usage-preview
                                        ref="nameRemark"
                                        :indications="getIndications(usage.properties.indications)"
                                        :is-simple="true"
                                        :per-usages="usage.perUsages"
                                        :status="usage.status"
                                        :taxon-name="usage.taxonName"
                                        :type-specimens="usage.typeSpecimens"
                                        :type-name="usage.typeName"
                                    />
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="border-b border-gray-300 py-4 px-5"
                         v-if="usages[0] && showProperties(usages[0].properties) && !isSimple">
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
                                <td class="px-4">{{ $t(`forms.reference.languages.${ name.language }`) }}</td>
                                <td class="px-4">{{ name.area }}</td>
                            </tr>
                        </table>
                        <p class="px-4">
                            {{ usages[0].properties.note }}
                        </p>
                    </div>
                </div>
                <div class="px-2 flex items-center group bg-gray-100 mb-3 border border-gray-300 border-l-0 z-0">
                    <favorite-button :type="1" :id="usages[0].id"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import FavoriteButton from "../FavoriteButton";
import { mapGetters } from "vuex";
import UsagePreview from "../UsagePreview";
import indications from "../selects/indications";

export default {
    props: {
        group: {
            type: Array,
            required: true,
        },
        isSimple: {
            type: Boolean,
            default: false,
        }
    },
    methods: {
        showProperties(p) {
            return (p.isFossil || p.isTerrestrial || p.isFreshwater || p.isBrackish || p.isMarine || p.commonNames?.length || p.note);
        },
        getIndications(indicationArray) {
            return indicationArray ? indicationArray.map((abbreviation) => {
                return indications.find(i => i.abbreviation === abbreviation);
            }).filter(Boolean) : [];
        },
    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
        }),
    },
    components: {UsagePreview, FavoriteButton}
}
</script>
<style lang="scss" scoped>
.trapezoid {
    border-color: transparent transparent #f4f4f5 transparent;
    border-width: 0 2rem 2rem 0;
}

.group {
    .usage-row {
        border-bottom: 1px solid lightgrey;
        padding: .5rem 2rem;

        .usage-content {
            &.is-indent {
                margin-left: 2rem;
            }

            .tag {
                margin-left: .5rem;
            }
        }
    }
}
</style>
