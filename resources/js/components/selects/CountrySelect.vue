<template>
    <t-select :errors="errors"
              :filter-by="filterBy"
              :options="filteredCountries"
              label="display"
              v-model="localValue"
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredCountry"
    >
        <template v-slot:selected-option="{ option }">
            {{ option.display['zh-tw'] }}
        </template>
        <template v-slot:option="{ option }">
            {{ option.display['zh-tw'] }}
        </template>
        <template v-slot:no-options="{ option }">
            {{ option }}
            {{ $t('forms.enterForOptions') }}
        </template>
    </t-select>
</template>
<script>
    import { debounce } from 'lodash';
    import Select from '../Select';

    export default {
        props: {
            value: {
                type: Object,
            },
            errors: {
                type: Array,
            },
        },
        data() {
            return {
                filteredCountries: [],
                localValue: this.value,
            }
        },
        components: {
            tSelect: Select,
        },
        methods: {
            onUpdateValue(country) {
                this.$emit('input', country);
            },
            filterBy(option, label, search) {
                return label['zh-tw'];
            },
            fetchFilteredCountry: debounce(function ({ value, keyword }) {
                this.axios.get('countries', {
                    params: { keyword },
                }).then(({ data }) => {
                    this.filteredCountries = data;
                })
            }),
        },
    }
</script>
