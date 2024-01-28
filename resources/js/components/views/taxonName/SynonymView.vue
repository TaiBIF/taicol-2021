<template>
    <div>
        <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
            <thead>
            <tr>
                <th v-text="$t('taxonName.rank')"/>
                <th v-text="$t('taxonName.type')"/>
                <th>
                    <sort-button :id="'taxon_name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('common.taxonName') }}
                    </sort-button>
                </th>
                <th v-text="$t('common.authors')"/>
                <th v-text="$t('common.referenceFrom')"></th>
                <th>
                    <sort-button :id="'publish_year'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        {{ $t('taxonName.year') }}
                    </sort-button>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="usage in usages">

                <!-- 階層 -->
                <td v-text="usage.taxonName.rank.display[$i18n.locale()]"></td>

                <!-- 類型 -->
                <td>
                    <status-with-indications
                        :indications="usage.indications"
                        :nomenclature="usage.taxonName.nomenclature"
                        :status="usage.status"></status-with-indications>
                </td>

                <!-- 學名 -->
                <td>
                    <router-link
                        :to="{name: 'taxon-name-page', params: {id: usage.taxonName.id}}"
                        class="my-link"
                    >
                        <taxon-name-label :taxon-name="usage.taxonName"/>
                    </router-link>
                </td>

                <!-- 命名者 -->
                <td>
                    <author-name v-bind="{
                        authors: usage.taxonName.authors,
                        exAuthors: usage.taxonName.exAuthors,
                        type: usage.taxonName.nomenclature.group,
                        originalTaxonName: usage.taxonName.originalTaxonName,
                        publishYear: usage.taxonName.publishYear,
                        taxonName: usage.taxonName,
                    }"></author-name>
                </td>
                <td>
                    <router-link
                        :to="{name: 'reference-page', params: {id: usage.reference.id}}"
                        class="my-link"
                    >
                        {{
                            showReference(usage.reference)
                        }}
                    </router-link>
                </td>
                <td v-text="usage.reference.publishYear"></td>
            </tr>
            <tr>
                <td colspan="6">
                    <p v-if="lastPage < currentPage" class="text-gray-300 text-center">
                        {{ $t('common.bottomOfResults', {total}) }}
                    </p>
                    <span class="caption"></span>
                    <loading v-if="isLoading"></loading>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import AuthorName from '../../AuthorName.vue';
import SortButton from '../../SortButton.vue';
import { subTitle } from '../../../utils/preview/reference';
import StatusWithIndications from '../../StatusWithIndications.vue';
import TaxonNameLabel from '../TaxonNameLabel.vue';
import Loading from '../../Loading.vue';

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
            usages: [],
            isLoading: false,
        };
    },
    mounted() {
        const app = this;
        app.fetchData();

        app.intersectionObserver = new IntersectionObserver(((entries) => {
            if (entries[0].intersectionRatio > 0 && this.usages.length) {
                app.fetchData();
            }
        }), { rootMargin: '0px 0px 500px 0px' });
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
                    data: { data, lastPage, total },
                } = await this.axios.get(
                    `/taxon-names/${this.$route.params.id}/synonyms?sortby=${this.sortby}&direction=${this.direction}`,
                    {
                        params: {
                            page,
                            direction: this.direction,
                            sortby: this.sortby,
                            perPage: this.perPage,
                        },
                    },
                );

                if (data.length > 0) {
                    this.$emit('toggle', 'synonym', true);
                }

                this.usages = [...this.usages, ...data];

                this.total = total;
                this.lastPage = lastPage;
                this.currentPage = page;
            } catch (e) {
                this.$emit('toggle', 'synonym', false);
            }
            this.isLoading = false;
        },
        onSortBy(column, direction) {
            this.sortby = column;
            this.direction = direction;
            this.usages = [];
            this.currentPage = 0;
            this.fetchData();
        },
        showReference(v) {
            return subTitle(v, false);
        },
    },
    components: {
        Loading,
        TaxonNameLabel,
        StatusWithIndications,
        SortButton,
        AuthorName,
    },
};
</script>
