<template>
    <t-select :errors="errors"
              :options="options"
              :searchable="false"
              label="name"
              v-model="localValue"
              v-on:input="onUpdateValue"
    />
</template>
<script>
    import Select from '../Select';
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
                localValue: options.find(o => o.id === this.value) ?? null,
                options,
            }
        },
        methods: {
            onUpdateValue(type) {
                this.$emit('input', type.id);
            },
        },
        components: {
            tSelect: Select,
        },
    }
</script>
