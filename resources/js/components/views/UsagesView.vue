<template>
    <div>
        <div v-for="usages in group">
            <div
                v-if="(usages[0].status === '' || usages[0].status === 'accepted') &&
                    usages[0].taxonName.rank.order < speciesRank.order"
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
                                        :type-name="usage.typeName"
                                        :type-specimens="usage.typeSpecimens"
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
                                        :type-name="usage.typeName"
                                        :type-specimens="usage.typeSpecimens"
                                    />
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- 取 group 中第一個 accepted 的學名 -->
                    <div
                        v-for="usage in [usages.filter((u) => !u.isTitle && u.status === 'accepted')[0]]"
                        v-if="usage &&
                              showProperties(usage.properties) &&
                              !isSimple"
                        class="border-b border-gray-300 py-2 px-5 flex flex-col gap-2">
                        <div v-if="usage.properties.commonNames && usage.properties.commonNames.length" class="px-2">
                            <table class="text-center">
                                <tr v-for="name in usage.properties.commonNames">
                                    <td class="px-2">{{ name.name }}</td>
                                    <td class="px-2">{{ $t(`reference.languages.${name.language}`) }}</td>
                                    <td class="px-2">{{ $t(`usage.area`)}}: {{ name.area }}</td>
                                </tr>
                            </table>
                        </div>
                        <usage-property-tags v-bind="{
                                    alienType: usage.properties.alienType,
                                    isInTaiwan: usage.properties.isInTaiwan,
                                    isEndemic: usage.properties.isEndemic,
                                    isFossil: usage.properties.isFossil,
                                    isTerrestrial: usage.properties.isTerrestrial,
                                    isFreshwater: usage.properties.isFreshwater,
                                    isBrackish: usage.properties.isBrackish,
                                    isMarine: usage.properties.isMarine,
                                }"/>
                        <div v-if="usage.properties.distributionInTw" class="px-4">
                            {{ $t('usage.distributionInTw')}}: {{ usage.properties.distributionInTw }}
                        </div>
                        <p v-if="usage.properties.note" class="px-4">
                            {{ usage.properties.note }}
                        </p>
                    </div>
                </div>
                <div class="px-2 flex items-center bg-gray-100 mb-3 border border-gray-300 border-l-0 z-0">
                    <favorite-button :id="usages[0].id" :type="1"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { mapGetters } from 'vuex';
import FavoriteButton from '../FavoriteButton.vue';
import UsagePreview from '../UsagePreview.vue';
import UsagePropertyTags from './UsagePropertyTags.vue';

import indications from '../selects/indications';

export default {
    props: {
        group: {
            type: Array,
            required: true,
        },
        isSimple: {
            type: Boolean,
            default: false,
        },
    },
    methods: {
        showProperties(p) {
            return (
                p.isFossil
                || p.isTerrestrial
                || p.isFreshwater
                || p.isBrackish
                || p.isMarine
                || p.commonNames?.length
                || p.note
                || p.isInTaiwan === 1
            );
        },
        getIndications(indicationArray) {
            return indicationArray ?
                indicationArray
                    .map((abbreviation) => indications.find((i) => i.abbreviation === abbreviation))
                    .filter(Boolean) : [];
        },
    },
    computed: {
        ...mapGetters({
            speciesRank: 'rank/getSpeciesRank',
        }),
    },
    components: { UsagePropertyTags, UsagePreview, FavoriteButton },
};
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
