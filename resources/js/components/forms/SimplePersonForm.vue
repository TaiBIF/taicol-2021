<template>
    <div class="form">
        <div class="columns">
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked" v-text="$t('person.lastName')"/>
                    <general-input v-model="form.lastName" :disabled="isEdit" :errors="errors['lastName']"
                                   :placeholder="$t('person.fillInLatinAlphabet')"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label is-marked">
                        {{ $t('person.firstName') }}
                    </label>
                    <general-input v-model="form.firstName" :disabled="isEdit" :errors="errors['firstName']"
                                   :placeholder="$t('person.fillInLatinAlphabet')"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('person.middleName')"/>
                    <general-input v-model="form.middleName" :errors="errors['middleName']"
                                   :placeholder="$t('person.fillInLatinAlphabet')"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked" v-text="$t('person.originalFullName')"/>
                    <general-input v-model="form.originalFullName"
                                   :errors="errors['originalFullName']"
                                   :placeholder="$t('person.placeholderOriginalFullName')"/>
                </div>
            </div>
            <div class="column is-6">
                <div class="field">
                    <label :class="{'is-marked': form.biologyDepartments.includes('plantae')}"
                           class="label"
                           v-text="`${$t('person.abbreviationName')}`"
                    />
                    <general-input v-model="form.abbreviationName" :disabled="isEdit"
                                   :errors="errors['abbreviationName']"
                                   :placeholder="$t('person.icnNameAuthorNeeded')"
                    />
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label" v-text="$t('person.otherNames')"/>
                    <general-input v-model="form.otherNames" :errors="errors['otherNames']"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('person.yearOfBirth')"/>
                    <general-input v-model="form.yearOfBirth" :errors="errors['yearOfBirth']" placeholder="YYYY"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label" v-text="$t('person.yearOfDeath')"/>
                    <general-input v-model="form.yearOfDeath" :errors="errors['yearOfDeath']" placeholder="YYYY"/>
                </div>
            </div>
            <div class="column is-2">
                <div class="field">
                    <label class="label">
                        {{ $t('person.yearOfPublication') }}
                        <tooltip>
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:body>
                                <div class="w-[320px]">
                                    {{ $t('person.yearOfLifeNote') }}
                                </div>
                            </template>
                        </tooltip>
                    </label>

                    <general-input v-model="form.yearOfPublication" :errors="errors['yearOfPublication']"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label" v-text="$t('person.countryNumericCode')"/>
                    <country-select v-model="form.nationality"/>
                </div>
            </div>
        </div>
        <div class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked" v-text="$t('person.biologyDepartment')"/>
                    <div class="control">
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="viruses">
                            {{ $t('person.biologyDepartmentOptions.viruses') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="bacteria">
                            {{ $t('person.biologyDepartmentOptions.bacteria') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="archaea">
                            {{ $t('person.biologyDepartmentOptions.archaea') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="protozoa">
                            {{ $t('person.biologyDepartmentOptions.protozoa') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="chromista">
                            {{ $t('person.biologyDepartmentOptions.chromista') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="fungi">
                            {{ $t('person.biologyDepartmentOptions.fungi') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="plantae">
                            {{ $t('person.biologyDepartmentOptions.plantae') }}
                        </label>
                        &nbsp;&nbsp;
                        <label class="checkbox">
                            <input v-model="form.biologyDepartments" type="checkbox" value="animalia">
                            {{ $t('person.biologyDepartmentOptions.animalia') }}
                        </label>
                    </div>
                    <p v-for="m in errors['biologyDepartments']" class="is-danger">{{ $t(`validation.${m}`) }}</p>
                </div>
            </div>
            <div class="column is-6">
                <div class="field">
                    <label class="label">
                        {{ $t('person.biologicalGroup') }}
                        <tooltip>
                            <i class="fas fa-info-circle"></i>
                            <template v-slot:body>
                                <div class="w-[365px]">
                                    {{ $t('person.biologicalGroupNote') }}
                                </div>
                            </template>
                        </tooltip>
                    </label>
                    <general-input v-model="form.biologicalGroup" :errors="errors['biologicalGroup']"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import {
    computed, defineComponent, inject, PropType, ref,
} from '@vue/composition-api';

import CountrySelect from '../selects/CountrySelect.vue';
import GeneralInput from '../GeneralInput.vue';
import { openNotify } from '../../utils';
import Tooltip from '../Tooltip.vue';

import { PersonDetail } from '../../types';
import { personDetailResource } from '../../utils/models/persons';

export default defineComponent({
    name: 'SimplePersonForm',
    props: {
        presetData: {
            type: Object,
            required: false,
        },
        onAfterSubmit: {
            type: Function as PropType<(data) => void>,
            required: true,
        },
    },
    setup(props, context) {
        const axios: any = inject('axios');
        const errors = ref({});
        const app: any = context.root;

        const form = ref<PersonDetail>(personDetailResource(props.presetData));

        const isEdit = computed(() => !!form.value?.id);
        const formData = computed(() => ({
            ...form.value,
            countryNumericCode: form.value.nationality?.numericCode,
        }));

        const onSubmit = () => {
            axios({
                method: isEdit.value ? 'PUT' : 'POST',
                url: isEdit.value ? `/persons/${formData.value.id}` : '/persons',
                data: formData.value,
            })
                .then(({ data }) => {
                    props.onAfterSubmit(data);
                    openNotify(app.$t('common.saveSuccess'));
                })
                .catch(({ errors: errorMessages, status }) => {
                    if (status === 409) {
                        openNotify('人名已存在', 'is-danger');
                    } else {
                        errors.value = errorMessages;
                    }
                });
        };

        return {
            isEdit,
            errors,
            form,
            formData,
            onSubmit,
        };
    },
    components: {
        Tooltip,
        GeneralInput,
        CountrySelect,
    },
});
</script>
