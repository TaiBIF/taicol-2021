<template>
    <t-select :disabled="disabled"
              :errors="errors"
              :loading="isLoading"
              :options="filteredPersons"
              clearable
              label="fullName"
              multiple
              ref="mySelect"
              v-model="localValue"
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredPersons"
    >
        <template v-slot:option="{ option }">
            <span v-if="group" v-text="`${option.lastName}, ${option.firstName}`"/>
            <span v-else v-text="`${option.fullName}`"/>
            <span v-if="option.abbreviationName">({{ option.abbreviationName }})</span>

            <span class="help has-text-grey-light">
                {{ option.originalFullName }} {{ option.yearLife || yearOfPublication }}
                {{ option.biologyDepartments.map(d => $t(`forms.person.biologyDepartmentOptions.${d}`)).join(', ') }}:{{ option.biologicalGroup.replace('、', ',') }}
            </span>
        </template>
        <template v-slot:selected-option="{ option }">
            <span v-if="group" v-text="`${renderFormatName(option, group)} (${renderFormatFullName(option)})`"/>
            <span v-else v-text="`${option.fullName}`"/>
        </template>
        <template v-slot:no-options>
            {{ $t('forms.enterForOptions') }}{{ $t('forms.or') }} <a v-on:click="onAddPersonFormLayer" v-text="$t('forms.person.create')" />
        </template>
    </t-select>
</template>
<script>
    import { debounce } from 'lodash';
    import Select from '../Select';
    import { factory as personFactory, fullName } from '../../utils/preview/person';

    export default {
        props: {
            value: {
                type: Array,
                required: true,
            },
            errors: {
                type: Array,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
            // Nomenclauture 類別「動物」或「植物」
            group: {
                type: String,
            },
        },
        data() {
            return {
                filteredPersons: [],
                localValue: this.value,
                isLoading: false,
            }
        },
        components: {
            tSelect: Select,
        },
        watch: {
            disabled(value) {
                if (value) {
                    this.localValue = [];
                }
            },
        },
        methods: {
            renderFormatName(person, group) {
                return personFactory(group)([person]);
            },
            renderFormatFullName(person) {
                return fullName(person);
            },
            onUpdateValue(value) {
                this.filteredPersons = [];
                this.$emit('input', value);
            },
            onAfterCreate(data) {
                this.localValue.push(data);
                this.onUpdateValue(this.localValue);
            },
            onAddPersonFormLayer() {
                this.$refs.mySelect.$refs.mySelect.onEscape();
                this.$store.commit('layer/ADD', {
                    template: () => import('./../layers/PersonLayer'),
                    defaultText: this.localValue,
                    events: {
                        onAfterSubmit: this.onAfterCreate,
                    },
                })
            },
            fetchFilteredPersons: debounce(function ({ value, keyword }) {
                this.isLoading = true;

                if (!keyword) {
                    this.isLoading = false;
                    this.filteredPersons = [];
                    return;
                }

                this.axios.get('persons', {
                    params: { keyword },
                }).then(({ data }) => {
                    this.isLoading = false;
                    this.filteredPersons = data;
                })
            }),
        },
    }
</script>
