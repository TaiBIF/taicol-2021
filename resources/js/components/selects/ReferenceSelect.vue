<template>
    <t-select :errors="errors"
              :filterable="false"
              :options="filteredReferences"
              clearable
              label="title"
              ref="mySelect"
              v-model="localValue"
              v-on:input="onUpdateValue"
              v-on:typing="fetchFilteredReference"
    >
        <template v-slot:option="{ option }">
            {{ option.title }}
            <span class="help has-text-grey-light"
                  v-text="`${option.subtitle}`"/>
        </template>
        <template v-slot:no-options>
            {{ $t('forms.enterForOptions') }}{{ $t('forms.or') }}
            <a v-on:click="onAddReferenceFormLayer"
               v-text="$t('functions.createReference')"/>
        </template>
    </t-select>
</template>
<script>
    import { debounce } from 'lodash';
    import Select from '../Select';

    export default {
        components: {
            tSelect: Select,
        },
        props: {
            value: {
                type: Number | null,
                required: true,
            },
            errors: {
                type: Array,
            },
        },
        data() {
            return {
                filteredReferences: [],
                localValue: this.value,
                isLoading: false,
            }
        },
        watch: {
            value(v) {
                this.localValue = v;
            },
        },
        methods: {
            fetchFilteredReference: debounce(function ({ value, keyword }) {
                this.isLoading = true;
                if (!keyword) {
                    this.isLoading = false;
                    this.filteredReferences = [];
                    return;
                }

                this.axios.get('/references', {
                    params: { keyword },
                }).then(({ data: { data } }) => {
                    this.filteredReferences = data;
                }).catch(() => {
                    // TODO
                }).then(() => {
                    this.isLoading = false;
                });
            }),
            onUpdateValue(value) {
                this.$emit('input', value);
            },
            onAddReferenceFormLayer() {
                this.$store.commit('layer/ADD', {
                    template: () => import('./../layers/ReferenceLayer'),
                    events: {
                        onAfterSubmit: (reference) => {
                            this.localValue = reference;
                            this.onUpdateValue(this.localValue);
                        },
                    },
                });
            },
        },
    }
</script>
