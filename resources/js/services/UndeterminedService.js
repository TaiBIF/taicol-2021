import AuthorName from '../components/AuthorName';
import TaxonNameLabel from '../components/views/TaxonNameLabel';
import AcceptedNotAccepted from '../components/views/usage/AcceptedNotAccepted';
import TaxonNameLabelWithUndeterminedIndication from '../components/views/TaxonNameLabelWithUndeterminedIndication';
import { factory as rFactory } from '../utils/preview/reference';
import { comboLast } from '../utils/preview/person';

const interleave = (arr, thing) => [].concat(...arr.map(n => [n, thing])).slice(0, -1)

export class UndeterminedService {
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
        this.perUsages = [];
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

    setReference(perUsages, nomenclature) {
        this.perUsages = perUsages;
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
            case '?':
            case 'sp.':
                return this._questionMark(this.indication.abbreviation);
            case 'aff.':
            case 'cf.':
                return this._between(this.indication.abbreviation);
            default:
                return this._others();
        }
    }

    _questionMark(indication) {
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
                    'div',
                    {
                        class: {
                            'is-inline': true,
                            'has-text-danger': true,
                        },
                    },
                    indication,
                ),
            ].filter(Boolean), ' '),
        );
    }

    _between(indication) {
        const taxonNameDOM = this._c(
            TaxonNameLabelWithUndeterminedIndication,
            {
                props: { taxonName: this.taxonName, indication },
                class: {
                    'is-orange': true,
                },
            },
        );

        return this._c(
            'div',
            { class: 'is-inline' },
            interleave([
                taxonNameDOM,
                this.authorNameDOM, // 命名者
                this.referenceDOM, // 引用文獻
            ].filter(Boolean), ' '),
        );
    }

    _others() {
        return this._c(
            'span',
            interleave([
                this._c(
                    AcceptedNotAccepted,
                    {
                        props: {
                            taxonName: this.taxonName,
                            indications: [this.indication],
                            status: 'not-accepted',
                            isSimple: this.isSimple,
                            perUsages: this.perUsages,
                        },
                    },
                ),
            ], ' '),
        );
    }
}
