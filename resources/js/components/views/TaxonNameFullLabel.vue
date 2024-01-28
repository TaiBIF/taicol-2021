<script>
import AuthorName from '../AuthorName.vue';
import TaxonNameLabel from './TaxonNameLabel.vue';

const interleave = (arr, thing) => [].concat(...arr.map((n) => [n, thing])).slice(0, -1);

export default {
    name: 'taxon-name-full-label',
    props: {
        taxonName: {
            type: Object,
            required: true,
        },
        withColor: {
            type: Boolean,
            default: () => false,
        },
    },
    render(createElement) {
        if (this.taxonName.rank == null || this.taxonName.nomenclature == null) {
            return '';
        }

        return createElement(
            'div',
            {
                class: 'is-inline',
            },
            interleave([
                createElement(
                    TaxonNameLabel,
                    {
                        props: {
                            taxonName: this.taxonName,
                        },
                        class: {
                            'has-text-orange': this.withColor,
                            'is-inline': true,
                        },
                    },
                ),
                ' ',
                createElement(
                    AuthorName,
                    {
                        props: {
                            authors: this.taxonName.authors,
                            exAuthors: this.taxonName.exAuthors,
                            type: this.taxonName.nomenclature.group,
                            originalTaxonName: this.taxonName.originalTaxonName,
                            publishYear: this.taxonName.publishYear,
                            taxonName: this.taxonName,
                        },
                        class: 'is-inline',
                    },
                ),
            ]),
        );
    },
    components: {
        AuthorName,
        TaxonNameLabel,
    },
};
</script>
