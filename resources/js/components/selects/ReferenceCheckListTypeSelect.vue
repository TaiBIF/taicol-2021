<template>
    <t-select v-model="localValue"
              :errors="errors"
              :options="options"
              :searchable="false"
              label="value"
              v-on:input="updateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="$t(`reference.checkListTypeOptions.${option.value}`)"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="$t(`reference.checkListTypeOptions.${option.value}`)"/>
        </template>
    </t-select>
</template>
<script>
import Select from '../Select.vue';
import CheckListTypes from '../../utils/options/referenceCheckListTypes';

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
    watch: {
        value(v) {
            if (v) {
                this.localValue = this.options.find((o) => o.value === v);
            }
        },
    },
    data() {
        const app = this;
        const options = CheckListTypes;
        return {
            localValue: CheckListTypes.find((o) => o.value === app.value),
            options,
        };
    },
    methods: {
        updateValue(type) {
            this.$emit('input', type?.value || null);
        },
    },
};
</script>
