<template>
    <t-select v-model="localValue"
              :clearable="true"
              :errors="errors"
              :keyId="isUseKeyId ? 'id' : ''"
              :options="options"
              :searchable="false"
              label="id"
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
        props: {
            value: {
                type: [Object, String],
            },
            errors: {
                type: Array,
            },
            isUseKeyId: {
                type: Boolean,
                default: () => false,
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
            },
        },
        components: {
            tSelect: Select,
        },
    }
</script>
