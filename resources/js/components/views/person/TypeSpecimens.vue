<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>{{ $t('typeSpecimen.collector') }}</th>
            <th style="white-space: nowrap">{{ $t('typeSpecimen.collectorNumber') }}</th>
            <th style="white-space: nowrap">{{ $t('common.taxonName') }}</th>
            <th>{{ $t('common.authors') }}</th>
            <th>{{ $t('typeSpecimen.country') }}</th>
            <th style="white-space: nowrap">{{ $t('typeSpecimen.locality') }}</th>
            <th>{{ $t('typeSpecimen.collectDate') }}</th>
            <th style="white-space: nowrap">{{ $t('typeSpecimen.shortHerbarium') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="typeSpecimen in typeSpecimens">
            <!-- 採集者 -->
            <td>
                <p v-for="collector in typeSpecimen.collectors" v-text="collector.fullName"/>
            </td>

            <!-- 採集號 -->
            <td>{{ typeSpecimen.collectorNumber }}</td>

            <!-- 學名 -->
            <td>
                <router-link
                    :to="{name: 'taxon-name-page', params: {id: typeSpecimen.taxonName.id}}"
                    class="my-link"
                >
                    <taxon-name-label :taxon-name="typeSpecimen.taxonName"/>
                </router-link>
            </td>

            <!-- 命名者 -->
            <td>
                <author-name v-bind="{
                    authors: typeSpecimen.taxonName.authors,
                    exAuthors: typeSpecimen.taxonName.exAuthors,
                    type: typeSpecimen.taxonName.nomenclature.group,
                    originalTaxonName: typeSpecimen.taxonName.originalTaxonName,
                    publishYear: typeSpecimen.taxonName.publishYear,
                }"/>
            </td>

            <!-- 採集國家 -->
            <td>{{ typeSpecimen.country.display['en-us'] }}</td>

            <!-- 採集地點 -->
            <td>{{ typeSpecimen.locality }}</td>

            <!-- 採集日期 -->
            <td>
                {{
                    [
                        typeSpecimen.collectionDay,
                        typeSpecimen.collectionMonth,
                        typeSpecimen.collectionYear,
                    ].filter(Boolean).join(' ')
                }}
            </td>

            <!-- 存放標本館 -->
            <td>{{ typeSpecimen.specimens[0] ? typeSpecimen.specimens[0].herbarium : '' }}</td>
        </tr>
        </tbody>
    </table>
</template>
<script>
import AuthorName from '../../AuthorName.vue';
import TaxonNameLabel from '../TaxonNameLabel.vue';

export default {
    data() {
        return {
            typeSpecimens: [],
            direction: '',
            sortby: '',
            total: 0,
        };
    },
    async mounted() {
        await this.fetchData();
    },
    methods: {
        async fetchData() {
            try {
                const { data } = await this.axios.get(`/persons/${this.$route.params.id}/type-specimens`);

                this.total = data.length;
                this.typeSpecimens = data;

                if (data.length === 0) {
                    this.$emit('hide', 'type-specimen');
                }
            } catch (e) {

            }
        },
    },
    components: {
        TaxonNameLabel,
        AuthorName,
    },
};
</script>
<style lang="scss" scoped>
.table {

    th, td {
        line-height: 2rem;
        padding-left: 2rem;
    }
}
</style>
