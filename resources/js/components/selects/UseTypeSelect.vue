<template>
    <t-select v-model="localValue"
              :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="label"
    >
        <template v-slot:selected-option="{ option }">
            <span>{{ option.label.charAt(0).toUpperCase() + option.label.slice(1) }}</span>
        </template>
        <template v-slot:option="{ option }">
            <span>{{ option.label.charAt(0).toUpperCase() + option.label.slice(1) }}</span>
        </template>
    </t-select>
</template>
<script>
import Select from '../Select.vue';

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
        localValue(value) {
            this.$emit('input', value);
        },
    },
    data() {
        const options = [
            'holotype',
            'syntype',
            'paratype',
            'lectotype',
            'paralectotype',
            'neotype',
            'topotype',
            'allotype',
            'type',
        ];
        return {
            localValue: options.find((o) => o === this.value),
            options,
        };
    },
};
</script>
