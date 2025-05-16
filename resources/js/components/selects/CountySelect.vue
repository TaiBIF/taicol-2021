<template>
    <t-select :clearable="false"
              :errors="errors"
              :options="options"
              :searchable="false"
              :disabled="disabled"
              label="name"
              v-model="localValue"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            {{ option.display['zh-tw'] }}
        </template>
        <template v-slot:option="{ option }">
            {{ option.display['zh-tw'] }}
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';
    import county from './map/county';

    export default {
        components: {
            tSelect: Select,
        },
        props: {
            value: {
                type: Object,
            },
            errors: {
                type: Array,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            const options = county;

            return {
                localValue: this.value,
                options,
            }
        },
        methods: {
            onUpdateValue(object) {
                this.$emit('input', object);
            },
        },
    }
</script>
