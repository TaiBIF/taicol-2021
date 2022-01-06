<script>
    import { MisappliedService } from '../../../services/MisappliedService';

    export default {
        render(createElement) {
            if (this.indications.length < 1) {
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
            const service = new MisappliedService(createElement, indication, this.isSimple);
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
<style lang="scss" scoped>
    div {
        display: inline;
    }
</style>
