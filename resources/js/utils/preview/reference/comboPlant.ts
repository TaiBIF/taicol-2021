import { TYPE_BOOK, TYPE_BOOK_ARTICLE } from '../../consts/reference';
import { comboAbbr } from '../person';

/**
 * 植物
 * 發表文獻：[author_name_abbr], [journal_abbr] [volume]([issue]): [page_name], [cite_figure]. [publish_year];
 * [reference_authors: last_name], [journal_abbr] [volume]([issue]): [page_name], [cite_figure]. [publish_year].
 * */
export default (references, names = comboAbbr) => {
    if (!references) {
        return '';
    }

    return references.map((ref) => {
        if (!ref) {
            return '';
        }

        const page = [
            ref.showPage ?? '',
            ref.figure ?? '',
        ].filter(Boolean).join(', ');

        // [volume]([issue]): [page_name]
        const volume = [
            [
                `${ref.target?.properties?.volume || ''}`,
                ref.target?.properties?.issue ? `(${ref.target?.properties?.issue})` : '',
            ].filter(Boolean).join(''),
            page,
        ].filter(Boolean).join(': ');

        let title = '';

        // 書籍或書籍章節有版本
        if (ref.target?.type === TYPE_BOOK || ref.target?.type === TYPE_BOOK_ARTICLE) {
            title = [
                names(ref.target?.authors || []),
                ref.target?.properties?.bookTitleAbbreviation,
                ref.target?.properties?.edition ? `${ref.target?.properties.edition} ed.` : '',
                volume,
            ].filter(Boolean).join(', ');
        } else {
            title = [
                [
                    names(ref.target?.authors || []),
                    ref.target?.properties?.bookTitleAbbreviation,
                ].filter(Boolean).join(', '),
                volume,
            ].filter(Boolean).join(' ');
        }

        return [
            [
                title,
                ref.target?.publishYear,
            ].filter(Boolean).join('. '),
            ref.nameInReference ? `'${ref.nameInReference}'` : '',
            ref.proParte ? 'pro parte' : '',
        ].filter(Boolean).join(', ');
    })
        .filter(Boolean)
        .join('; ');
};
