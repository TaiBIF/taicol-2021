<template>
    <div class="container">

        <table class="table is-fullwidth is-hoverable has-text-left">
            <thead>
            <!-- 筆數結果顯示 -->
            <tr>
                <th class="has-text-centered" colspan="10">
                    <span v-if="total" class="caption">共計 {{ total }} 筆資料</span>
                    <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">查無結果</span>
                </th>
            </tr>
            <tr>
                <th>所屬類群</th>
                <th>
                    <sort-button :id="'rank'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('forms.taxonName.rank') }}
                    </sort-button>
                </th>
                <th>
                    接受學名
                    <b-tooltip multilined position="is-bottom" size="is-large">
                        <i class="fas fa-info-circle"></i>
                        <template v-slot:content>
                            <strong>國際藻類、真菌、植物命名法規(ICN)：</strong>
                            <br/>&nbsp;「正確學名(correct name)」<br/>
                            <strong>國際動物命名法規(ICZN)：</strong>
                            <br/>&nbsp;「有效學名(valid name)」
                        </template>
                    </b-tooltip>
                </th>
                <th v-on:click="onSortBy('status')">
                    {{ $t('forms.taxonName.status') }}
                    <b-tooltip multilined position="is-bottom" size="is-large">
                        <i class="fas fa-info-circle"></i>
                        <template v-slot:content>
                            <strong>國際藻類、真菌、植物命名法規(ICN)：</strong><br/>
                            <ul>
                                <li>nom. cons. = 保留名</li>
                                <li>nom. illeg. = 不合法名</li>
                                <li>nom. inval. = 不正當名</li>
                                <li>nom. rej. = 廢棄名</li>
                                <li>syn. = 異名</li>
                            </ul>
                            <strong>國際動物命名法規(ICZN)：</strong><br/>
                            <ul>
                                <li>nom. prot. = 受保護名</li>
                                <li>nom. obl. = 被遺忘名</li>
                                <li>unavailable = 不適用</li>
                                <li>syn. = 異名</li>
                            </ul>
                        </template>
                    </b-tooltip>
                </th>
                <th>
                    <sort-button :id="'name'" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('models.taxonName') }}
                    </sort-button>
                </th>

                <th>命名者</th>
                <th>中文俗名</th>
                <th v-text="$t('forms.taxonName.reference')"/>
                <th>
                    <sort-button :id="'publish_year'" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('forms.reference.publishYear') }}
                    </sort-button>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="taxonName in taxonNames">
                <td></td>

                <!-- 階層 -->
                <td v-text="taxonName.rank.display[$i18n.locale()]"></td>
                <td></td>
                <td></td>
                <!-- 學名 -->
                <td>
                    <router-link :to="`/taxon-names/${taxonName.id}?keyword=${$route.query.keyword}`" target="_blank">
                        <!-- 屬以上的學名不斜體 -->
                        <template v-if="taxonName.rank.order >= 30">
                            <i>{{ taxonName.name }}</i>
                        </template>
                        <template v-else>
                            {{ taxonName.name }}
                        </template>
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
                    }"></author-name>
                </td>
                <td></td>
                <!-- 發表文獻 -->
                <td>
                    <span v-if="taxonName.reference">
                        {{ rName(taxonName.reference, taxonName.properties.usage.showPage) }}
                    </span>
                    <span v-else>{{ taxonName.properties.referenceName }}</span>
                </td>
                <td v-text="taxonName.publishYear"></td>
            </tr>
            <tr>
                <td class="has-text-centered has-text-weight-bold has-text-grey" colspan="10">
                    <span class="caption">共計 {{ total }} 筆資料</span>
                    <loading v-if="pageStatus === $c.PAGE_IS_LOADING"/>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
    import Breadcrumb from '../components/Breadcrumb';
    import Loading from '../components/Loading';
    import { mapGetters } from 'vuex';
    import SortButton from '../components/SortButton';
    import AuthorName from '../components/AuthorName';
    import { taxonNameInReference, title } from '../utils/preview/reference';

    export default {
        data() {
            return {
                pageStatus: this.$c.PAGE_IS_INITIAL,
                currentPage: 1,
                lastPage: 1,
                total: 0,
                sortby: '',
                direction: 1,
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
        watch: {
            route() {
                this.currentPage = 1;
                this.lastPage = 1;
                this.total = 0;
                this.taxonNames = [];
                this.fetchData();
                this.setBreadcrumb();
            },
        },
        mounted() {
            const app = this;
            app.intersectionObserver = new IntersectionObserver(function (entries) {
                if (entries[0].intersectionRatio > 0 && app.currentPage <= app.lastPage) {
                    app.fetchData();
                }
            });
            app.intersectionObserver.observe(document.querySelector('.caption'));

            app.setBreadcrumb();
        },
        methods: {
            onSortBy(column, direction) {
                this.sortby = column;
                this.direction = direction;
                this.taxonNames = [];
                this.currentPage = 1;
                this.fetchData();
            },
            setBreadcrumb() {
                this.$store.commit('breadcrumb/SET_ITEMS', [{
                    url: '#',
                    name: `搜尋: ${this.$route.query.keyword}`,
                }]);
            },
            fetchData() {
                if (this.pageStatus === this.$c.PAGE_IS_LOADING) {
                    return;
                }

                this.pageStatus = this.$c.PAGE_IS_LOADING;
                this.axios.get(`/taxon-names`, {
                    params: {
                        keyword: this.$route.query.keyword,
                        page: this.currentPage,
                        direction: this.direction,
                        sortby: this.sortby,
                    },
                })
                    .then(({ data: { data, total, lastPage } }) => {
                        this.taxonNames = this.taxonNames.concat(data);
                        this.currentPage += 1;
                        this.lastPage = lastPage;
                        this.total = total;
                        this.pageStatus = this.$c.PAGE_IS_SUCCESS;
                    });
            },
            rName(reference, showPage) {
                return [
                    reference.properties.bookTitleAbbreviation ? taxonNameInReference(reference) : title(reference),
                    showPage
                ].filter(Boolean).join(': ');
            }
        },
        destroyed() {
            this.intersectionObserver.disconnect();
        },
        components: {
            AuthorName,
            SortButton,
            Breadcrumb,
            Loading,
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
                top: calc(#{$navbar-height} + 1.5rem + 3rem);
                padding-top: 1.2rem;
                color: $grey;
            }
        }
    }

    a {
        color: $black;
        &:hover {
            text-decoration: underline;

            &::after {
                font-family: "Font Awesome 5 Free";
                content: "  ";
                font-weight: 900;
            }
        }
    }
</style>
