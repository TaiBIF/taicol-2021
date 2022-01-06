<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              :taggable="true"
              label="name"
              :multiple="status === 'accepted' || status === 'not-accepted'"
              :filterable="false"
              v-model="localValue"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="`${option.abbreviation}`"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="`${option.abbreviation}`"/>
            <br/>
            <span class="help is-small has-text-grey-light" v-text="`${option.name} ${option.definition}`"></span>
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
                this.$emit('input', []);
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
                if (indicationObject === null) {
                    this.$emit('input', []);
                } else if (Array.isArray(indicationObject)) {
                    this.$emit('input', indicationObject);
                } else {
                    this.$emit('input', [indicationObject]);
                }
            },
        },
    }
</script>
