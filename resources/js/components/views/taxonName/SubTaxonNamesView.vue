<template>
    <div>
        <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
            <thead>
            <tr>
                <th>
                    <sort-button :id="'rank'" :on-click="onSortBy" :sortby="sortby" :direction="direction">
                        {{ $t('forms.taxonName.rank') }}
                    </sort-button>
                </th>
                <th>
                    <sort-button :id="'taxon_name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        學名
                    </sort-button>
                </th>
                <th>
                    命名者
                </th>
                <th v-text="$t('forms.taxonName.reference')">發表文獻</th>
                <th>
                    <sort-button :id="'publish_year'" :on-click="onSortBy" :sortby="sortby" :direction="direction">
                        {{ $t('forms.reference.publishYear') }}
                    </sort-button>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="taxonName in taxonNames">

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
                    <router-link v-if="taxonName.reference"
                                 :to="`/references/${taxonName.reference.id}`" class="my-link">
                        {{ rName(taxonName.reference, taxonName.properties.usage.showPage) }}
                    </router-link>
                    <span v-else v-text="taxonName.properties.referenceName"></span>
                </td>
                <td>
                    <span v-if="taxonName.reference"
                          v-text="taxonName.reference.publishYear"/>
                </td>
            </tr>
            <tr>
                <td colspan="8">
                    <p v-if="lastPage < currentPage" class="text-gray-300 text-center">資料最底，共計 {{ total }} 筆</p>
                    <span class="caption"></span>
                    <loading v-if="isLoading"></loading>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import AuthorName from '../../AuthorName';
import SortButton from '../../SortButton';
import { factory, taxonNameInReference, title } from '../../../utils/preview/reference';
import StatusWithIndications from '../../StatusWithIndications';
import TaxonNameLabel from '../TaxonNameLabel';
import Loading from '../../Loading';

export default {
    props: {
        taxonName: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            direction: '',
            sortby: '',
            currentPage: 0,
            lastPage: 0,
            perPage: 20,
            total: 0,
            taxonNames: [],
            isLoading: false,
        }
    },
    mounted() {
        this.fetchData();
        const app = this;
        app.intersectionObserver = new IntersectionObserver(function (entries) {
            if (entries[0].intersectionRatio > 0) {
                app.fetchData();
            }
        }, {rootMargin: '0px 0px 500px 0px'});
        app.intersectionObserver.observe(document.querySelector('.caption'));
    },
    methods: {
        async fetchData() {
            if ((this.lastPage !== 0 && this.lastPage < this.currentPage) || this.isLoading) {
                return;
            }

            this.isLoading = true;

            try {
                const page = this.currentPage + 1;
                const {
                    data: {data, lastPage, total}
                } = await this.axios.get(`/taxon-names/${ this.$route.params.id }/sub-taxon-names`, {
                    params: {
                        page,
                        direction: this.direction,
                        sortby: this.sortby,
                        perPage: this.perPage,
                    }
                });

                this.total = total;
                this.lastPage = lastPage;
                this.currentPage = page;
                this.taxonNames = this.taxonNames.concat(data);

                if (taxonNames.length > 0) {
                    this.$emit('toggle', 'sub-taxon-names', true);
                }
            } catch (e) {
                this.$emit('toggle', 'sub-taxon-names', false);
            }

            this.isLoading = false;
        },
        onSortBy(column, direction) {
            this.sortby = column;
            this.direction = direction;
            this.taxonNames = [];
            this.currentPage = 0;
            this.fetchData();
        },
        showReference(v) {
            return factory(this.type)([v]);
        },
        rName(reference, showPage) {
            return [
                reference.properties.bookTitleAbbreviation ? taxonNameInReference(reference) : title(reference),
                showPage,
            ].filter(Boolean).join(': ');
        },
    },
    components: {TaxonNameLabel, StatusWithIndications, SortButton, AuthorName, Loading},
}
</script>
