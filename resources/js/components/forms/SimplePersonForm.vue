<template>
    <div class="form">
        <div class="columns">
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked" v-text="$t('forms.person.lastName')"/>
                    <general-input :errors="errors['lastName']" v-model="form.lastName" :disabled="isEdit" placeholder="拉丁字母填拼寫"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked">
                        {{ $t('forms.person.firstName') }}
                    </label>
                    <general-input :errors="errors['firstName']" v-model="form.firstName" :disabled="isEdit" placeholder="拉丁字母填拼寫"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.middleName')"/>
                    <general-input :errors="errors['middleName']" v-model="form.middleName" placeholder="拉丁字母填拼寫"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked" v-text="$t('forms.person.fullNameInNativeAlphabet')"/>
                    <general-input :errors="errors['originalFullName']"
                                   v-model="form.originalFullName" placeholder="完整全名包含[姓, 名 中間名]，如有中文名填入此欄"/>
                </div>
            </div>
            <div class="column is-6">
                <div class="field">
                    <label class="label"
                           :class="{'is-marked': form.biologyDepartments.includes('plantae')}"
                           v-text="`${$t('forms.person.nameAbbreviation')}`"
                    />
                    <general-input :errors="errors['abbreviationName']" v-model="form.abbreviationName"
                                   :disabled="isEdit"
                                   placeholder="植物學名命名者必填"
                    />
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
                    <general-input :errors="errors['yearOfBirth']" v-model="form.yearOfBirth" placeholder="YYYY"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.yearOfDeath')"/>
                    <general-input :errors="errors['yearOfDeath']" v-model="form.yearOfDeath" placeholder="YYYY"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label">
                        {{ $t('forms.person.yearOfPublication') }}
                        <b-tooltip
                            position="is-bottom">
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:content>
                                發表或採集年代，以 fl. 或 col. 接年代或年代範圍。<br/>
                                範例：fl. 1995-2016、fl. 2013-、col. 1790-1800<br/>
                                生卒年不詳時建議盡量填寫本欄，可供選擇人名時參考。<br/>
                            </template>
                        </b-tooltip>
                    </label>

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
            <div class="column is-6">
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
            <div class="column is-6">
                <div class="field">
                    <label class="label">
                        {{ $t('forms.person.biologicalGroup') }}
                        <b-tooltip
                            position="is-bottom" size="is-large" multilined>
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:content>
                                請填入大類群，如：藻類、苔蘚植物、蕨類、裸子植物、被子植物、子囊菌、擔子菌、昆蟲、蜘蛛、軟甲類、軟體動物、魚類、兩棲類、爬蟲類、鳥類、哺乳類、海洋無脊椎、陸生無脊椎等；或填入階層學名，如：鱗翅目、桑科等。也可填入多類群，以頓號「、」分隔。總字數10字以內。
                            </template>
                        </b-tooltip>
                    </label>
                    <general-input :errors="errors['biologicalGroup']" v-model="form.biologicalGroup"/>
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
        props: {
            presetData: {
                type: Object,
                required: false,
            },
            onAfterSubmit: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                errors: {},
                form: {
                    ...{
                        lastName: '',
                        firstName: '',
                        middleName: '',
                        abbreviationName: '',
                        originalFullName: '',
                        otherNames: '',
                        yearOfBirth: '',
                        yearOfDeath: '',
                        yearOfPublication: '',
                        nationality: null,
                        biologyDepartments: [],
                        biologicalGroup: '',
                    },
                    ...this.presetData,
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
            isEdit() {
                return !!this.form?.id;
            }
        },
        methods: {
            submit(isEdit = false) {
                this.axios({
                    method: isEdit ? 'PUT' : 'POST',
                    url: isEdit ? `/persons/${this.presetData.id}` : '/persons',
                    data: { ...this.formData },
                })
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
        components: {
            GeneralInput,
            CountrySelect,
        },
    }
</script>
