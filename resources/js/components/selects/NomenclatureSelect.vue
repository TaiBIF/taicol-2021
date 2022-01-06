<template>
    <t-select v-model="localValue"
              :errors="errors"
              :options="options"
              :searchable="false"
              :selectable="v => !v.isDisabled"
              label="name"
              v-on:input="onUpdateValue"
              :disabled="disabled"
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

    export default {
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
            }
        },
        data() {
            return {
                options: [],
                localValue: this.value || null,
                rootSelectValue: null,
            }
        },
        components: {
            tSelect: Select,
        },
        mounted() {
            this.options = this.$store.state.nomenclature.items;
        },
        methods: {
            onUpdateValue(value) {
                this.$emit('input', value || null);
                if (this.localValue) {
                    this.$emit('update-ranks', this.localValue?.ranks);
                }
            },
            updateById(value) {
                this.value = value;
            },
        },
    }
</script>
