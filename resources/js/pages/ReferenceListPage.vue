<template>
    <div>
        <div class="container">
            <!-- 筆數結果顯示 -->
            <div class="help has-text-centered">
                <template v-if="total">共計 {{ total }} 筆資料</template>
                <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">查無結果</span>
            </div>

            <table class="table is-fullwidth is-hoverable has-text-left">
                <thead>
                <tr>
                    <th>
                        <sort-button :id="'type'" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.reference.type') }}
                        </sort-button>
                    </th>
                    <th v-text="$t('forms.reference.author')"/>
                    <th>
                        <sort-button :id="'publish_year'" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.reference.publishYear') }}
                        </sort-button>
                    </th>
                    <th style="max-width:100px">
                        <sort-button :id="'article_title'" :on-click="onSortBy" :sortby="sortby">
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
                        <span class="has-text-weight-bold"
                              v-text="r.properties.articleTitle"></span>
                        <br v-if="r.properties.articleTitle"/>
                    </td>

                    <!-- 發表文獻 -->
                    <td class="has-text-left" v-text="subTitle(r)"/>

                    <!-- 語言 -->
                    <td class="no-wrap">
                        <span v-if="r.language">
                            {{ $t(`forms.reference.languages.${r.language}`) }}
                        </span>
                    </td>

                    <td>
                        <router-link :to="`/references/${r.id}`"
                                     class="button is-small"
                                     v-text="'查看'"
                                     target="_blank "
                        ></router-link>
                    </td>
                </tr>
                <tr>
                    <th class="has-text-centered" colspan="7">
                        <span class="caption">共計 {{ total }} 筆資料</span>
                        <loading v-if="pageStatus === $c.PAGE_IS_LOADING"/>
                    </th>
                </tr>
                </tbody>
            </table>
        </div>
        <br/>
    </div>
</template>
<script>
    import AutoComplete from './../components/AutoComplete';
    import Breadcrumb from '../components/Breadcrumb';
    import Loading from '../components/Loading';
    import { fullNameAbbreviation } from '../utils/preview/person';
    import { subTitle } from '../utils/preview/reference';
    import SortButton from '../components/SortButton';

    export default {
        data() {
            return {
                pageStatus: this.$c.PAGE_IS_INITIAL,
                currentPage: 1,
                lastPage: 1,
                sortby: '',
                direction: '',
                total: 0,
                references: [],
                intersectionObserver: null,
            }
        },
        computed: {
            route() {
                return this.$route.query;
            },
        },
        watch: {
            route() {
                this.currentPage = 1;
                this.lastPage = 1;
                this.total = 0;
                this.references = [];
                this.fetchData();
                this.setBreadcrumb();
            },
        },
        mounted() {
            const app = this;
            this.intersectionObserver = new IntersectionObserver(function (entries) {
                if (entries[0].intersectionRatio > 0 && app.currentPage <= app.lastPage) {
                    app.fetchData();
                }
            });
            this.intersectionObserver.observe(document.querySelector('.caption'));
            this.setBreadcrumb();
        },
        methods: {
            fullNameAbbreviation: (author) => fullNameAbbreviation(author),
            subTitle: (r) => subTitle(r, false),
            setBreadcrumb() {
                this.$store.commit('breadcrumb/SET_ITEMS', [{
                    url: '#',
                    name: `搜尋: ${this.$route.query.keyword}`,
                }]);
            },
            onSortBy(column, direction) {
                this.sortby = column;
                this.direction = direction;
                this.references = [];
                this.currentPage = 1;
                this.fetchData();
            },
            fetchData() {
                if (this.pageStatus === this.$c.PAGE_IS_LOADING) {
                    return;
                }

                this.pageStatus = this.$c.PAGE_IS_LOADING;
                this.axios.get(`/references`,
                    {
                        params: {
                            keyword: this.$route.query.keyword,
                            page: this.currentPage,
                            sortby: this.sortby,
                            direction: this.direction,
                        },
                    },
                )
                    .then(({ data: { data, total, lastPage } }) => {
                        this.references = this.references.concat(data);
                        this.pageStatus = this.$c.PAGE_IS_SUCCESS;
                        this.currentPage += 1;
                        this.lastPage = lastPage;
                        this.total = total;
                    });
            },
        },
        destroyed() {
            this.intersectionObserver.disconnect();
        },
        components: {
            SortButton,
            AutoComplete,
            Breadcrumb,
            Loading,
        },
    }
</script>
<style lang="scss" scoped>
    .no-wrap {
        white-space: nowrap;
    }

    .container {
        max-width: 90%;

        .table {
            position: relative;
            z-index: 10;

            th {
                background-color: white;
                z-index: 60;
                position: sticky;
                top: calc(#{$navbar-height} + 1.5rem + 3rem);
                padding-top: 1.2rem;
                color: $grey;
            }
        }
    }
</style>
