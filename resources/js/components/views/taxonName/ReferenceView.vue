<template>
    <div class="flex flex-col h-full">
        <div class="grow">
            <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
                <thead>
                <tr>
                    <th>{{ $t('reference.authority') }}</th>
                    <th v-text="$t('common.reference')"/>
                    <th>
                        <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('taxonName.year') }}
                        </sort-button>
                    </th>
                    <th v-text="$t('taxonName.treatment')"/>
                    <th v-text="$t('taxonName.referenceInformation')"/>
                </tr>
                </thead>
                <tbody>
                <tr v-for="usage in usages">
                    <!-- 作者 -->
                    <td>
                        <p class="no-wrap" v-text="showAuthors(usage.reference.authors)"/>
                    </td>

                    <!-- 文獻 -->
                    <td>
                        <router-link
                            :to="{ name: 'reference-page', params: { id: usage.reference.id }}"
                            class="my-link">
                            {{ subTitle(usage.reference) }}
                        </router-link>
                    </td>
                    <!-- 年份 -->
                    <td v-text="usage.reference.publishYear"></td>
                    <td>
                        <status-with-indications
                            :indications="usage.properties.indications ? usage.properties.indications : []"
                            :nomenclature="usage.taxonName.nomenclature"
                            :status="usage.status"/>
                    </td>
                    <td>
                        <div class="tags">
                            <span v-if="usage.properties.isInTaiwan === 1" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.inTaiwan') }}
                            </span>
                            <span v-if="usage.properties.isEndemic" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.endemic') }}
                            </span>

                            <span v-if="usage.properties.alienType === 'native'"
                                  class="bg-gray-50 px-3 ml-2">{{ $t('usage.alienTypeOptions.native') }}</span>
                            <span v-if="usage.properties.alienType === 'naturalized'"
                                  class="bg-gray-50 px-3 ml-2">{{ $t('usage.alienTypeOptions.naturalized') }}</span>
                            <span v-if="usage.properties.alienType === 'invasive'"
                                  class="bg-gray-50 px-3 ml-2">{{ $t('usage.alienTypeOptions.invasive') }}</span>
                            <span v-if="usage.properties.alienType === 'cultured'"
                                  class="bg-gray-50 px-3 ml-2">{{ $t('usage.alienTypeOptions.cultured') }}</span>

                            <span v-if="usage.properties.isFossil" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.fossil') }}
                            </span>
                            <span v-if="usage.properties.isTerrestrial" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.terrestrial') }}
                            </span>
                            <span v-if="usage.properties.isFreshwater" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.freshWater') }}
                            </span>
                            <span v-if="usage.properties.isBrackish" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.brackish') }}
                            </span>
                            <span v-if="usage.properties.isMarine" class="bg-gray-50 px-3 ml-2">
                                {{ $t('usage.marine') }}
                            </span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="sticky bottom-0 border-t p-4 bg-white">
            <div class="buttons is-right">
                <router-link
                    :to="{'name': 'taxon-name-usages-compare', params:{id: $route.params.id}}"
                    class="button">{{ $t('taxonName.goCompareReference') }}
                </router-link>
            </div>
        </div>
    </div>
</template>
<script>
import AuthorName from '../../AuthorName.vue';
import SortButton from '../../SortButton.vue';
import { factory, subTitle } from '../../../utils/preview/reference';
import { comboLast } from '../../../utils/preview/person';
import StatusWithIndications from '../../StatusWithIndications.vue';

export default {
    data() {
        return {
            direction: '',
            sortby: '',
            usages: [],
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        subTitle: (r) => subTitle(r, false),
        async fetchData() {
            try {
                const { data: { data } } = await this.axios.get(
                    `/taxon-names/${this.$route.params.id}/references?sortby=${this.sortby}&direction=${this.direction}`,
                );

                if (data.length > 0) {
                    this.$emit('toggle', 'in-reference', true);
                }

                this.usages = data;
            } catch (e) {
                this.$emit('toggle', 'in-reference', false);
            }
        },
        onSortBy(column, direction) {
            this.sortby = column;
            this.direction = direction;
            this.usages = [];
            this.currentPage = 1;
            this.fetchData();
        },
        showReference(v) {
            return factory(this.type)([v]);
        },
        showAuthors(authors) {
            return comboLast(authors);
        },
    },
    components: { StatusWithIndications, SortButton, AuthorName },
};
</script>
