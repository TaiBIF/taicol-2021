<template>
    <t-select v-model="localValue"
              :errors="errors"
              :key-id="'key'"
              :options="options"
              :searchable="false"
              label="key"
              v-on:input="onUpdateValue"
    >
        <template v-slot:selected-option="{ option }">
            <span v-text="option.display[$i18n.locale()]"/>
        </template>
        <template v-slot:option="{ option }">
            <span v-text="option.display[$i18n.locale()]"/>
        </template>
    </t-select>
</template>
<script>
import Select from '../Select.vue';

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
    watch: {
        value(value) {
            this.localValue = this.options.find((o) => o.key === value) ?? null;
        },
    },
    data() {
        const options = [
            {
                key: 0,
                display: {
                    'zh-tw': '分類研究',
                    'en-us': 'Taxonomic Research',
                },
            },
            {
                key: 1,
                display: {
                    'zh-tw': '純名錄',
                    'en-us': 'Simple Checklist',
                },
            },
        ];

        return {
            localValue: options.find((o) => o.key === this.value) ?? null,
            options,
        };
    },
    methods: {
        onUpdateValue(sexObject) {
            this.$emit('input', sexObject);
        },
    },
};
</script>
