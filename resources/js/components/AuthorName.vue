<template>
    <span>{{ finalName }}</span>
</template>
<script>
    import { animalAuthorNames, plantAuthorNames } from '../utils/preview/person';

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
        },
        computed: {
            finalName() {
                if (this.type === 'animal') {
                    return animalAuthorNames(
                        this.authors,
                        this.exAuthors,
                        this.originalTaxonName,
                        this.publishYear,
                    );
                } else if (this.type === 'plant') {
                    return plantAuthorNames(
                        this.authors,
                        this.exAuthors,
                        this.originalTaxonName,
                    );
                }
            },
        },
    }
</script>
