<template>
    <p>
        <undetermined v-if="status === 'undetermined'"
                      :indications="indications"
                      :per-usages="perUsages"
                      :taxon-name="taxonName"
        />
        <misapplied v-else-if="status === 'misapplied'"
                    :indications="indications"
                    :is-simple="isSimple"
                    :per-usages="perUsages"
                    :taxon-name="taxonName"
        />
        <accepted-not-accepted v-else
                               :indications="indications"
                               :is-simple="isSimple"
                               :per-usages="perUsages"
                               :status="status"
                               :taxon-name="taxonName"
        />
        <template v-if="!isSimple">
            <template v-if="taxonName.nomenclature.group === 'bacteria'">
                {{ comboTypeStrain(typeSpecimens) }}
            </template>
            <template v-else>
                <span v-if="typeSpecimens.length" v-text="Scombo(typeSpecimens) + '.'"></span>
                <template v-if="typeName">
                    Type:
                    <taxon-name-label :taxon-name="typeName"/><!--
                    -->&nbsp;<!--
                    --><author-name v-bind="{
                        authors: typeName.authors,
                        exAuthors: typeName.exAuthors,
                        type: typeName.nomenclature.group,
                        originalTaxonName: typeName.originalTaxonName,
                        publishYear: typeName.publishYear,
                }"/>
                </template>
            </template>
        </template>
    </p>
</template>
<script>
import { combo as Scombo, comboTypeStrain } from '../utils/preview/typeSpecimen';
import AuthorName from './AuthorName.vue';
import TaxonNameLabel from './views/TaxonNameLabel.vue';
import AcceptedNotAccepted from './views/usage/AcceptedNotAccepted.vue';
import Undetermined from './views/usage/Undetermined.vue';
import Misapplied from './views/usage/Misapplied.vue';

export default {
    components: {
        Misapplied, Undetermined, AcceptedNotAccepted, TaxonNameLabel, AuthorName,
    },
    props: {
        status: {
            type: String,
            required: true,
        },
        taxonName: {
            type: Object,
            required: true,
        },
        indications: {
            type: Array,
            default() {
                return [];
            },
        },
        typeSpecimens: {
            type: Array,
            required: true,
        },
        typeName: {
            type: Object,
        },
        perUsages: {
            type: Array,
            required: true,
        },
        isSimple: {
            type: Boolean,
            required: false,
        },
    },
    methods: {
        Scombo,
        comboTypeStrain,
    },
};
</script>
<style lang="scss" scoped>
.is-orange {
    color: $orange;
}
</style>
