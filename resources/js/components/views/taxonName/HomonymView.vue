<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>
                {{ $t('forms.taxonName.rank') }}
            </th>
            <th>
                學名
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
                    <template v-if="taxonName.rank.order > 30">
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
                    taxonName: taxonName,
                }"></author-name>
            </td>

            <!-- 發表文獻 -->
            <td>
                <router-link v-if="taxonName.reference" :to="`/references/${taxonName.reference.id}`" class="my-link">
                    {{ subTitle(taxonName.reference) }}
                </router-link>
                <span v-else v-text="taxonName.properties.referenceName"></span>
            </td>

            <td>
                <span v-if="taxonName.reference"
                      v-text="taxonName.reference.publishYear"
                ></span>
            </td>
        </tr>
        </tbody>
    </table>
</template>
<script>
    import AuthorName from '../../AuthorName';
    import SortButton from '../../SortButton';
    import StatusWithIndications from '../../StatusWithIndications';
    import { subTitle } from '../../../utils/preview/reference';

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
                taxonNames: [],
            }
        },
        mounted() {
            this.fetchData()
        },
        methods: {
            subTitle: (r) => subTitle(r, false),
            async fetchData() {
                try {
                    const {
                        data: {
                            data,
                        },
                    } = await this.axios.get(`/taxon-names/${this.$route.params.id}/homonyms?sortby=${this.sortby}&direction=${this.direction}`)

                    if (data.length > 0) {
                        this.$emit('toggle', 'homonym', true);
                    }

                    this.taxonNames = data;
                } catch (e) {
                    this.$emit('toggle', 'homonym', false);
                }
            },
            onSortBy(column, direction) {
                this.sortby = column;
                this.direction = direction;
                this.taxonNames = [];
                this.currentPage = 1;
                this.fetchData();
            },
        },
        components: { StatusWithIndications, SortButton, AuthorName },
    }
</script>
