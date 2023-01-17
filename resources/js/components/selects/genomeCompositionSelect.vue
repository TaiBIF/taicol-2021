<template>
    <t-select v-model="localValue"
              :errors="errors"
              :key-id="'key'"
              :options="options"
              :searchable="false"
              label="key"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="option.key"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="option.key"/>
        </template>
    </t-select>
</template>
<script>
import Select from '../Select.vue';
import genomeComposition from './genomeComposition';

export default {
    components: {
        tSelect: Select,
    },
    props: {
        value: {
            type: String,
        },
        errors: {
            type: Array,
        },
    },
    watch: {
        value(value) {
            this.localValue = this.options.find((o) => o.key === value) ?? null;
        },
    },
    data() {
        const options = genomeComposition;

        return {
            localValue: options.find((o) => o.key === this.value) ?? null,
            options,
        };
    },
    methods: {
        onUpdateValue(value) {
            console.log(value);
            this.$emit('input', value);
        },
    },
};
</script>
