<template>
    <t-select :clearable="true"
              :errors="errors"
              :options="filteredTaxonNames"
              label="formattedName"
              ref="taxonNameSelect"
              v-model="localValue"
              v-on:input="updateValue()"
              v-on:typing="fetchFilteredTaxonNames"
    >
        <template v-slot:selected-option="{ option }">
            <span v-html="option.formattedName.replace(/_(.*)_/, '<i>$1</i>')"/>
            <author-name v-bind="{
                authors: option.authors,
                exAuthors: option.exAuthors,
                type: option.nomenclature.group,
                originalTaxonName: option.originalTaxonName,
                publishYear: option.publishYear,
            }"></author-name>
        </template>
        <template v-slot:option="{ option }">
            <span v-html="option.formattedName.replace(/_(.*)_/, '<i>$1</i>')"/>
            <author-name v-bind="{
                authors: option.authors,
                exAuthors: option.exAuthors,
                type: option.nomenclature.group,
                originalTaxonName: option.originalTaxonName,
                publishYear: option.publishYear,
            }"></author-name>
        </template>
        <template v-slot:no-options>
            <span v-if="isLoading">載入中</span>
            <p v-else>
                {{ $t('forms.enterForOptions') }}{{ $t('forms.or')}} <a v-on:click="addTaxonNameFormLayer" v-text="$t('forms.taxonName.create')"/>
            </p>
        </template>
    </t-select>
</template>
<script>
    import { debounce } from 'lodash';
    import Select from '../Select';
    import AuthorName from '../AuthorName';

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
                filteredTaxonNames: [],
                localValue: this.value,
                rootSelectValue: null,
                isLoading: false,
            }
        },
        components: {
            AuthorName,
            tSelect: Select,
        },
        methods: {
            updateValue() {
                this.$emit('input', this.localValue);
            },
            addTaxonNameFormLayer() {
                this.$refs.taxonNameSelect.$refs.mySelect.onEscape();
                this.$store.state.layers.push(
                    {
                        template: () => import('./../layers/TaxonNameLayer'),
                        defaultText: this.localValue,
                        events: {
                            onAfterSubmit: (data) => {
                                this.localValue = data;
                            },
                        },
                    },
                )
            },
            fetchFilteredTaxonNames: debounce(function ({ value, keyword }) {
                this.isLoading = true;
                this.filteredTaxonNames = [];
                this.axios.get('taxon-names', {
                    params: { keyword },
                }).then(({ data: { data } }) => {
                    this.filteredTaxonNames = data;
                    this.isLoading = false;
                })
            }),
        },
    }
</script>
