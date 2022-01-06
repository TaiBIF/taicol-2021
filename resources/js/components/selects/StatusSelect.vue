<template>
    <t-select :errors="errors"
              :options="options"
              :searchable="false"
              label="key"
              :key-id="'key'"
              v-model="localValue"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="option.display[$i18n.locale()]"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="option.display[$i18n.locale()]"/>
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';
    import Status from './status';

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
                this.localValue = this.options.find(o => o.key === value) ?? null;
            }
        },
        data() {
            const options = Status;

            return {
                localValue: options.find(o => o.key === this.value) ?? null,
                options,
            }
        },
        methods: {
            onUpdateValue(sexObject) {
                this.$emit('input', sexObject);
            },
        },
    }
</script>
