<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              :disabled="disabled"
              label="name"
              v-model="localValue"
              v-on:input="onUpdateValue"
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
    import { watch } from 'vue';
import Select from '../Select';
    import municipality from './map/municipality';

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
            county: {
                type: Object
            },
            disabled: {
                type: Boolean,
                default: false,
            }
        },
        watch: {
            county(object){
                this.options = municipality.filter(item => item.parent_id === object.id);
            }
        },
        data() {
            const options = [];

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
