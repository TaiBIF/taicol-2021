<template>
    <div class="page">
        <!-- 筆數結果顯示 -->
        <div class="help text-center">
            <template v-if="total">{{ $t('common.totalRowsNumberWithPage', {total, currentPage}) }}</template>
            <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">{{ $t('common.noResult') }}</span>
        </div>
        <table class="table w-full is-hoverable has-text-left table-fixed">
            <thead>
            <tr>
                <th class="w-[115px]">
                    <sort-button :id="'type'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('reference.type') }}
                    </sort-button>
                </th>
                <th class="w-[190px]" v-text="$t('reference.authority')"/>
                <th class="w-[100px]">
                    <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('common.publishYear') }}
                    </sort-button>
                </th>
                <th class="w-[80%]">
                    <sort-button :id="'article_title'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('reference.articleTitle') }}
                    </sort-button>
                </th>
                <th class="w-[100%]" v-text="$t('reference.publishTitle')"></th>
                <th class="w-[85px]" v-text="$t('reference.language')"/>
                <th class="w-[40px]"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="r in references">
                <!-- 文獻類型 -->
                <td class="no-wrap"
                    v-text="$t(`reference.typeOptions.${r.type}`)"></td>

                <!-- 作者 -->
                <td class="has-text-left">
                    <p v-for="author in r.authors"
                       class="no-wrap"
                       v-text="fullNameAbbreviation(author)"/>
                </td>

                <!-- 發表年份 -->
                <td v-text="r.publishYear"></td>

                <!-- 文章標題 -->
                <td class="has-text-left">
                    <router-link v-if="r.properties.articleTitle"
                                 :to="{name: 'reference-page', params: {id: r.id}}" class="my-link">
                        <span class="has-text-weight-bold" v-text="r.properties.articleTitle"/>
                    </router-link>
                </td>

                <!-- 發表文獻 -->
                <td class="has-text-left">
                    <router-link :to="{name: 'reference-page', params:{id: r.id}}" class="my-link">
                        {{ subTitle(r) }}
                    </router-link>
                </td>

                <!-- 語言 -->
                <td>
                    <p v-if="r.language" class="break-normal">
                        {{ $t(`reference.languages.${r.language}`) }}
                    </p>
                </td>
                <td>
                    <favorite-button :id="r.id" :type="3"/>
                </td>
            </tr>
            <tr>
                <td class="text-center" colspan="7">
                    <pagination :current-page="currentPage"
                                :per-page="perPage"
                                :total="total"
                                v-on:change="onChangePage"
                    />
                    <span class="caption">{{ $t('common.totalRowsNumber', {total}) }}</span>
                    <loading v-if="pageStatus === $c.PAGE_IS_LOADING"/>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import AutoComplete from '../components/AutoComplete.vue';
import Loading from '../components/Loading.vue';
import SortButton from '../components/SortButton.vue';
import Pagination from '../components/Pagination.vue';
import Searchbar from '../components/Searchbar.vue';
import FavoriteButton from '../components/FavoriteButton.vue';

import { fullNameAbbreviation } from '../utils/preview/person';
import { subTitle } from '../utils/preview/reference';

export default {
    data() {
        return {
            pageStatus: this.$c.PAGE_IS_INITIAL,
            currentPage: parseInt(this.$route.query?.page, 10) || 1,
            lastPage: 1,
            sortby: this.$route.query?.sortby || '',
            direction: this.$route.query?.direction || '',
            perPage: 20,
            total: 0,
            references: [],
            intersectionObserver: null,
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fullNameAbbreviation: (author) => fullNameAbbreviation(author),
        subTitle: (r) => subTitle(r, false),
        onChangePage(page) {
            const p = parseInt(page, 10);
            if (p !== this.currentPage) {
                this.$router.replace({ query: { ...this.$route.query, page: p } });
                this.currentPage = p;
                this.fetchData();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            }
        },
        onSortBy(column, newDirection) {
            const { sortby, direction } = this.$route.query;
            if (sortby === column && direction === newDirection) {
                return;
            }

            this.$router.replace({ query: { ...this.$route.query, sortby: column, direction: newDirection } });
            this.sortby = column;
            this.direction = newDirection;

            this.fetchData();
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        },
        fetchData() {
            if (this.pageStatus === this.$c.PAGE_IS_LOADING) {
                return;
            }

            this.pageStatus = this.$c.PAGE_IS_LOADING;
            this.axios.get(
                '/search-references',
                {
                    params: {
                        keywords: this.$route.query.keywords,
                        strict: 0,
                        page: this.currentPage,
                        sortby: this.sortby,
                        direction: this.direction,
                        perPage: this.perPage,
                    },
                },
            )
                .then(({ data: { data, total, lastPage } }) => {
                    this.references = data;
                    this.pageStatus = this.$c.PAGE_IS_SUCCESS;
                    this.lastPage = lastPage;
                    this.total = total;
                });
        },
    },
    components: {
        FavoriteButton,
        Pagination,
        SortButton,
        AutoComplete,
        Loading,
        Searchbar,
    },
};
</script>
<style lang="scss" scoped>
.page {
    .table {
        position: relative;
        z-index: 10;

        th {
            background-color: white;
            z-index: 60;
            position: sticky;
            top: 0;
            color: $grey;
        }
    }
}
</style>
