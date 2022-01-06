<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>
                {{ $t('forms.taxonName.rank') }}
            </th>
            <th>類型</th>
            <th>
                <sort-button :id="'taxon_name'" :on-click="onSortBy" :sortby="sortby" :direction="direction">
                    學名
                </sort-button>
            </th>
            <th>
                命名者
            </th>
            <th>出處</th>
            <th>
                <sort-button :id="'publish_year'" :on-click="onSortBy" :sortby="sortby" :direction="direction">
                    年份
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
                    :nomenclature="usage.taxonName.nomenclature"
                    :status="usage.status"
                    :indications="usage.properties.indications"/>
            </td>
            <!-- 學名 -->
            <td>
                <router-link :to="`/taxon-names/${usage.taxonName.id}`" class="my-link">
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
                <router-link :to="`/references/${usage.reference.id}`" class="my-link">
                    {{
                        showReference({
                            showPage: usage.showPage,
                            figure: usage.figure,
                            target: usage.reference,
                        })
                    }}
                </router-link>
            </td>
            <td v-text="usage.reference.publishYear"></td>
        </tr>
        </tbody>
    </table>
</template>
<script>
    import AuthorName from '../../AuthorName';
    import SortButton from '../../SortButton';
    import { factory } from '../../../utils/preview/reference';
    import StatusWithIndications from '../../StatusWithIndications';
    import TaxonNameLabel from '../TaxonNameLabel';

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
            }
        },
        mounted() {
            this.fetchData()
        },
        methods: {
            async fetchData() {
                try {
                    const {
                        data: {
                            data,
                        },
                    } = await this.axios.get(`/taxon-names/${this.$route.params.id}/accepted?sortby=${this.sortby}&direction=${this.direction}`)

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
                return factory(this.type)([v]);
            },
        },
        components: { TaxonNameLabel, StatusWithIndications, SortButton, AuthorName },
    }
</script>
