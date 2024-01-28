<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>{{ $t('taxonName.kingdom') }}</th>
            <th style="white-space: nowrap">{{ $t('taxonName.group') }}</th>
            <th style="white-space: nowrap">
                <sort-button :id="'rank'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                    {{ $t('taxonName.rank') }}
                </sort-button>
            </th>
            <th>
                <sort-button :id="'name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                    {{ $t('common.taxonName') }}
                </sort-button>
            </th>

            <th>{{ $t('common.authors') }}</th>
            <th style="white-space: nowrap">{{ $t('common.chineseName') }}</th>
            <th v-text="$t('taxonName.publication')"/>
            <th style="white-space: nowrap">
                <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                    {{ $t('common.publishYear') }}
                </sort-button>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="taxonName in taxonNames">
            <!-- 階層 -->
            <td>
                <span v-if="taxonName.root" v-text="taxonName.root.name"/>
            </td>

            <!-- 所屬類群 -->
            <td>
                {{ taxonName.parentGroup ? taxonName.parentGroup.name : '' }}
            </td>

            <!-- 階層 -->
            <td v-text="taxonName.rank.display[$i18n.locale()]"></td>

            <!-- 學名 -->
            <td>
                <router-link
                    :to="{name: 'taxon-name-page', params: {id: taxonName.id}}"
                    class="my-link"
                >
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
                }"/>
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
            <td colspan="8">
                <span class="caption"></span>
                <loading v-if="isLoading"></loading>
            </td>
        </tr>
        </tbody>
    </table>
</template>
<script>
import SortButton from '../../SortButton.vue';
import Loading from '../../Loading.vue';
import AuthorName from '../../AuthorName.vue';
import TaxonNameLabel from '../TaxonNameLabel.vue';
import { taxonNameInReference, title } from '../../../utils/preview/reference';

export default {
    data() {
        return {
            taxonNames: [],
            direction: '',
            currentPage: 0,
            lastPage: 0,
            sortby: '',
            perPage: 30,
            total: 0,
            isLoading: false,
        };
    },
    async mounted() {
        const app = this;
        app.fetchData();
        app.intersectionObserver = new IntersectionObserver((entries) => {
            if (entries[0].intersectionRatio > 0) {
                app.fetchData();
            }
        });
        app.intersectionObserver.observe(document.querySelector('.caption'));
    },
    methods: {
        onSortBy(column, newDirection) {
            this.sortby = column;
            this.direction = newDirection;

            this.currentPage = 0;
            this.lastPage = 0;
            this.taxonNames = [];

            this.fetchData();
        },
        rName(reference, showPage) {
            return [
                reference.properties.bookTitleAbbreviation ? taxonNameInReference(reference) : title(reference),
                showPage,
            ].filter(Boolean).join(': ');
        },
        async fetchData() {
            if ((this.lastPage !== 0 && this.lastPage <= this.currentPage) || this.isLoading) {
                return;
            }

            try {
                this.isLoading = true;
                const page = this.currentPage + 1;
                const { data: { data, lastPage, total } } = await this.axios.get('/search-taxon-names', {
                    params: {
                        keywords: `person_id: ${this.$route.params.id}`,
                        strict: 0,
                        page,
                        direction: this.direction,
                        sortby: this.sortby,
                        perPage: this.perPage,
                    },
                });

                this.total = total;
                this.lastPage = lastPage;
                this.currentPage = page;
                this.taxonNames = this.taxonNames.concat(data);
                if (this.taxonNames.length === 0) this.$emit('hide', 'taxon-name');
                this.isLoading = false;
            } catch (e) {
                this.isLoading = false;
            }
        },
    },
    components: {
        TaxonNameLabel,
        AuthorName,
        SortButton,
        Loading,
    },
};
</script>
<style lang="scss" scoped>
.table {
    th {
        position: sticky;
        background-color: white;
        top: 0;
    }

    th, td {
        line-height: 2rem;
        padding-left: 2rem;
    }
}
</style>
