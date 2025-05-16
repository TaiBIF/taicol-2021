<template>
    <t-select ref="mySelect"
              v-model="localValue"
              :errors="errors"
              :filterable="false"
              :options="filteredTaxa"
              :disabled="disabled"
              multiple
              clearable
              label="text"
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredTaxa">
        <template v-slot:option="{ option }">
            <span v-html="option.text"></span>
        </template>
        <template v-slot:selected-option="{ option }">
            <span v-html="option.text"></span>
        </template>
        <template v-slot:no-options>
            <div>
                {{ $t('common.enterForOptions') }}
            </div>
        </template>
    </t-select>
</template>
<script>
import { debounce } from 'lodash';
import Select from '../Select.vue';

export default {
    components: {
        tSelect: Select,
    },
    props: {
        value: {
            type: Array,
        },
        errors: {
            type: Array,
        },
        disabled: {
            type: Boolean,
            default: false,
        }
    },
    data() {
        return {
            filteredTaxa: [],
            localValue: this.value,
            isLoading: false,
        };
    },
    watch: {
        // value(v) {
        //     this.localValue = v;
        //     console.log(this.localValue);
        // },
    },
    methods: {
        fetchFilteredTaxa: debounce(function ({ keyword }) {
            this.isLoading = true;
            if (!keyword || keyword.length <= 1) {
                this.isLoading = false;
                return;
            }

            this.axios.get('/higher-taxa', {
                params: { keyword },
            }).then(({ data: data }) => {

                this.filteredTaxa = data.map((r) => ({
                    taxonId: r.id,
                    text: r.text,
                }));

            }).catch(() => {
                // TODO
            }).then(() => {
                this.isLoading = false;
            });
        }),
        onUpdateValue(value) {
            this.$emit('input', value);
        },
    },
};
</script>
