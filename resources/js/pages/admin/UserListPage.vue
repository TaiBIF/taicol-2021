<template>
    <page :preload="onFetchUsers">
        <div>
            <router-link :to="`/admin/users/create`" class="button is-pulled-right">新增使用者</router-link>
        </div>

        <table class="table is-fullwidth is-hoverable">
            <thead>
            <tr>
                <td>ID</td>
                <td>
                    <sort-button :id="'last_name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        姓名
                    </sort-button>
                </td>
                <td>帳號</td>
                <td>角色</td>
                <td>
                    <sort-button :id="'status'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                        狀態
                    </sort-button>
                </td>
                <td></td>
            </tr>
            </thead>
            <tbody>
            <tr v-for="user in users" :class="{'text-gray-300': user.status === 0}">
                <td v-text="user.id"></td>
                <td v-text="`${user.name}`"></td>
                <td v-text="user.email"></td>
                <td class="text-center">
                    <span v-if="user.roleId === 1">管理者</span>
                    <span v-else>一般使用者</span>
                </td>
                <td>
                    <span class="px-3" v-if="user.status === 0">停用中</span>
                    <span class="px-3 bg-green-500 text-white" v-else-if="user.status === 1">啟用中</span>
                    <span class="px-3 bg-yellow-400 text-white" v-else-if="user.status === 2">等待開通</span>
                </td>
                <td>
                    <router-link :to="`/admin/users/${user.id}`" class="button">編輯</router-link>
                    <button class="button" v-if="user.status === 2"
                            v-on:click="() => onActiveUser(user)">開通
                    </button>
                    <button class="button" v-else-if="user.status === 1"
                            v-on:click="() => onDisableUser(user)">停用帳號
                    </button>
                </td>
            </tr>
            <tr>
                <td class="has-text-centered has-text-weight-bold has-text-grey" colspan="10">
                    <pagination :current-page="currentPage"
                                :per-page="perPage"
                                :total="total"
                                v-on:change="onChangePage"
                    />
                    <span class="caption">共計 {{ total }} 筆資料</span>
                </td>
            </tr>
            </tbody>
        </table>
    </page>
</template>
<script>
import page from '../Page';
import Pagination from '../../components/Pagination';
import Loading from '../../components/Loading';
import SortButton from "../../components/SortButton";

export default {
    data() {
        return {
            users: [],
            currentPage: parseInt(this.$route.query?.page) || 1,
            lastPage: 1,
            perPage: 20,
            total: 0,
            sortby: this.$route.query?.sortby || '',
            direction: 'asc',
        }
    },
    methods: {
        async onFetchUsers() {
            try {
                const {data: {data, total, lastPage}} = await this.axios.get('users', {
                    params: {
                        page: this.currentPage,
                        direction: this.direction,
                        sortby: this.sortby,
                        perPage: this.perPage,
                    }
                });
                this.users = data;
                this.total = total;
                this.lastPage = lastPage;
                return 200;
            } catch (e) {

            }
        },
        async onActiveUser(user) {
            if (confirm(`確定要開通 ${ user.email } ?`)) {
                try {
                    const {data} = await this.axios.put(`/users/${ user.id }/status`, {
                        ...user,
                        status: 1
                    });
                    user.status = 1;
                } catch (e) {
                    alert('發生錯誤');
                }
            }
        },
        async onDisableUser(user) {
            if (confirm(`確定要停用 ${ user.email } ?`)) {
                try {
                    const {data} = await this.axios.put(`/users/${ user.id }/status`, {
                        status: 0
                    });

                    user.status = 0;
                } catch (e) {
                    alert('發生錯誤');
                }
            }
        },
        onChangePage(page) {
            const p = parseInt(page);
            if (p !== this.currentPage) {
                this.$router.replace({query: {...this.$route.query, page: p}});
                this.currentPage = p;
                this.onFetchUsers();
                window.scrollTo({top: 0});
            }
        },

        onSortBy(column, newDirection) {
            const {sortby, direction} = this.$route.query;
            if (sortby === column && direction === newDirection) {
                return;
            }

            this.$router.replace({query: {...this.$route.query, sortby: column, direction: newDirection}});
            this.sortby = column;
            this.direction = newDirection;

            this.onFetchUsers();
            window.scrollTo({top: 0});
        },
    },
    components: {SortButton, page, Pagination, Loading},
}
</script>
