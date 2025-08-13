<template>
    <t-select ref="taxonNameSelect"
              v-model="localValue"
              :clearable="true"
              :disabled="disabled"
              :errors="errors"
              :options="filteredTaxonNames"
              label="name"
              v-on:input="updateValue()"
              v-on:typing="fetchFilteredTaxonNames"
    >
        <template v-slot:selected-option="{ option }">
            <taxon-name-label :taxon-name="option" class="is-inline-block"/><!--
            -->&nbsp;<!--
            --><author-name v-bind="{
                authors: option.authors,
                exAuthors: option.exAuthors,
                type: option.nomenclature.group,
                originalTaxonName: option.originalTaxonName,
                publishYear: option.publishYear,
                taxonName: option,
            }"/>
        </template>
        <template v-slot:option="{ option }">
            <taxon-name-label :taxon-name="option" class="is-inline-block"/><!--
            -->&nbsp;<!--
            --><author-name v-bind="{
                authors: option.authors,
                exAuthors: option.exAuthors,
                type: option.nomenclature.group,
                originalTaxonName: option.originalTaxonName,
                publishYear: option.publishYear,
                taxonName: option,
            }"/>
            <br/>
            <span class="help has-text-grey-light">
                {{ option.root ? option.root.name : '' }} [{{ option.rank.display['zh-tw'] }}] {{
                    showReference(option)
                }}
            </span>
        </template>
        <template v-slot:no-options>
            <span v-if="isLoading">{{ $t('common.loading') }}</span>
            <p v-else>
                {{ $t('common.enterForOptions') }} {{ $t('common.or') }} <a v-on:click="addTaxonNameFormLayer"
                                                                            v-text="$t('taxonName.create')"/>
            </p>
        </template>
    </t-select>
</template>
<script>
import { debounce } from 'lodash';
import Select from '../Select.vue';
import AuthorName from '../AuthorName.vue';
import TaxonNameLabel from '../views/TaxonNameLabel.vue';
import { openNotify } from '../../utils';


export default {
    props: {
        value: {
            type: Object,
        },
        errors: {
            type: Array,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            filteredTaxonNames: [],
            localValue: this.value,
            rootSelectValue: null,
            isLoading: false,
        };
    },
    components: {
        TaxonNameLabel,
        AuthorName,
        tSelect: Select,
    },
    methods: {
        showReference(o) {
            return o.reference?.subtitle ?? o.properties.referenceName ?? '';
        },
        updateValue() {
            this.$emit('input', this.localValue);
        },
        addTaxonNameFormLayer() {
            this.$refs.taxonNameSelect.$refs.mySelect.onEscape();
            this.$store.commit('layer/TAXON_NAME', {
                onAfterSubmit: (data) => {
                    console.log(data);
                    if (data.nomenclature.id==2 && data.rank.id==30){
                        this.localValue = data;
                        this.$emit('input', this.localValue);
                    } else {
                        openNotify('新增學名非正確法規及階層', 'is-danger');
                    }
                },
            });
        },
        fetchFilteredTaxonNames: debounce(function ({ value, keyword }) {
            this.filteredTaxonNames = [];

            if (keyword.length > 1){
                this.isLoading = true;
                this.axios.get('taxon-names', {
                    params: { keyword, strict: true, only_plant_genus: true },
                }).then(({ data: { data } }) => {
                    this.filteredTaxonNames = data;
                    this.isLoading = false;
                });
            }
        }),
    },
};
</script>
