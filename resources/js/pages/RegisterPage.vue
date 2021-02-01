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
                                        <general-input :errors="errors['lastName']"
                                                       :placeholder="$t('forms.person.lastName')"
                                                       v-model="lastName"/>
                                    </div>
                                    <div class="field">
                                        <general-input :errors="errors['middleName']"
                                                       :placeholder="$t('forms.person.middleName')"
                                                       v-model="middleName"/>
                                    </div>
                                    <div class="field">
                                        <general-input :errors="errors['firstName']"
                                                       :placeholder="$t('forms.person.firstName')"
                                                       v-model="firstName"/>
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
                        <div class="column is-6">
                            <div class="field">
                                <label class="label" v-text="$t('forms.person.yearOfBirth')"/>
                                <general-input :errors="errors['yearOfBirth']" v-model="yearOfBirth"/>
                            </div>
                        </div>
                        <div class="column is-6">
                            <div class="field">
                                <label class="label" v-text="$t('forms.person.nationality')"/>
                                <country-select v-model="nationality"/>
                            </div>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-12">
                            <div class="field">
                                <label class="label is-marked" v-text="$t('forms.person.biologyDepartment')"/>
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
                        <div class="column is-12">
                            <label class="label" for="addToPerson">
                                <input class="checkbox" id="addToPerson" type="checkbox" v-model="isExpert">
                                {{ $t('forms.person.isExpert') }}
                            </label>
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
                lastName: '',
                firstName: '',
                middleName: '',
                yearOfBirth: '',
                nationality: null,
                email: '',
                password: '',
                passwordConfirm: '',
                biologyDepartments: [],
                isExpert: false,
            }
        },
        computed: {
            formData() {
                return {
                    lastName: this.lastName,
                    firstName: this.firstName,
                    middleName: this.middleName,
                    yearOfBirth: this.yearOfBirth,
                    nationality: this.nationality?.id,
                    email: this.email,
                    password: this.password,
                    biologyDepartments: [],
                    isExpert: this.isExpert,
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
