<template>
    <div class="page">
        <!-- 筆數結果顯示 -->
        <div class="help text-center">
            <template v-if="total">{{ $t('common.totalRowsNumberWithPage', {total, currentPage}) }}</template>
            <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">{{ $t('common.noResult') }}</span>
        </div>
        <table class="table w-full is-hoverable has-text-left table-fixed min-w-[1280px]">
            <thead>
            <tr>
                <th class="w-[100px]">{{ $t('common.kingdom') }}</th>
                <th class="w-[160px]">{{ $t('taxonName.group') }}</th>
                <th class="w-[80px]">
                    <sort-button :id="'rank'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('common.rank') }}
                    </sort-button>
                </th>
                <th class="w-[50%]">
                    <sort-button :id="'name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('common.taxonName') }}
                    </sort-button>
                </th>

                <th class="w-[210px]">{{ $t('common.authors') }}</th>
                <th class="w-[160px]">{{ $t('common.chineseName') }}</th>
                <th class="w-[50%]"
                    v-text="$t('taxonName.publication')"/>
                <th class="w-[130px]">
                    <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('common.publishYear') }}
                    </sort-button>
                </th>
                <th class="w-[50px]"></th>
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
                    {{ taxonName.parentGroup ? taxonName.parentGroup.name : '' }}
                </td>

                <!-- 階層 -->
                <td v-text="taxonName.rank.display[$i18n.locale()]"></td>

                <!-- 學名 -->
                <td>
                    <router-link :to="{name: 'taxon-name-page', params: { id: taxonName.id}}" class="my-link">
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
                    <template v-if="taxonName.reference">
                        {{ rName(taxonName.reference, taxonName.properties.usage.showPage) }}
                    </template>
                    <template v-else>{{ taxonName.properties.referenceName }}</template>
                </td>
                <td v-text="taxonName.publishYear"></td>
                <td>
                    <favorite-button :id="taxonName.id" :type="2"/>
                </td>
            </tr>
            <tr>
                <td class="text-center has-text-weight-bold has-text-grey" colspan="10">
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
import { mapGetters } from 'vuex';
import Breadcrumb from '../components/Breadcrumb.vue';
import Loading from '../components/Loading.vue';
import SortButton from '../components/SortButton.vue';
import AuthorName from '../components/AuthorName.vue';
import TaxonNameLabel from '../components/views/TaxonNameLabel.vue';
import Pagination from '../components/Pagination.vue';
import Searchbar from '../components/Searchbar.vue';
import FavoriteButton from '../components/FavoriteButton.vue';

import { taxonNameInReference, title } from '../utils/preview/reference';

export default {
    data() {
        return {
            pageStatus: this.$c.PAGE_IS_INITIAL,
            currentPage: parseInt(this.$route.query?.page, 10) || 1,
            lastPage: 1,
            perPage: 20,
            total: 0,
            sortby: this.$route.query?.sortby || '',
            direction: this.$route.query?.direction || '',
            columns: [
                {
                    label: this.$t('taxonName.group'),
                },
                {
                    label: this.$t('taxonName.rank'),
                },
            ],
            taxonNames: [],
        };
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
            const p = parseInt(page, 10);
            if (p !== this.currentPage) {
                this.$router.replace({ query: { ...this.$route.query, page: p } });
                this.currentPage = p;
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
            this.axios.get('/search-taxon-names', {
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
