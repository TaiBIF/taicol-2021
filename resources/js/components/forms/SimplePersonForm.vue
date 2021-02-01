<template>
    <div class="form">
        <div class="columns">
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked" v-text="$t('forms.person.lastName')"/>
                    <general-input :errors="errors['lastName']" v-model="form.lastName"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked">
                        {{ $t('forms.person.firstName') }}
                    </label>
                    <general-input :errors="errors['firstName']" v-model="form.firstName"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.middleName')"/>
                    <general-input :errors="errors['middleName']" v-model="form.middleName"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.fullNameInNativeAlphabet')"/>
                    <general-input :errors="errors['fullNameInNativeAlphabet']"
                                   v-model="form.fullNameInNativeAlphabet"/>
                </div>
            </div>
            <div class="column is-6">
                <div class="field">
                    <label class="label" v-text="`${$t('forms.person.nameAbbreviation')}（植物學名填寫）`"/>
                    <general-input :errors="errors['authorAbbreviation']" v-model="form.abbreviationName"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.otherNames')"/>
                    <general-input :errors="errors['otherNames']" v-model="form.otherNames"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.yearOfBirth')"/>
                    <general-input :errors="errors['yearOfBirth']" v-model="form.yearOfBirth"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.yearOfDeath')"/>
                    <general-input :errors="errors['yearOfDeath']" v-model="form.yearOfDeath"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.yearOfPublication')"/>
                    <general-input :errors="errors['yearOfPublication']" v-model="form.yearOfPublication"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.nationality')"/>
                    <country-select v-model="form.nationality"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-12">
                <div class="field">
                    <label class="label is-marked" v-text="$t('forms.person.biologyDepartment')"/>
                    <div class="control">
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="viruses">
                            {{ $t('forms.person.biologyDepartmentOptions.viruses') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="bacteria">
                            {{ $t('forms.person.biologyDepartmentOptions.bacteria') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="archaea">
                            {{ $t('forms.person.biologyDepartmentOptions.archaea') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="protozoa">
                            {{ $t('forms.person.biologyDepartmentOptions.protozoa') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="chromista">
                            {{ $t('forms.person.biologyDepartmentOptions.chromista') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="fungi">
                            {{ $t('forms.person.biologyDepartmentOptions.fungi') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="plantae">
                            {{ $t('forms.person.biologyDepartmentOptions.plantae') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input type="checkbox" v-model="form.biologyDepartments" value="animalia">
                            {{ $t('forms.person.biologyDepartmentOptions.animalia') }}
                        </label>
                    </div>
                    <p class="is-danger" v-for="m in errors['biologyDepartments']">{{ m }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import CountrySelect from '../selects/CountrySelect';
    import GeneralInput from '../GeneralInput';
    import { openNotify } from './../../utils';

    export default {
        components: {
            GeneralInput,
            CountrySelect,
        },
        props: {
            onAfterSubmit: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                errors: {},
                form: {
                    lastName: '',
                    firstName: '',
                    middleName: '',
                    abbreviationName: '',
                    fullNameInNativeAlphabet: '',
                    otherNames: '',
                    yearOfBirth: '',
                    yearOfDeath: '',
                    yearOfPublication: '',
                    nationality: null,
                    biologyDepartments: [],
                },
            }
        },
        computed: {
            formData() {
                return {
                    ...this.form,
                    countryNumericCode: this.form.nationality?.numericCode,
                }
            },
        },
        methods: {
            submit() {
                this.axios.post('/persons', this.formData)
                    .then(({ data }) => {
                        this.onAfterSubmit(data);
                        openNotify(this.$t('forms.saveSuccess'));
                    })
                    .catch(({ errors, status }) => {
                        if (status === 409) {
                            openNotify(this.$t('forms.person.personExist'), 'is-danger');
                        } else {
                            this.errors = errors;
                        }
                    });
            },
        },
    }
</script>
