<template>
    <div>
        <div class="min-h-4/5">
            <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
                <thead>
                <tr>
                    <th>作者</th>
                    <th>文獻</th>
                    <th>
                        <sort-button :id="'publish_year'" :on-click="onSortBy" :sortby="sortby" :direction="direction">
                            年份
                        </sort-button>
                    </th>
                    <th>文獻中處理</th>
                    <th>文獻中屬性資訊</th>
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
                        <router-link :to="`/references/${usage.reference.id}`" class="my-link">
                            {{ subTitle(usage.reference) }}
                        </router-link>
                    </td>
                    <!-- 年份 -->
                    <td v-text="usage.reference.publishYear"></td>
                    <td>
                        <status-with-indications
                            v-if="usage.properties.indications"
                            :nomenclature="usage.taxonName.nomenclature"
                            :status="usage.status"
                            :indications="usage.properties.indications"/>
                    </td>
                    <td>
                        <div class="tags">
                            <span v-if="usage.properties.isInTaiwan" class="bg-gray-50 px-3 ml-2">存在於臺灣</span>
                            <span v-if="usage.properties.isEndemic" class="bg-gray-50 px-3 ml-2">臺灣特有種</span>

                            <span v-if="usage.properties.alienType === 'native'" class="bg-gray-50 px-3 ml-2">原生</span>
                            <span v-if="usage.properties.alienType === 'naturalized'" class="bg-gray-50 px-3 ml-2">歸化</span>
                            <span v-if="usage.properties.alienType === 'invasive'" class="bg-gray-50 px-3 ml-2">入侵</span>
                            <span v-if="usage.properties.alienType === 'vultured'" class="bg-gray-50 px-3 ml-2">栽培豢養</span>

                            <span v-if="usage.properties.isFossil" class="bg-gray-50 px-3 ml-2">化石種</span>
                            <span v-if="usage.properties.isTerrestrial" class="bg-gray-50 px-3 ml-2">陸生</span>
                            <span v-if="usage.properties.isFreshwater" class="bg-gray-50 px-3 ml-2">淡水</span>
                            <span v-if="usage.properties.isBrackish" class="bg-gray-50 px-3 ml-2">半鹹水域</span>
                            <span v-if="usage.properties.isMarine" class="bg-gray-50 px-3 ml-2">海洋</span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="sticky bottom-0 border-t p-4 bg-white">
            <div class="buttons is-right">
                <router-link class="button" :to="`/taxon-names/${$route.params.id}/compare`">前往異名表比較</router-link>
            </div>
        </div>
    </div>
</template>
<script>
    import AuthorName from '../../AuthorName';
    import SortButton from '../../SortButton';
    import { factory, subTitle } from '../../../utils/preview/reference';
    import { comboLast } from '../../../utils/preview/person';
    import StatusWithIndications from '../../StatusWithIndications';

    export default {
        data() {
            return {
                direction: '',
                sortby: '',
                usages: [],
            }
        },
        mounted() {
            this.fetchData()
        },
        methods: {
            subTitle: (r) => subTitle(r, false),
            async fetchData() {
                try {
                    const { data: { data } } = await this.axios.get(`/taxon-names/${this.$route.params.id}/references?sortby=${this.sortby}&direction=${this.direction}`);

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
    }
</script>
