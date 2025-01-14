import { comboLast } from '../person';

/**
 * 動物
 * [last_name], [publish_year]: [page_name], [cite_figure]
 * */
export default (references, names = comboLast) => {
    if (!references) {
        return '';
    }

    return references.map((ref) => {
        if (!ref) {
            return '';
        }

        const publishYear = ref.target?.publishYear || '';

        const title = [
            names(ref.target?.authors || []),
            publishYear,
        ].filter(Boolean).join(', ');

        let showPage = '';

        if (ref.target?.properties?.articleNumber && ref.showPage){
            showPage = ref.target?.properties?.articleNumber + '(' + ref.showPage  +')';
        } else if (ref.showPage){
            showPage = ref.showPage;
        }

        const page = [
            showPage ?? '',
            // ref.showPage ?? '',
            ref.figure ?? '',
        ].filter(Boolean).join(', ');

        return [
            [
                title,
                page,
            ].filter(Boolean).join(': '),
            ref.nameInReference ? `'${ref.nameInReference}'` : '',
            ref.proParte ? 'pro parte' : '',
        ].filter(Boolean).join(', ');
    }).filter(Boolean).join('; ');
};
