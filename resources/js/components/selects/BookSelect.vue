<template>
    <t-select v-model="localValue"
              :clear-search-on-blur="() => true"
              :create-option="value => ({ title: value, bookTitleAbbreviation: '' })"
              :errors="errors"
              :filterable="false"
              :options="filteredBooks"
              :push-tag="true"
              clearable
              label="title"
              taggable
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredBook"
    >
        <template v-slot:option="{ option }">
            <template v-if="option.id">
                {{ option.title }}
            </template>
            <template v-else>
                {{ option.title }}
                <span class="help is-inline is-info" v-text="`(${$t('forms.draftCreateNote')})`"/>
            </template>
        </template>
    </t-select>
</template>
<script>
import { debounce } from 'lodash';
import Select from '../Select.vue';

export default {
    components: {
        tSelect: Select,
    },
    props: {
        value: {
            type: Object | String,
        },
        errors: {
            type: Array,
        },
    },
    data() {
        return {
            filteredBooks: [],
            localValue: this.value ?? null,
        };
    },
    watch: {
        value(v) {
            this.localValue = v;
        },
    },
    methods: {
        onUpdateValue(value) {
            this.$emit('input', value);
        },
        fetchFilteredBook: debounce(function ({ value, keyword }) {
            if (keyword === '') {
                return [];
            }

            this.axios.get('books', {
                params: { keyword },
            }).then(({ data }) => {
                this.filteredBooks = data;
            });
        }),
    },
};
</script>
