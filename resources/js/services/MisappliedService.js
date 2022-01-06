import AuthorName from '../components/AuthorName';
import TaxonNameLabel from '../components/views/TaxonNameLabel';
import { factory as rFactory } from '../utils/preview/reference';
import { animalAuthorNames, comboLast, plantAuthorNames } from '../utils/preview/person';

const interleave = (arr, thing) => [].concat(...arr.map(n => [n, thing])).slice(0, -1)

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
        this.authorNameDOM = this._c(
            AuthorName,
            {
                props: {
                    authors: taxonName.authors,
                    exAuthors: taxonName.exAuthors,
                    type: taxonName.nomenclature.group,
                    originalTaxonName: taxonName.originalTaxonName,
                    publishYear: taxonName.publishYear,
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
                    taxonName: taxonName,
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
            referencePreview,
        ) : null;
        return this;
    }

    render() {
        switch (this.indication.abbreviation) {
            case 'auct. non':
                return this._auctNon();
            case 'nec':
                return this._nec();
            case 'non':
                return this._non();
            case 'not of':
                return this._notOf();
            case 'sensu':
                return this._sensu();
            case 'sensu auct.':
                return this._sensuAuct();
            default:
                throw new Error();
        }
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
            );
        } else if (this.taxonName.nomenclature.group === 'plant') {
            authorName = plantAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
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
            );
        } else if (this.taxonName.nomenclature.group === 'plant') {
            authorName = plantAuthorNames(
                this.taxonName.authors,
                this.taxonName.exAuthors,
                this.taxonName.originalTaxonName,
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
                    ], ' ')
                ].filter(Boolean), ', ')
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
                    ], ' ')
                ], ', ')
            ].filter(Boolean), ' '),
        );
    }
}
