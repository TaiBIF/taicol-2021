<script>
    import { UndeterminedService } from '../../../services/UndeterminedService';

    const interleave = (arr, thing) => [].concat(...arr.map(n => [n, thing])).slice(0, -1)

    export default {
        render(createElement) {
            if (!this.indications || this.indications.length < 1) {
                console.log(this.indications.length);

                return createElement(
                    'div',
                    {
                        class: {
                            'is-inline': true,
                            'has-text-danger': true,
                        },
                    },
                    '::ERROR',
                );
            }

            const indication = this.indications[0];
            const service = new UndeterminedService(createElement, indication, this.isSimple);
            return service
                .setAuthors(this.taxonName)
                .setTaxonName(this.taxonName)
                .setReference(this.perUsages, this.taxonName.nomenclature?.group)
                .render();
        },
        props: {
            taxonName: {
                type: Object,
                required: true,
            },
            indications: {
                type: Array,
                required: true,
            },
            isSimple: {
                type: Boolean,
                default: false,
            },
            perUsages: {
                type: Array,
                required: true,
            },
        },
    }
</script>
