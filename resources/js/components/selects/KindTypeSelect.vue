<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="name"
              v-model="localValue"
              v-on:input="onUpdateValue"
    />
</template>
<script>
    import Select from '../Select';

    export default {
        components: {
            tSelect: Select,
        },
        props: {
            value: {
                type: Number,
            },
            errors: {
                type: Array,
            },
        },
        data() {
            const options = [
                {
                    id: 1,
                    name: '標本',
                },
                {
                    id: 2,
                    name: '手繪圖',
                },
                {
                    id: 3,
                    name: '生物照片',
                },
                {
                    id: 4,
                    name: 'DNA',
                },
            ];

            return {
                localValue: options.find(o => o.id === this.value) ?? null,
                options,
            }
        },
        methods: {
            onUpdateValue(type) {
                this.$emit('input', type.id);
            },
        },
    }
</script>
