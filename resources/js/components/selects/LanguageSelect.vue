<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="id"
              v-model="localValue"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="$t(`forms.reference.languages.${option.id}`)"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="$t(`forms.reference.languages.${option.id}`)"/>
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';
    import options from '../../utils/options/languages';

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
        },
        data() {
            return {
                localValue: this.value,
                options,
            }
        },
        methods: {
            onUpdateValue(value) {
                this.$emit('input', value);
            }
        }
    }
</script>
