<template>
    <t-select v-model="localValue"
              :disabled="disabled"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="display"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            {{ option.display[$i18n.locale()] }}
        </template>
        <template v-slot:option="{ option }">
            <div
                :class="{'has-text-weight-bold': option.isHighlight, 'has-text-info': option.isHighlight}">
                {{ option.display[$i18n.locale()] }}
            </div>
        </template>
    </t-select>
</template>
<script>
import tSelect from '../Select.vue';

export default {
    components: {
        tSelect,
    },
    props: {
        options: {
            type: Array,
            required: true,
        },
        value: {
            type: Object | null,
            required: true,
        },
        errors: {
            type: Array,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    watch: {
        value(value) {
            this.localValue = value;
        },
    },
    data() {
        return {
            localValue: this.value,
        };
    },
    methods: {
        onUpdateValue(value) {
            this.$emit('input', value);
        },
    },
};
</script>
