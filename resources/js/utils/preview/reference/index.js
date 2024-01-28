import { comboLast } from '../person';
import {
    TYPE_BOOK, TYPE_BOOK_ARTICLE, TYPE_CHECKLIST, TYPE_JOURNAL,
} from '../../consts/reference';

import comboPlant from './comboPlant';
import comboAnimal from './comboAnimal';

/**
 * 期刊文章: article_title
 */
const renderBookTitle = (reference) => {
    const volume = reference.properties?.volume ?
        `vol. ${reference.properties?.volume}`
        : reference.properties?.chapter ? `ch. ${reference.properties?.chapter}` : '';

    return [
        reference.properties?.bookTitle,
        reference.properties?.edition ? `${reference.properties?.edition} ed.` : '',
        volume,
    ]
        .filter(Boolean)
        .join(', ');
};
/*
 * 書籍篇章: [article_title] or [book_title], [edition] ed., vol. [volume]or ch. [chapter]
 * 書籍: [book_title], [edition] ed., vol. [volume]or ch. [chapter]
 */
const renderBookArticleTitle = (reference) => (
    reference.properties?.articleTitle ? reference.properties?.articleTitle : renderBookTitle(reference)
);

export const title = (reference) => {
    if (!reference) {
        return '';
    }

    switch (reference.type) {
        case TYPE_JOURNAL: {
            return reference.properties?.articleTitle;
        }

        case TYPE_BOOK_ARTICLE: {
            return renderBookArticleTitle(reference);
        }
        case TYPE_BOOK:
            return renderBookTitle(reference);
        case TYPE_CHECKLIST:
            return reference.properties?.bookTitle;
        default:
            return '';
    }
};

/**
 * 期刊文章: [reference_authors:last_name], [publish_year], [journal_abbr] [volume]([issue]): [pages_range]
 */
const renderJournalSubtitle = (reference) => {
    const t = [
        reference.properties?.bookTitleAbbreviation,
    ]
        .filter(Boolean)
        .join(', ');
    const volume = reference.properties?.volume;
    const issue = reference.properties?.issue ? `(${reference.properties?.issue})` : '';
    const lastPart = [
        volume + issue,
        reference.properties?.pagesRange,
    ].filter(Boolean).join(': ');

    return [t, lastPart].filter(Boolean).join(' ');
};

/**
 * 書籍: [reference_authors:last_name], [publish_year], [book_title_abbr], [edition] ed., [volume]or[chapter]
 * @param reference
 */
const renderBookSubtitle = (reference) => {
    const volume = reference.properties?.volume ?
        `${reference.properties?.volume}`
        : reference.properties?.chapter ? `ch. ${reference.properties?.chapter}` : '';
    return [
        reference.properties?.bookTitleAbbreviation,
        reference.properties?.edition ? `${reference.properties?.edition} ed.` : '',
        volume,
    ]
        .filter(Boolean)
        .join(', ');
};

/**
 * 書籍篇章: [reference_authors:last_name], [publish_year], [book_title_abbr], [edition] ed., [volume]or[chapter]: [pages_range]
 * @param reference
 */
const renderBookArticleSubtitle = (reference) => [renderBookSubtitle(reference), reference.properties?.pagesRange].filter(Boolean).join(': ');

export const subTitle = (reference, isWithAuthors = true) => {
    if (!reference) {
        return '';
    }

    if (isWithAuthors) {
        const lastNames = comboLast(reference.authors || []);
        let subTitleResult = '';
        switch (reference.type) {
            case TYPE_JOURNAL:
                subTitleResult = renderJournalSubtitle(reference);
                break;
            case TYPE_BOOK_ARTICLE:
                subTitleResult = renderBookArticleSubtitle(reference);
                break;
            case TYPE_BOOK:
                subTitleResult = renderBookSubtitle(reference);
                break;
            case TYPE_CHECKLIST:
                subTitleResult = reference.properties?.bookTitleAbbreviation;
                break;
            default:
                subTitleResult = ':Error:';
        }

        return [
            lastNames,
            reference.publishYear,
            subTitleResult,
        ].filter(Boolean).join(', ');
    }

    switch (reference.type) {
        case TYPE_JOURNAL:
            return renderJournalSubtitle(reference);
        case TYPE_BOOK_ARTICLE:
            return renderBookArticleSubtitle(reference);
        case TYPE_BOOK:
            return renderBookSubtitle(reference);
        case TYPE_CHECKLIST:
            return reference.properties?.bookTitleAbbreviation;
        default:
            break;
    }
};

export const taxonNameInReference = (reference) => {
    if (!reference) {
        return '';
    }

    reference.properties.pagesRange = '';

    switch (reference.type) {
        case TYPE_JOURNAL:
            return renderJournalSubtitle(reference);
        case TYPE_BOOK_ARTICLE:
            return renderBookArticleSubtitle(reference);
        case TYPE_BOOK:
            return renderBookSubtitle(reference);
        case TYPE_CHECKLIST:
            return reference.properties?.bookTitleAbbreviation;
    }
};

export const factory = (type) => {
    if (type === 'animal') {
        return comboAnimal;
    }
    return comboPlant;
};

export { comboPlant, comboAnimal };
