<template>
    <t-select :clearable="false"
              :errors="errors"
              :options="options"
              :searchable="false"
              :disabled="disabled"
              label="name"
              v-model="localValue"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            
            {{ $i18n.locale() === 'zh-tw' ?
                option.display['zh-tw'] : option.display['en-us']  }}
        </template>
        <template v-slot:option="{ option }">
            {{ $i18n.locale() === 'zh-tw' ?
                option.display['zh-tw'] : option.display['en-us']  }}
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';
    import methods from './map/filterMethod';

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
            disabled: {
                type: Boolean,
                default: false,
            }
        },
        watch: {
        value(value) {
            this.localValue = value;
            },
        },
        data() {
            const options = methods;

            return {
                localValue: this.value,
                options,
            }
        },
        methods: {
            onUpdateValue(object) {
                this.$emit('input', object);
            },
        },
    }
</script>
