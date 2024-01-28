import AuthorName from '../components/AuthorName.vue';
import TaxonNameLabel from '../components/views/TaxonNameLabel.vue';
import { factory as rFactory } from '../utils/preview/reference';
import {
    animalAuthorNames,
    bacteriaAuthorNames,
    comboLast,
    plantAuthorNames,
} from '../utils/preview/person';

const interleave = (arr, thing) => [].concat(...arr.map((n) => [n, thing])).slice(0, -1);

export class MisappliedService {
    constructor(createElement, indication, isSimple = false) {
        this._c = createElement;
        this.taxonName = {};
        this.indication = indication;
        this.indicationGroupDOM = this._c(
            'span',
            {
                class: { 'has-text-danger': true },
            },
            this.indication.abbreviation,
        );
        this.isSimple = isSimple;
    }

    setAuthors(taxonName) {
        if (taxonName) {
            taxonName.properties.isApprovedList = false;
        }

        this.authorNameDOM = this._c(
            AuthorName,
            {
                props: {
                    authors: taxonName.authors,
                    exAuthors: taxonName.exAuthors,
                    type: taxonName.nomenclature.group,
                    originalTaxonName: taxonName.originalTaxonName,
                    publishYear: taxonName.publishYear,
                    taxonName,
                },
                class: {
                    'is-inline': true,
                },
            },
        );
        return this;
    }

    setTaxonName(taxonName) {
        this.taxonName = taxonName;
        this.taxonNameDOM = this._c(
            TaxonNameLabel,
            {
                props: {
                    taxonName,
                },
                class: {
                    'is-orange': true,
                },
            },
        );
        return this;
    }

    setReference(perUsages, nomenclature) {
        const referencePreview = [
            rFactory(nomenclature)(perUsages, comboLast),
        ]
            .filter(Boolean)
            .join('; ');

        this.referenceDOM = referencePreview ? this._c(
            'span',
            `${referencePreview}.`,
        ) : null;
        return this;
    }

    render() {
        let dom;
        switch (this.indication.abbreviation) {
            case 'auct. non':
                dom = this._auctNon();
                break;
            case 'nec':
                dom = this._nec();
                break;
            case 'non':
                dom = this._non();
                break;
            case 'not of':
                dom = this._notOf();
                break;
            case 'sensu':
                dom = this._sensu();
                break;
            case 'sensu auct.':
                dom = this._sensuAuct();
                break;
            default:
                throw new Error();
        }

        return this._c(
            'div',
            {
                class: { 'is-inline': true },
            },
            interleave([
                dom,
            ], ''),
        );
    }

    _auctNon() {
        if (this.isSimple) {
            return this._c(
                'div',
                {
                    class: { 'is-inline': true },
                },
                interleave([
                    this.taxonNameDOM,
                    this.indicationGroupDOM,
                    this.authorNameDOM,
                ], ' '),
            );
        }

        return this._c(
            'div',
            {
                class: { 'is-inline': true },
            },
            interleave([
                this.taxonNameDOM,
                this.indicationGroupDOM,

                interleave([
                    this.authorNameDOM, // 命名者
                    this.referenceDOM, // 引用文獻
                ], ': '),
            ], ' '),
        );
    }

    _nec() {
        if (this.isSimple) {
            return this._c(
                'div',
                {
                    class: { 'is-inline': true },
                },
                interleave([
                    this.taxonNameDOM,
                    this.indicationGroupDOM,
                    this.authorNameDOM,
                ], ' '),
            );
        }

        return this._c(
            'div',
            {
                class: { 'is-inline': true },
            },
            interleave([
                this.taxonNameDOM,
                interleave([
                    this.referenceDOM, // 引用文獻
                    interleave([
                        this.indicationGroupDOM, // 標註
                        this.authorNameDOM, // 命名者
                    ], ' '),
                ], ', '),
            ], ': '),
        );
    }

