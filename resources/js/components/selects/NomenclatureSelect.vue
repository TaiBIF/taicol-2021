<template>
    <t-select :errors="errors"
              :loading="isLoading"
              :options="options"
              :searchable="false"
              :selectable="v => !v.isDisabled"
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

    export default {
        props: {
            value: {
                type: Object,
            },
            errors: {
                type: Array,
            },
        },
        data() {
            return {
                isLoading: true,
                options: [],
                localValue: this.value || null,
                rootSelectValue: null,
            }
        },
        components: {
            tSelect: Select,
        },
        watch: {
            options(data) {
                this.$emit('after-load-options', data);
            },
        },
        mounted() {
            this.axios
                .get('/nomenclatures')
                .then(({ data }) => {
                    this.options = data;
                    this.isLoading = false;
                });
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
