<template>
    <page :preload="onFetchUsers">
        <div class="overflow-y-auto h-full">
            <div class="container">
                <div class="pt-2">
                    <router-link
                        :to="{name: 'admin-user-edit', params: {id: 'create'}}"
                        class="button is-pulled-right">{{ $t('user.create') }}
                    </router-link>
                </div>
                <table class="table is-fullwidth is-hoverable">
                    <thead class="">
                    <tr>
                        <th>ID</th>
                        <th>
                            <sort-button :id="'last_name'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                                {{ $t('user.name') }}
                            </sort-button>
                        </th>
                        <th>{{ $t('user.account') }}</th>
                        <th>{{ $t('user.role') }}</th>
                        <th>
                            <sort-button :id="'status'" :direction="direction" :on-click="onSortBy" :sortby="sortby">
                                {{ $t('user.account') }}
                            </sort-button>
                        </th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="user in users" :class="{'text-gray-300': user.status === 0}">
                        <td v-text="user.id"></td>
                        <td v-text="`${user.name}`"></td>
                        <td v-text="user.email"></td>
                        <td class="text-center">
                            <span v-if="user.roleId === 1">{{ $t('user.roles.admin') }} </span>
                            <span v-else>{{ $t('user.roles.general') }}</span>
                        </td>
                        <td>
                            <span v-if="user.status === 0" class="px-3"
                                  v-text="$t('user.statusOptions.disabled')"/>
                            <span v-else-if="user.status === 1" class="px-3 bg-green-500 text-white"
                                  v-text="$t('user.statusOptions.activated')"/>
                            <span v-else-if="user.status === 2" class="px-3 bg-yellow-400 text-white"
                                  v-text="$t('user.statusOptions.waiting')"/>
                        </td>
                        <td>
                            <div class="buttons">
                                <router-link
                                    :to="{name: 'admin-user-edit', params: {id: user.id}}"
                                    class="button">{{ $t('common.edit') }}
                                </router-link>
                                <button v-if="user.status === 2" class="button"
                                        v-on:click="() => onActiveUser(user)">{{ $t('user.activate') }}
                                </button>
                                <button v-else-if="user.status === 1" class="button"
                                        v-on:click="() => onDisableUser(user)">{{ $t('user.disable') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center has-text-weight-bold has-text-grey" colspan="10">
                            <pagination :current-page="currentPage"
                                        :per-page="perPage"
                                        :total="total"
                                        v-on:change="onChangePage"
                            />
                            <span class="caption" v-text="$t('common.totalRowsNumber', {total})"></span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </page>
</template>
<script>
import page from '../Page.vue';
import Pagination from '../../components/Pagination.vue';
import Loading from '../../components/Loading.vue';
import SortButton from '../../components/SortButton.vue';

export default {
    data() {
        return {
            users: [],
            currentPage: parseInt(this.$route.query?.page, 10) || 1,
            lastPage: 1,
            perPage: 20,
            total: 0,
            sortby: this.$route.query?.sortby || '',
            direction: 'asc',
        };
    },
    methods: {
        async onFetchUsers() {
            try {
                const { data: { data, total, lastPage } } = await this.axios.get('users', {
                    params: {
                        page: this.currentPage,
                        direction: this.direction,
                        sortby: this.sortby,
                        perPage: this.perPage,
                    },
                });
                this.users = data;
                this.total = total;
                this.lastPage = lastPage;
                return 200;
            } catch (e) {

            }
        },
        async onActiveUser(user) {
            if (confirm(`確定要開通 ${user.email} ?`)) {
                try {
                    const { data } = await this.axios.put(`/users/${user.id}/status`, {
                        ...user,
                        status: 1,
                    });
                    user.status = 1;
                } catch (e) {
                    alert('發生錯誤');
                }
            }
        },
        async onDisableUser(user) {
            if (confirm(`確定要停用 ${user.email} ?`)) {
                try {
                    const { data } = await this.axios.put(`/users/${user.id}/status`, {
                        status: 0,
                    });

                    user.status = 0;
                } catch (e) {
                    alert('發生錯誤');
                }
            }
        },
        onChangePage(page) {
            const p = parseInt(page, 10);
            if (p !== this.currentPage) {
                this.$router.replace({ query: { ...this.$route.query, page: p } });
                this.currentPage = p;
                this.onFetchUsers();
                window.scrollTo({ top: 0 });
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

            this.onFetchUsers();
            window.scrollTo({ top: 0 });
        },
    },
    components: {
        SortButton, page, Pagination, Loading,
    },
};
</script>
<style lang="scss" scoped>
.table {
    th {
        background-color: white;
        z-index: 60;
        position: sticky;
        top: 0;
    }
}
</style>
