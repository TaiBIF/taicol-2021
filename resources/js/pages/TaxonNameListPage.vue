<template>
    <div class="container">
        <searchbar v-if="$route.meta.searchbar" v-on:on-search="fetchData" />
        <div style="padding-top: 4rem">
            <!-- 筆數結果顯示 -->
            <div class="help has-text-centered">
                <template v-if="total">共計 {{ total }} 筆資料 · 第 {{ currentPage }} 頁</template>
                <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">查無結果</span>
            </div>
            <table class="table is-fullwidth is-hoverable has-text-left">
                <thead>

                <tr>
                    <th>界</th>
                    <th style="white-space: nowrap">所屬類群</th>
                    <th style="white-space: nowrap">
                        <sort-button :id="'rank'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.taxonName.rank') }}
                        </sort-button>
                    </th>
                    <th>
                        <sort-button :id="'name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('models.taxonName') }}
                        </sort-button>
                    </th>

                    <th>命名者</th>
                    <th style="white-space: nowrap">中文俗名</th>
                    <th v-text="$t('forms.taxonName.reference')"/>
                    <th style="white-space: nowrap">
                        <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                            {{ $t('forms.reference.publishYear') }}
                        </sort-button>
                    </th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="taxonName in taxonNames">
                    <!-- 階層 -->
                    <td>
                    <span v-if="taxonName.root"
                          v-text="taxonName.root.name"/>
                    </td>

                    <!-- 所屬類群 -->
                    <td>
                        <taxon-name-label v-if="taxonName.parentGroup" :taxon-name="taxonName.parentGroup"/>
                    </td>

                    <!-- 階層 -->
                    <td v-text="taxonName.rank.display[$i18n.locale()]"></td>

                    <!-- 學名 -->
                    <td>
                        <router-link :to="`/taxon-names/${taxonName.id}`" class="my-link">
                            <taxon-name-label :taxon-name="taxonName"/>
                        </router-link>
                    </td>

                    <!-- 命名者 -->
                    <td>
                        <author-name v-bind="{
                        authors: taxonName.authors,
                        exAuthors: taxonName.exAuthors,
                        type: taxonName.nomenclature.group,
                        originalTaxonName: taxonName.originalTaxonName,
                        publishYear: taxonName.publishYear,
                        taxonName: taxonName,
                    }"></author-name>
                    </td>
                    <td>
                        {{ taxonName.commonNameTw }}
                    </td>

                    <!-- 發表文獻 -->
                    <td>
                    <span v-if="taxonName.reference">
                        {{ rName(taxonName.reference, taxonName.properties.usage.showPage) }}
                    </span>
                        <span v-else>{{ taxonName.properties.referenceName }}</span>
                    </td>
                    <td v-text="taxonName.publishYear"></td>
                    <td>
                        <favorite-button :type="2" :id="taxonName.id" />
                    </td>
                </tr>
                <tr>
                    <td class="has-text-centered has-text-weight-bold has-text-grey" colspan="10">
                        <pagination :current-page="currentPage"
                                    :per-page="perPage"
                                    :total="total"
                                    v-on:change="onChangePage"
                        />
                        <span class="caption">共計 {{ total }} 筆資料</span>
                        <loading v-if="pageStatus === $c.PAGE_IS_LOADING"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script>
    import Breadcrumb from '../components/Breadcrumb';
    import Loading from '../components/Loading';
    import { mapGetters } from 'vuex';
    import SortButton from '../components/SortButton';
    import AuthorName from '../components/AuthorName';
    import { taxonNameInReference, title } from '../utils/preview/reference';
    import TaxonNameLabel from '../components/views/TaxonNameLabel';
    import Pagination from '../components/Pagination';
    import Searchbar from '../components/Searchbar';
    import FavoriteButton from "../components/FavoriteButton";

    export default {
        data() {
            return {
                pageStatus: this.$c.PAGE_IS_INITIAL,
                currentPage: parseInt(this.$route.query?.page) || 1,
                lastPage: 1,
                perPage: 20,
                total: 0,
                sortby: this.$route.query?.sortby || '',
                direction: this.$route.query?.direction || '',
                columns: [
                    {
                        label: '所屬類群',
                    },
                    {
                        label: this.$t('forms.taxonName.rank'),
                    },
                ],
                taxonNames: [],
            }
        },
        computed: {
            ...mapGetters({
                user: 'auth/user',
            }),
            route() {
                return this.$route.query;
            },
        },
        mounted() {
            this.fetchData();
        },
        methods: {
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
                this.axios.get(`/search-taxon-names`, {
                    params: {
                        keywords: decodeURI(this.$route.query.keywords ?? ''),
                        strict: 0,
                        page: this.currentPage,
                        direction: this.direction,
                        sortby: this.sortby,
                        perPage: this.perPage,
                    },
                })
                    .then(({ data: { data, total, lastPage } }) => {
                        this.taxonNames = data;
                        this.lastPage = lastPage;
                        this.total = total;
                        this.pageStatus = this.$c.PAGE_IS_SUCCESS;
                    });
            },
            rName(reference, showPage) {
                return [
                    reference.properties.bookTitleAbbreviation ? taxonNameInReference(reference) : title(reference),
                    showPage,
                ].filter(Boolean).join(': ');
            },
        },
        components: {
            FavoriteButton,
            Pagination,
            TaxonNameLabel,
            AuthorName,
            SortButton,
            Breadcrumb,
            Loading,
            Searchbar,
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
