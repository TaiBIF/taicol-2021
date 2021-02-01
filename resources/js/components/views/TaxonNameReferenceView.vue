<template>
    <div>
        <table class="table is-fullwidth is-hoverable has-text-left">
            <thead>
            <tr>
                <th>作者</th>
                <th>文獻</th>
                <th>{{ $t('forms.reference.publishYear') }}</th>
                <th>文獻中處理</th>
                <th>文獻中學名寫法</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="usage in usages">
                <!-- 作者 -->
                <td>
                    <p class="no-wrap"
                       v-text="showAuthors(usage.reference.authors)"/>
                </td>

                <!-- 文獻 -->
                <td v-text="usage.reference.subtitle"></td>
                <!-- 年份 -->
                <td v-text="usage.reference.publishYear"></td>
                <td>
                    <span v-if="usage.status ==='accepted'">valid</span>
                    <span v-else>correct</span>
                </td>
                <td v-text="usage.nameInReference"></td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
    import AuthorName from '../AuthorName';
    import SortButton from '../SortButton';
    import { factory } from '../../utils/preview/reference';
    import { comboLast } from '../../utils/preview/person';
    import StatusWithIndications from '../StatusWithIndications';

    export default {
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
                this.axios.get(`/taxon-names/${this.$route.params.id}/references`)
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
            showReference(v) {
                return factory(this.type)([v]);
            },
            showAuthors(authors) {
                return comboLast(authors);
            }
        },
        components: { StatusWithIndications, SortButton, AuthorName },
    }
</script>
