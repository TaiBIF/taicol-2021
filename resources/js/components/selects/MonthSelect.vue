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
            {{ option ? option.name : '' }}
        </template>
        <template v-slot:option="{ option }">
            {{ option ? option.name : '' }}
        </template>
    </t-select>
</template>
<script>
    import Select from '../Select';

    export default {
        components: {
            tSelect: Select,
        },
        props: {
            value: {
                type: String,
            },
            errors: {
                type: Array,
            },
        },
        data() {
            const options = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];

            return {
                localValue: this.value,
                options,
            }
        },
        methods: {
            onUpdateValue(v) {
                this.$emit('input', v);
            },
        },
    }
</script>
