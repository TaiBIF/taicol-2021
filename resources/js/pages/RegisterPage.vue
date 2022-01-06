<template>
    <div class="container">
        <div class="box">
            <p class="title is-3" v-text="$t('forms.person.createAccount')"/>
            <div class="form">
                <div class="form">
                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('forms.person.name')"/>
                                <div class="field-body">
                                    <div class="field">
                                        <general-input :errors="errors['name']"
                                                       :placeholder="$t('forms.person.name')"
                                                       v-model="name"/>
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
                                               v-model="email"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('forms.person.password')"/>
                                <general-input :errors="errors['password']" type="password" v-model="password"/>
                            </div>
                        </div>
                        <div class="column is-6">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('forms.person.passwordConfirm')"/>
                                <general-input type="password" v-model="passwordConfirm"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-12">
                            <div class="field">
                                <label class="label" v-text="$t('forms.person.biologyDepartment')"/>
                                <div class="control">
                                    <label :for="`o_${department}`" class="checkbox"
                                           v-for="department in biologyDepartmentOptions">
                                        <input :id="`o_${department}`" :value="department" class="checkbox"
                                               type="checkbox"
                                               v-model="biologyDepartments">
                                        {{ $t(`forms.person.biologyDepartmentOptions.${department}`) }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column">
                            <div class="button is-fullwidth"
                                 v-on:click="onRegister"
                                 v-text="$t('forms.actions.submit')"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import CountrySelect from '../components/selects/CountrySelect';
    import GeneralInput from '../components/GeneralInput';
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
            }
        },
        computed: {
            formData() {
                return {
                    name: this.name,
                    email: this.email,
                    password: this.password,
                    passwordConfirm: this.passwordConfirm,
                    biologyDepartments: this.biologyDepartments,
                }
            },
        },
        methods: {
            onRegister() {
                this.axios.post('/users', this.formData)
                    .then(() => {
                        this.$router.push('/login');
                        openNotify(this.$t('forms.createSuccess'));
                    })
                    .catch(({ errors }) => {
                        this.errors = errors;
                    });
            },
        },
    }
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