    _non() {
        let authorName = '';

        if (this.taxonName.nomenclature.group === 'animal') {
            authorName = animalAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
                this.taxonName.publishYear,
                this.taxonName,
            );
        } else if (this.taxonName.nomenclature.group === 'plant'
        ) {
            authorName = plantAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
            );
        } else if (
            this.taxonName.nomenclature.group === 'bacteria'
            || this.taxonName.nomenclature.group === 'virus'
        ) {
            authorName = bacteriaAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
                this.taxonName.publishYear,
                this.taxonName,
                false,
            );
        }

        if (this.isSimple) {
            return this._c(
                'div',
                {
                    class: { 'is-inline': true },
                },
                interleave([
                    this.taxonNameDOM,
                    [
                        '(',
                        interleave([
                            this.indicationGroupDOM,
                            authorName.replace(/[\(\)]/g, ''), // 命名者
                        ], ' '),
                        ')',
                    ],
                ].filter(Boolean), ' '),
            );
        }

        return this._c(
            'div',
            {
                class: { 'is-inline': true },
            },
            interleave([
                this.taxonNameDOM,
                interleave([
                    [
                        '(',
                        interleave([
                            this.indicationGroupDOM,
                            authorName.replace(/[\(\)]/g, ''), // 命名者
                        ], ' '),
                        ')',
                    ],
                    this.referenceDOM, // 引用文獻
                ].filter(Boolean), ': '),
            ].filter(Boolean), ' '),
        );
    }

    _notOf() {
        let authorName = '';

        if (this.taxonName.nomenclature.group === 'animal') {
            authorName = animalAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
                this.taxonName.publishYear,
                this.taxonName,
            );
        } else if (this.taxonName.nomenclature.group === 'plant') {
            authorName = plantAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
            );
        } else if (
            this.taxonName.nomenclature.group === 'bacteria'
            || this.taxonName.nomenclature.group === 'virus'
        ) {
            authorName = bacteriaAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
                this.taxonName.publishYear,
                this.taxonName,
                false,
            );
        }

        if (this.isSimple) {
            return this._c(
                'div',
                {
                    class: { 'is-inline': true },
                },
                interleave([
                    this.taxonNameDOM,
                    [
                        '(',
                        interleave([
                            this.indicationGroupDOM,
                            authorName.replace(/[\(\)]/g, ''), // 命名者
                        ], ' '),
                        ')',
                    ],
                ].filter(Boolean), ' '),
            );
        }

        return this._c(
            'div',
            {
                class: {
                    'is-inline': true,
                },
            },
            interleave([
                this.taxonNameDOM,
                interleave([
                    [
                        '(',
                        interleave([
                            this.indicationGroupDOM,
                            authorName.replace(/[\(\)]/g, ''), // 命名者
                        ], ' '),
                        ')',
                    ],
                    this.referenceDOM, // 引用文獻
                ].filter(Boolean), ': '),
            ].filter(Boolean), ' '),
        );
    }

    _sensu() {
        if (this.isSimple) {
            return this._c(
                'div',
                {
                    class: {
                        'is-inline': true,
                    },
                },
                interleave([
                    this.taxonNameDOM,
                    this._c(
                        'span',
                        {
                            class: { 'has-text-danger': true },
                        },
                        'non',
                    ),
                    this.authorNameDOM,
                ].filter(Boolean), ' '),
            );
        }

        return this._c(
            'div',
            {
                class: {
                    'is-inline': true,
                },
            },
            interleave([
                this.taxonNameDOM,
                this.indicationGroupDOM,
                interleave([
                    this.referenceDOM,
                    interleave([
                        this._c(
                            'span',
                            {
                                class: { 'has-text-danger': true },
                            },
                            'non',
                        ),
                        this.authorNameDOM,
                    ], ' '),
                ].filter(Boolean), ', '),
            ].filter(Boolean), ' '),
        );
    }

    _sensuAuct() {
        if (this.isSimple) {
            return this._c(
                'div',
                {
                    class: {
                        'is-inline': true,
                    },
                },
                interleave([
                    this.taxonNameDOM,
                    this._c(
                        'span',
                        {
                            class: { 'has-text-danger': true },
                        },
                        'non',
                    ),
                    this.authorNameDOM,
                ].filter(Boolean), ' '),
            );
        }

        return this._c(
            'div',
            {
                class: {
                    'is-inline': true,
                },
            },
            interleave([
                this.taxonNameDOM,
                this.indicationGroupDOM,
                interleave([
                    this.referenceDOM,
                    interleave([
                        this._c(
                            'span',
                            {
                                class: { 'has-text-danger': true },
                            },
                            'non',
                        ),
                        this.authorNameDOM,
                    ], ' '),
                ], ', '),
            ].filter(Boolean), ' '),
        );
    }
}
