<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="label"
              v-model="localValue"
    >
        <template v-slot:selected-option="{ option }">
            <span>{{ option.label.charAt(0).toUpperCase() + option.label.slice(1)}}</span>
        </template>
        <template v-slot:option="{ option }">
            <span>{{ option.label.charAt(0).toUpperCase() + option.label.slice(1)}}</span>
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';

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
            ];
            return {
                localValue: options.find(o => o === this.value),
                options,
            }
        },
    }
</script>
