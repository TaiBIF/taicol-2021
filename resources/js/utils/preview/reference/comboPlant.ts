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

        let showPage = '';

        if (ref.target?.properties?.articleNumber && ref.showPage){
            showPage = ref.target?.properties?.articleNumber + ' (' + ref.showPage  +')';
        } else if (ref.showPage){
            showPage = ref.showPage;
        }

        const page = [
            showPage,
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

        let proParte = '';

        if (ref.proParte){
            if (ref?.proParteType){
                proParte = ref.proParteType;
                proParte = proParte.replaceAll('＿','');

                if (ref?.proParteText){
                    proParte = proParte + ' ' + ref.proParteText;
                }
            } else {
                proParte = 'pro parte';
            }
        }

        let resultParts: string[] = [];

        // 避免年份重複：只在 title 沒包含年份時才加
        const publishYear = ref.target?.publishYear || '';
        let titleStr = title;

        if (publishYear && !title.includes(publishYear)) {
            titleStr = [title, publishYear].filter(Boolean).join('. ');
        }

        resultParts.push(titleStr);

        if (ref.nameInReference) {
            resultParts.push(`'${ref.nameInReference}'`);
        }

        if (ref.proParte) {
            resultParts.push(proParte);
        }

        let result = resultParts.filter(Boolean).join(', ');

        if (ref.description) {
            result += ' (' + ref.description + ')';
        }

        // let result = [
        //     [
        //         title,
        //         ref.target?.publishYear,
        //     ].filter(Boolean).join('. '),
        //     ref.nameInReference ? `'${ref.nameInReference}'` : '',
        //     ref.proParte ? proParte : '',
        // ].filter(Boolean).join(', ');

        // if (ref.description) {
        //     result += ' (' + ref.description + ')';  // 用空格接在最後
        // }

        return result;

    }).filter(Boolean).join('; ');
};
