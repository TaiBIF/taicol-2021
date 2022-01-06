<script>
    import { comboAnimal, comboPlant } from '../../../utils/preview/reference';
    import { comboAbbr, comboLast } from '../../../utils/preview/person';
    import AuthorName from '../../AuthorName';
    import { cloneDeep } from 'lodash';
    import TaxonNameLabel from '../TaxonNameLabel';

    export default {
        render(createElement) {
            const taxonNameDOM = this._c(
                TaxonNameLabel,
                {
                    props: {
                        taxonName: this.taxonName,
                    },
                    class: {
                        'is-orange': true,
                    },
                },
            );

            const referenceDOM = this.refPreview ? createElement(
                'span',
                this.refPreview,
            ) : '';

            const authorNameDOM = createElement(
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
                    class: {
                        'is-inline': true,
                    },
                },
            );

            const indicationGroupDOM = createElement(
                'div',
                {
                    class: {
                        'is-inline': true,
                    },
                },
                [
                    ...this.interleave(this.indications.map(i => {
                        return createElement(
                            'span',
                            {
                                class: {
                                    'has-text-success': this.status === 'accepted',
                                    'has-text-danger': this.status === 'not-accepted',
                                },
                            },
                            i.abbreviation,
                        )
                    }), ', '),
                ],
            );

            return createElement(
                'div',
                {
                    class: {
                        'is-inline': true,
                    },
                },
                this.interleave([
                    taxonNameDOM,
                    createElement(
                        'div',
                        {
                            class: {
                                'is-inline': true,
                            },
                        },
                        this.interleave([
                            authorNameDOM, // 命名者
                            !this.isSimple ? referenceDOM : null, // 引用文獻
                        ].filter(Boolean), this.taxonName.nomenclature.group === 'animal' ? ': ' : ', '),
                    ),
                    this.indications.length ? indicationGroupDOM : null, // 標註
                ].filter(Boolean), ' '),
            );
        },
        methods: {
            interleave: (arr, thing) => [].concat(...arr.map(n => [n, thing])).slice(0, -1),
        },
        computed: {
            refPreview() {
                if (this.taxonName.nomenclature?.group === 'animal') {
                    // 若為原始組合名，動物「命名者」有年份了，拿掉第一本 reference 的年份
                    const firstPerUsage = cloneDeep(this.perUsages[0]);
                    if (firstPerUsage?.target && !this.taxonName.originalTaxonName) {
                        firstPerUsage.target.publishYear = '';
                    }

                    return [
                        firstPerUsage ?
                            comboAnimal(
                                [firstPerUsage],
                                // 有原始組合名，則顯示 last name
                                this.taxonName.originalTaxonName ? comboLast : () => {
                                },
                            ) : '',
                        comboAnimal(this.perUsages.slice(1, this.perUsages.length), comboLast),
                    ]
                        .filter(Boolean)
                        .join('; ');
                } else {
                    const firstPerUsage = this.perUsages[0];
                    return [
                        firstPerUsage ? comboPlant(
                            [firstPerUsage],
                            () => {
                            }) : '',
                        comboPlant(this.perUsages.slice(1, this.perUsages.length), comboAbbr),
                    ]
                        .filter(Boolean)
                        .join('; ');
                }
            },
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
            status: {
                type: String,
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
        components: { AuthorName },
    }
</script>
