<template>
    <t-select :errors="errors"
              :options="options"
              :searchable="false"
              label="display"
              v-model="localValue"
              v-on:input="onUpdateValue"
              :disabled="disabled"
    >
        <template v-slot:selected-option="{ option }">
            {{ option.display['zh-tw'] }}
        </template>
        <template v-slot:option="{ option }">
            <div
                :class="{'has-text-weight-bold': option.isHighlight, 'has-text-info': option.isHighlight}">
                {{ option.display['zh-tw'] }}
            </div>
        </template>
    </t-select>
</template>
<script>
    import tSelect from './../Select';

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
            }
        },
        watch: {
            value(value) {
                this.localValue = value;
            }
        },
        data() {
            return {
                localValue: this.value,
            }
        },
        methods: {
            onUpdateValue(value) {
                this.$emit('input', value);
            },
        },
    }
</script>
