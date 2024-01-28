<template>
    <t-select v-model="localValue"
              :errors="errors"
              :filter-by="filterBy"
              :options="filteredCountries"
              label="display"
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredCountry"
    >
        <template v-slot:selected-option="{ option }">
            {{ option.display[$i18n.locale()] }}
        </template>
        <template v-slot:option="{ option }">
            {{ option.display[$i18n.locale()] }}
        </template>
        <template v-slot:no-options="{ option }">
            {{ option }}
            {{ $t('common.enterForOptions') }}
        </template>
    </t-select>
</template>
<script>
import { debounce } from 'lodash';
import Select from '../Select.vue';

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
        };
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
            });
        }),
    },
};
</script>
