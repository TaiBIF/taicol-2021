<template>
    <div class="container">
        <searchbar v-if="$route.meta.searchbar"  v-on:on-search="fetchData" />
        <div style="padding-top: 4rem">
            <!-- 筆數結果顯示 -->
            <div class="help has-text-centered">
                <template v-if="total">共計 {{ total }} 筆資料 · 第 {{ currentPage }} 頁</template>
                <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">查無結果</span>
            </div>
            <table class="table is-fullwidth is-hoverable has-text-left">
                <thead>
                <tr>
                    <th class="no-wrap">
                        <sort-button :id="'type'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.reference.type') }}
                        </sort-button>
                    </th>
                    <th v-text="$t('forms.reference.author')"/>
                    <th class="no-wrap">
                        <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.reference.publishYear') }}
                        </sort-button>
                    </th>
                    <th style="max-width:100px" class="no-wrap">
                        <sort-button :id="'article_title'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.reference.articleTitle') }}
                        </sort-button>
                    </th>
                    <th v-text="$t('forms.reference.publishTitle')"></th>
                    <th v-text="$t('forms.reference.language')"/>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="r in references">
                    <!-- 文獻類型 -->
                    <td class="no-wrap"
                        v-text="$t(`forms.reference.typeOptions.${r.type}`)"></td>

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
                                     :to="`/references/${r.id}`" class="my-link">
                            <span class="has-text-weight-bold" v-text="r.properties.articleTitle"/>
                        </router-link>
                    </td>

                    <!-- 發表文獻 -->
                    <td class="has-text-left">
                        <router-link :to="`/references/${r.id}`" class="my-link">
                            {{ subTitle(r) }}
                        </router-link>
                    </td>

                    <!-- 語言 -->
                    <td class="no-wrap">
                        <span v-if="r.language">
                            {{ $t(`forms.reference.languages.${r.language}`) }}
                        </span>
                    </td>
                    <td>
                        <favorite-button :type="3" :id="r.id" />
                    </td>
                </tr>
                <tr>
                    <th class="has-text-centered" colspan="7">
                        <pagination :current-page="currentPage"
                                    :per-page="perPage"
                                    :total="total"
                                    v-on:change="onChangePage"
                        />
                        <span class="caption">共計 {{ total }} 筆資料</span>
                        <loading v-if="pageStatus === $c.PAGE_IS_LOADING"/>
                    </th>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script>
    import AutoComplete from './../components/AutoComplete';
    import Loading from '../components/Loading';
    import { fullNameAbbreviation } from '../utils/preview/person';
    import { subTitle } from '../utils/preview/reference';
    import SortButton from '../components/SortButton';
    import Pagination from '../components/Pagination';
    import Searchbar from '../components/Searchbar';
    import FavoriteButton from "../components/FavoriteButton";

    export default {
        data() {
            return {
                pageStatus: this.$c.PAGE_IS_INITIAL,
                currentPage: parseInt(this.$route.query?.page) || 1,
                lastPage: 1,
                sortby: this.$route.query?.sortby || '',
                direction: this.$route.query?.direction || '',
                perPage: 20,
                total: 0,
                references: [],
                intersectionObserver: null,
            }
        },
        mounted() {
            this.fetchData();
        },
        methods: {
            fullNameAbbreviation: (author) => fullNameAbbreviation(author),
            subTitle: (r) => subTitle(r, false),
            onChangePage(page) {
                const p = parseInt(page);
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
                this.axios.get(`/search-references`,
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
            Searchbar
        },
    }
</script>
<style lang="scss" scoped>
    .container {
        max-width: 90%;

        .table {
            position: relative;
            z-index: 10;

            th {
                background-color: white;
                z-index: 60;
                position: sticky;
                top: calc(#{$navbar-height} + 3.5rem);
                padding-top: 1.2rem;
                color: $grey;
            }
        }
    }
</style>
