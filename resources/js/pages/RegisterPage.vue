<template>
    <div class="container">
        <div class="box">
            <p class="title is-3" v-text="$t('loginPage.createAccount')"/>
            <div class="form">
                <div class="form">
                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('person.name')"/>
                                <div class="field-body">
                                    <div class="field">
                                        <general-input v-model="name"
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
                                <general-input v-model="email"
                                               :errors="errors['email']"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('person.password')"/>
                                <general-input v-model="password" :errors="errors['password']" type="password"/>
                            </div>
                        </div>
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('person.passwordConfirm')"/>
                                <general-input v-model="passwordConfirm" type="password"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-12">
                            <div class="field">
                                <label class="label" v-text="$t('loginPage.biologyDepartment')"/>
                                <div class="control">
                                    <label v-for="department in biologyDepartmentOptions" :for="`o_${department}`"
                                           class="checkbox">
                                        <input :id="`o_${department}`" v-model="biologyDepartments" :value="department"
                                               class="checkbox"
                                               type="checkbox">
                                        {{ $t(`person.biologyDepartmentOptions.${department}`) }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column">
                            <div class="button is-fullwidth"
                                 v-on:click="onRegister"
                                 v-text="$t('common.submit')"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import CountrySelect from '../components/selects/CountrySelect.vue';
import GeneralInput from '../components/GeneralInput.vue';
import biologyDepartmentOptions from '../utils/options/biologyDepartments';
import { openNotify } from '../utils';

export default {
    components: {
        CountrySelect,
        GeneralInput,
    },
    data() {
        return {
            biologyDepartmentOptions,
            errors: {},
            name: '',
            email: '',
            password: '',
            passwordConfirm: '',
            biologyDepartments: [],
        };
    },
    computed: {
        formData() {
            return {
                name: this.name,
                email: this.email,
                password: this.password,
                passwordConfirm: this.passwordConfirm,
                biologyDepartments: this.biologyDepartments,
            };
        },
    },
    methods: {
        onRegister() {
            this.axios.post('/users', this.formData)
                .then(() => {
                    this.$router.push('/login');
                    openNotify(this.$t('common.createSuccess'));
                })
                .catch(({ errors }) => {
                    this.errors = errors;
                });
        },
    },
};
</script>
<style lang="scss" scoped>
.container {
    max-width: $desktop;
    display: flex;
    align-items: center;
    justify-content: center;

    .box {
        width: $tablet;
        margin-top: 3rem;
    }

    label.checkbox {
        margin-right: 1.5rem;
    }
}
</style>
