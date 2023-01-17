<template>
    <div class="page">
        <!-- 筆數結果顯示 -->
        <div class="help text-center">
            <template v-if="total">共計 {{ total }} 筆資料 · 第 {{ currentPage }} 頁</template>
            <span v-else-if="pageStatus === $c.PAGE_IS_SUCCESS" class="is-danger">查無結果</span>
        </div>
        <table class="table w-full  is-hoverable has-text-left table-fixed">
            <thead>
            <tr>
                <th class="w-[50%]">
                    <sort-button :id="'name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        人名
                    </sort-button>
                </th>
                <th class="w-[150px]">
                    <sort-button :id="'abbreviation_name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        縮寫
                    </sort-button>
                </th>
                <th class="w-[50%]">
                    原母語完整名
                </th>
                <th class="w-[140px]">生卒年/活躍年代</th>
                <th class="w-[170px]">研究領域</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="person in persons">
                <!-- 人名 -->
                <td>
                    <router-link :to="`/persons/${person.id}`" class="my-link">
                        {{ fullName(person) }}
                    </router-link>
                </td>

                <!-- 縮寫 -->
                <td>
                    <router-link :to="`/persons/${person.id}`" class="my-link">
                        {{ person.abbreviationName }}
                    </router-link>
                </td>

                <!-- 原母語完整名 -->
                <td>
                    <router-link :to="`/persons/${person.id}`" class="my-link">
                        {{ person.originalFullName }}
                    </router-link>
                </td>

                <!-- 生卒年/活躍年代 -->
                <td>
                    <span v-if="person.yearOfBirth || person.yearOfDeath"
                          v-text="`${person.yearOfBirth || ''}-${person.yearOfDeath || ''}`"></span>
                    <span v-else v-text="person.yearOfPublication"></span>
                </td>

                <!-- 研究領域 -->
                <td
                    v-text="person.biologyDepartments.map(
                            (d) => $t(`forms.person.biologyDepartmentOptions.${d}`)
                        ).join(', ')"
                ></td>
            </tr>
            <tr>
                <td class="text-center" colspan="7">
                    <pagination :current-page="currentPage"
                                :per-page="perPage"
                                :total="total"
                                v-on:change="onChangePage"
                    />
                    <span class="caption">共計 {{ total }} 筆資料</span>
                    <loading v-if="pageStatus === $c.PAGE_IS_LOADING"/>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import AutoComplete from '../components/AutoComplete.vue';
import Loading from '../components/Loading.vue';
import SortButton from '../components/SortButton.vue';
import Pagination from '../components/Pagination.vue';
import Searchbar from '../components/Searchbar.vue';
import FavoriteButton from '../components/FavoriteButton.vue';

import { fullName } from '../utils/preview/person';

export default {
    data() {
        return {
            pageStatus: this.$c.PAGE_IS_INITIAL,
            currentPage: parseInt(this.$route.query?.page, 10) || 1,
            lastPage: 1,
            sortby: this.$route.query?.sortby || '',
            direction: this.$route.query?.direction || '',
            perPage: 20,
            total: 0,
            persons: [],
            intersectionObserver: null,
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        fullName,
        onChangePage(page) {
            const p = parseInt(page, 10);
            if (p !== this.currentPage) {
                this.$router.replace({ query: { ...this.$route.query, page: p } });
                this.currentPage = p;
                this.fetchData();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            }
        },
        onSortBy(column, newDirection) {
            const { sortby, direction } = this.$route.query;
            if (sortby === column && direction === newDirection) {
                return;
            }

            this.$router.replace({ query: { ...this.$route.query, sortby: column, direction: newDirection } });
            this.sortby = column;
            this.direction = newDirection;

            this.fetchData();
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        },
        fetchData() {
            if (this.pageStatus === this.$c.PAGE_IS_LOADING) {
                return;
            }

            this.pageStatus = this.$c.PAGE_IS_LOADING;
            this.axios.get(
                '/search-persons',
                {
                    params: {
                        keywords: this.$route.query.keywords,
                        strict: 0,
                        page: this.currentPage,
                        sortby: this.sortby,
                        direction: this.direction,
                        perPage: this.perPage,
                    },
                },
            )
                .then(({ data: { data, total, lastPage } }) => {
                    this.persons = data;
                    this.pageStatus = this.$c.PAGE_IS_SUCCESS;
                    this.lastPage = lastPage;
                    this.total = total;
                });
        },
    },
    components: {
        FavoriteButton,
        Pagination,
        SortButton,
        AutoComplete,
        Loading,
        Searchbar,
    },
};
</script>
<style lang="scss" scoped>
.page {
    .table {
        position: relative;
        z-index: 10;

        th {
            background-color: white;
            z-index: 60;
            position: sticky;
            top: 0;
            color: $grey;
        }
    }
}
</style>
