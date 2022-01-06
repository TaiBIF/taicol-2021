<template>
    <page :preload="onFetchUser">
        <div>
            <p class="title inline mr-3">{{ isCreatePage ? '新增使用者' : '編輯使用者' }}</p>
            <template v-if="user.id">
                <span class="px-3 bg-red-600 text-white" v-if="user.status === 0">停用中</span>
                <span class="px-3 bg-green-500 text-white" v-else-if="user.status === 1">啟用中</span>
                <span class="px-3 bg-yellow-400 text-white" v-else-if="user.status === 2">等待開通</span>
            </template>
        </div>
        <div class="box mt-4">
            <div class="form">
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('forms.person.name')"/>
                            <div class="field-body">
                                <div class="field">
                                    <general-input :errors="errors['name']"
                                                   :placeholder="$t('forms.person.name')"
                                                   v-model="user.name"/>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('forms.person.email')"/>
                            <general-input :errors="errors['email']"
                                           :disabled="!isCreatePage"
                                           v-model="user.email"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" :class="{'is-marked': isCreatePage}">
                                {{ $t('forms.person.password') }}
                                <span v-if="!isCreatePage">(如不需更改，不用輸入)</span>
                            </label>

                            <general-input :errors="errors['password']" type="password" v-model="user.password"/>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label class="label" :class="{'is-marked': isCreatePage}"
                                   v-text="$t('forms.person.passwordConfirm')"/>
                            <general-input type="password" v-model="user.passwordConfirm"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-12">
                        <div class="field">
                            <label class="label" v-text="$t('forms.person.biologyDepartment')"/>
                            <div class="control">
                                <label :for="`o_${department}`" class="checkbox mr-3"
                                       v-for="department in biologyDepartmentOptions">
                                    <input :id="`o_${department}`" :value="department" class="checkbox"
                                           type="checkbox"
                                           v-model="user.biologyDepartments">
                                    {{ $t(`forms.person.biologyDepartmentOptions.${department}`) }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-12">
                        <div class="field">
                            <label class="label" v-text="'角色'"/>
                            <div class="control">
                                <label :for="`o_role_user`" class="checkbox mr-3">
                                    <input :id="`o_role_user`" :value="0" class="checkbox"
                                           type="radio"
                                           v-model="user.role"> 一般使用者
                                </label>
                                <label :for="`o_role_admin`" class="checkbox mr-3">
                                    <input :id="`o_role_admin`" :value="1" class="checkbox"
                                           type="radio"
                                           v-model="user.role"> 管理員
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column">
                        <div class="buttons is-right">
                            <p class="text-gray-300 mr-2" v-if="user.id">最後編輯時間：{{ user.updatedAt }}</p>
                            <div v-if="user.id">
                                <button class="button" v-if="user.status === 1"
                                        v-on:click="() => onDisableUser(user)">停用</button>
                                <button class="button" v-if="user.status === 0"
                                        v-on:click="() => onActiveUser(user)">重新啟用</button>
                                <button class="button" v-if="user.status === 2"
                                        v-on:click="() => onActiveUser(user)">開通</button>
                            </div>
                            <button class="button is-success"
                                    v-on:click="onSave"
                                    v-text="$t('forms.actions.save')"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </page>
</template>

<script>
    import page from '../Page';
    import biologyDepartmentOptions from '../../utils/options/biologyDepartments';
    import GeneralInput from "../../components/GeneralInput";
    import CountrySelect from "../../components/selects/CountrySelect";
    import {openNotify} from "../../utils";

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
                errors: {}
            }
        },
        methods: {
            async onFetchUser() {
                const id = this.$route.params.id;
                if (id === 'create') {
                    return 200;
                } else {
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
                    } catch (e) {}
                }
            },
            onSave() {
                const method = this.isCreatePage ? 'post' : 'put';
                const url = this.isCreatePage ? '/users' : `/users/${this.$route.params.id}`;
                this.axios({
                    method, url,
                    data: this.user,
                })
                    .then(() => {
                        openNotify(this.$t('forms.saveSuccess'));
                        this.$router.push(`/admin/users`);
                    })
                    .catch(({ errors }) => {
                        this.errors = errors;
                    });
            },
            async onActiveUser(user) {
                if (confirm(`確定要啟用 ${user.email} ?`)) {
                    try {
                        await this.axios.put(`/users/${user.id}/status`, {
                            status: 1
                        });
                        user.status = 1;
                    } catch(e) {
                        alert('發生錯誤');
                    }
                }
            },
            async onDisableUser(user) {
                if (confirm(`確定要停用 ${user.email} ?`)) {
                    try {
                        const { data } = await this.axios.put(`/users/${user.id}/status`, {
                            status: 0
                        });

                        user.status = 0;
                    } catch(e) {
                        alert('發生錯誤');
                    }
                }
            }
        },
        components: { page, GeneralInput, CountrySelect },
    }
</script>
