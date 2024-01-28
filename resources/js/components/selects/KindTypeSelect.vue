<template>
    <t-select v-model="localValue"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="key"
              v-on:input="onUpdateValue">
        <template v-slot:selected-option="{ option }">
            <span v-text="option.display[$i18n.locale()]"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="option.display[$i18n.locale()]"/>
        </template>
    </t-select>
</template>
<script>
import Select from '../Select.vue';
import typeSpecimenKind from '../../utils/options/typeSpecimenKind';

export default {
    props: {
        value: {
            type: Number,
        },
        errors: {
            type: Array,
        },
    },
    data() {
        const options = typeSpecimenKind;

        return {
            localValue: options.find((o) => o.id === this.value) ?? null,
            options,
        };
    },
    methods: {
        onUpdateValue(type) {
            this.$emit('input', type.id);
        },
    },
    components: {
        tSelect: Select,
    },
};
</script>
