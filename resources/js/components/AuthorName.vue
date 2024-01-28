<template>
    <span>{{ finalName }}</span>
</template>
<script>
import { animalAuthorNames, bacteriaAuthorNames, plantAuthorNames } from '../utils/preview/person';

export default {
    props: {
        authors: {
            type: Array,
            required: true,
        },
        exAuthors: {
            type: Array,
            required: true,
        },
        type: {
            type: String,
            required: true,
        },
        publishYear: {
            type: String,
        },
        originalTaxonName: {
            type: Object,
        },
        taxonName: {
            type: Object,
        },
    },
    computed: {
        finalName() {
            if (this.taxonName?.properties.authorsName) {
                return this.taxonName?.properties.authorsName;
            }
            switch (this.type) {
                case 'animal':
                    return animalAuthorNames(
                        this.authors,
                        this.exAuthors,
                        this.originalTaxonName,
                        this.publishYear,
                        this.taxonName,
                    );
                case 'plant':
                    return plantAuthorNames(
                        this.authors,
                        this.exAuthors,
                        this.originalTaxonName,
                    );

                case 'bacteria':
                    return bacteriaAuthorNames(
                        this.authors,
                        this.exAuthors,
                        this.originalTaxonName,
                        this.publishYear,
                        this.taxonName,
                    );
                case 'virus':
                    return '';
                default:
                    return '';
            }
        },
    },
};
</script>
