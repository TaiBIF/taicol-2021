<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>採集者</th>
            <th style="white-space: nowrap">採集號</th>
            <th style="white-space: nowrap">學名</th>
            <th>命名者</th>
            <th>採集國家</th>
            <th style="white-space: nowrap">採集地點</th>
            <th>採集日期</th>
            <th style="white-space: nowrap">存放標本館</th>
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
                <router-link :to="`/taxon-names/${typeSpecimen.taxonName.id}`" class="my-link">
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
            <td>{{ typeSpecimen.citationNoteNumber }}</td>
        </tr>
        </tbody>
    </table>
</template>
<script>
    import AuthorName from '../../AuthorName';
    import TaxonNameLabel from '../TaxonNameLabel';

    export default {
        data() {
            return {
                typeSpecimens: [],
                direction: '',
                sortby: '',
                total: 0,
            }
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
    }
</script>
<style lang="scss" scoped>
    .table {

        th, td {
            line-height: 2rem;
            padding-left: 2rem;
        }
    }
</style>
