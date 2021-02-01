<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
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
    import Select from '../Select';
    import sexs from './sexs';

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
            const options = sexs;

            return {
                localValue: this.value,
                options,
            }
        },
        methods: {
            onUpdateValue(sexObject) {
                this.$emit('input', sexObject);
            },
        },
    }
</script>
