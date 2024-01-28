<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>
                {{ $t('taxonName.rank') }}
            </th>
            <th>{{ $t('taxonName.type') }}</th>
            <th>
                <sort-button :id="'taxon_name'"
                             :direction="direction"
                             :on-click="onSortBy"
                             :sortby="sortby">
                    {{ $t('common.taxonName') }}
                </sort-button>
            </th>
            <th>
                {{ $t('common.authors') }}
            </th>
            <th>{{ $t('common.referenceFrom') }}</th>
            <th>
                <sort-button :id="'publish_year'"
                             :direction="direction"
                             :on-click="onSortBy"
                             :sortby="sortby">
                    {{ $t('common.year') }}
                </sort-button>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="usage in usages">

            <!-- 階層 -->
            <td v-text="usage.taxonName.rank.display[$i18n.locale()]"></td>
            <td>
                <status-with-indications
                    :indications="usage.properties.indications || []"
                    :nomenclature="usage.taxonName.nomenclature"
                    :status="usage.status"/>
            </td>
            <!-- 學名 -->
            <td>
                <router-link :to="{name: 'taxon-name-page', params: {id: usage.taxonName.id}}"
                             class="my-link">
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
                <router-link :to="{name: 'reference-page', params: {id: usage.reference.id}}" class="my-link">
                    {{
                        showReference(usage.reference)
                    }}
                </router-link>
            </td>
            <td v-text="usage.reference.publishYear"></td>
        </tr>
        </tbody>
    </table>
</template>
<script>
import AuthorName from '../../AuthorName.vue';
import SortButton from '../../SortButton.vue';
import StatusWithIndications from '../../StatusWithIndications.vue';
import TaxonNameLabel from '../TaxonNameLabel.vue';
import { subTitle } from '../../../utils/preview/reference';

export default {
    props: {
        type: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            direction: '',
            sortby: '',
            usages: [],
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            try {
                const {
                    data: {
                        data,
                    },
                } = await this.axios.get(`/taxon-names/${this.$route.params.id}/accepted?sortby=${this.sortby}&direction=${this.direction}`);

                if (data.length > 0) {
                    this.$emit('toggle', 'accepted', true);
                }

                this.usages = data;
            } catch (e) {
                this.$emit('toggle', 'accepted', false);
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
            return subTitle(v, false);
        },
    },
    components: {
        TaxonNameLabel,
        StatusWithIndications,
        SortButton,
        AuthorName,
    },
};
</script>
