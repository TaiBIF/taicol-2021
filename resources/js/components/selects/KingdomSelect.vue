<template>
    <t-select v-model="localValue"
              :disabled="disabled"
              :errors="errors"
              :options="options"
              :searchable="false"
              :selectable="v => !v.isDisabled"
              label="name"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            {{ option.name }}
        </template>
        <template v-slot:option="{ option }">
            {{ option.name }}
        </template>
    </t-select>
</template>
<script>
import Select from '../Select';

export default {
    props: {
        options: {
      type: Array,
      required: true
    },

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
        return {
            localValue: this.value || null,
        };
    },
    components: {
        tSelect: Select,
    },
    mounted() {
        // this.options = this.$store.state.nomenclature.items;
    },
    watch: {
        value(newVal) {
            this.localValue = newVal || null;
        },
    },
    methods: {
        onUpdateValue(value) {
            this.$emit('input', value || null);
            if (this.localValue) {
                this.$emit('update-kingdom', this.localValue?.id);
            }
        },
        updateById(value) {
            this.value = value;
        },
    },
};
</script>
