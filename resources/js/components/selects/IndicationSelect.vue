<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              :taggable="true"
              label="name"
              :multiple="true"
              :filterable="false"
              v-model="localValue"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="`${option.abbreviation}`"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="`${option.abbreviation}(${option.name}) = ${option.definition}`"/>
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';
    import indications from './indications';

    export default {
        components: {
            tSelect: Select,
        },
        props: {
            status: {
                type: String,
                default: '',
            },
            value: {
                type: Array,
            },
            type: {
                type: String,
                required: true,
            },
            errors: {
                type: Array,
            },
        },
        watch: {
            status(v) {
                this.localValue = [];
                this.options = indications.filter(i => i.status === v && i.group.includes(this.type));
            },
        },
        data() {
            const options = indications.filter(i => i.status === this.status && i.group.includes(this.type));

            return {
                localValue: this.value,
                options,
            }
        },
        methods: {
            onUpdateValue(indicationObject) {
                this.$emit('input', indicationObject);
            },
        },
    }
</script>
