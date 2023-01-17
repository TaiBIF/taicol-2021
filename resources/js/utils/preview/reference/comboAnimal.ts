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

        const page = [
            ref.showPage ?? '',
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
