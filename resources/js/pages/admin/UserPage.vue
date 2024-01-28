<template>
    <page :preload="onFetchUser" class="container h-full flex flex-col gap-4">
        <div class="pt-2 flex items-center">
            <p class="font-bold text-3xl inline mr-3">{{ isCreatePage ? $t('user.create') : $t('user.edit') }}</p>
            <template v-if="user.id">
                <span v-if="user.status === 0" class="px-3 bg-red-600 text-white">{{
                        $t('user.statusOptions.disabled')
                    }}</span>
                <span v-else-if="user.status === 1"
                      class="px-3 bg-green-500 text-white">{{ $t('user.statusOptions.activated') }}</span>
                <span v-else-if="user.status === 2"
                      class="px-3 bg-yellow-400 text-white">{{ $t('user.statusOptions.waiting') }}</span>
            </template>
        </div>
        <div class="box grow mb-0">
            <div class="form">
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('person.name')"/>
                            <div class="field-body">
                                <div class="field">
                                    <general-input v-model="user.name"
                                                   :errors="errors['name']"
                                                   :placeholder="$t('person.name')"/>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('person.email')"/>
                            <general-input v-model="user.email"
                                           :disabled="!isCreatePage"
                                           :errors="errors['email']"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label :class="{'is-marked': isCreatePage}" class="label">
                                {{ $t('person.password') }}
                                <span v-if="!isCreatePage">(如不需更改，不用輸入)</span>
                            </label>

                            <general-input v-model="user.password" :errors="errors['password']" type="password"/>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label :class="{'is-marked': isCreatePage}" class="label"
                                   v-text="$t('person.passwordConfirm')"/>
                            <general-input v-model="user.passwordConfirm" type="password"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-12">
                        <div class="field">
                            <label class="label" v-text="$t('person.biologyDepartment')"/>
                            <div class="control">
                                <label v-for="department in biologyDepartmentOptions" :for="`o_${department}`"
                                       class="checkbox mr-3">
                                    <input :id="`o_${department}`" v-model="user.biologyDepartments" :value="department"
                                           class="checkbox"
                                           type="checkbox">
                                    {{ $t(`person.biologyDepartmentOptions.${department}`) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-12">
                        <div class="field">
                            <label class="label" v-text="$t('user.role')"/>
                            <div class="control">
                                <label :for="`o_role_user`" class="checkbox mr-3">
                                    <input :id="`o_role_user`" v-model="user.role" :value="0"
                                           class="checkbox"
                                           type="radio"> {{ $t('user.roles.general') }}
                                </label>
                                <label :for="`o_role_admin`" class="checkbox mr-3">
                                    <input :id="`o_role_admin`" v-model="user.role" :value="1"
                                           class="checkbox"
                                           type="radio"> {{ $t('user.roles.admin') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="buttons is-right pb-4">
            <p v-if="user.id" class="text-gray-300 mr-2">{{ $t('user.lastModified') }}: {{ user.updatedAt }}</p>
            <div v-if="user.id">
                <button v-if="user.status === 1" class="button"
                        v-on:click="() => onDisableUser(user)">{{ $t('user.disable') }}
                </button>
                <button v-if="user.status === 0" class="button"
                        v-on:click="() => onActiveUser(user)">{{ $t('user.reactivate') }}
                </button>
                <button v-if="user.status === 2" class="button"
                        v-on:click="() => onActiveUser(user)">{{ $t('user.activate') }}
                </button>
            </div>
            <button class="button is-success"
                    v-on:click="onSave"
                    v-text="$t('common.save')"/>
        </div>
    </page>
</template>

<script>
import page from '../Page.vue';
import biologyDepartmentOptions from '../../utils/options/biologyDepartments';
import GeneralInput from '../../components/GeneralInput.vue';
import CountrySelect from '../../components/selects/CountrySelect.vue';
import { openNotify } from '../../utils';

export default {
    data() {
        return {
            biologyDepartmentOptions,
            isCreatePage: this.$route.params.id === 'create',
            user: {
                name: '',
                email: '',
                password: '',
                passwordConfirm: '',
                status: 0,
                role: 0,
                biologyDepartments: [],
            },
            errors: {},
        };
    },
    methods: {
        async onFetchUser() {
            const { id } = this.$route.params;
            if (id === 'create') {
                return 200;
            }
            try {
                const { data: { data } } = await this.axios.get(`/users/${id}`);
                this.user = {
                    id: data.id,
                    name: data.name,
                    email: data.email,
                    status: data.status,
                    biologyDepartments: data.biologyDepartments,
                    role: data.role,
                    updatedAt: data.updatedAt,
                };

                return 200;
            } catch (e) {
            }
        },
        onSave() {
            const method = this.isCreatePage ? 'post' : 'put';
            const url = this.isCreatePage ? '/users' : `/users/${this.$route.params.id}`;
            this.axios({
                method,
                url,
                data: this.user,
            })
                .then(() => {
                    openNotify(this.$t('common.saveSuccess'));
                    this.$router.push('/admin/users');
                })
                .catch(({ errors }) => {
                    this.errors = errors;
                });
        },
        async onActiveUser(user) {
            if (confirm(`確定要啟用 ${user.email} ?`)) {
                try {
                    await this.axios.put(`/users/${user.id}/status`, {
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
    },
    components: { page, GeneralInput, CountrySelect },
};
</script>
<style lang="scss" scoped>
.box {
    margin-bottom: 0 !important;
}
</style>
