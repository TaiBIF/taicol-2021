<template>
    <div>
        <table class="table is-fullwidth is-hoverable has-text-left">
            <thead>
            <tr>
                <th>
                    {{ $t('forms.taxonName.rank') }}
                </th>
                <th>類型</th>
                <th>
                    學名
                </th>
                <th v-text="$t('forms.taxonName.reference')">發表文獻</th>
                <th>
                    {{ $t('forms.reference.publishYear') }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="usage in usages">

                <!-- 階層 -->
                <td v-text="usage.taxonName.rank.display[$i18n.locale()]"></td>
                <td>
                    <status-with-indications :status="usage.status" :indications="usage.properties.indications"></status-with-indications>
                </td>
                <!-- 學名 -->
                <td>
                    <template v-if="usage.taxonName.rank.order > 30">
                        <i>{{ usage.taxonName.name }}</i>
                    </template>
                    <template v-else>
                        {{ usage.taxonName.name }}
                    </template>
                </td>
                <td v-text="usage.taxonName.properties.referenceName"></td>
                <td v-text="usage.reference.publishYear"></td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
    import AuthorName from '../AuthorName';
    import SortButton from '../SortButton';
    import StatusWithIndications from '../StatusWithIndications';

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
                usages: [],
            }
        },
        mounted() {
            this.fetchData()
        },
        methods: {
            fetchData() {
                this.axios.get(`/taxon-names/${this.$route.params.id}/homonyms`)
                    .then(({ data: { data, total } }) => {
                        this.usages = data;
                    });
            },
            onSortBy() {
                this.sortby = column;
                this.direction = direction;
                this.usages = [];
                this.currentPage = 1;
                this.fetchData();
            },
        },
        components: { StatusWithIndications, SortButton, AuthorName },
    }
</script>
