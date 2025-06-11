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
            showPage = ref.target?.properties?.articleNumber + ' (' + ref.showPage  +')';
        } else if (ref.showPage){
            showPage = ref.showPage;
        }

        const page = [
            showPage ?? '',
            ref.figure ?? '',
        ].filter(Boolean).join(', ');

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

        let result = [
            [
                title,
                page,
            ].filter(Boolean).join(': '),
            ref.nameInReference ? `'${ref.nameInReference}'` : '',
            ref.proParte ? proParte : '',
        ].filter(Boolean).join(', ');

        if (ref.description) {
            result += ' (' + ref.description + ')';  // 用空格接在最後
        }

        return result;


        // return [
        //     [
        //         title,
        //         page,
        //     ].filter(Boolean).join(': '),
        //     ref.nameInReference ? `'${ref.nameInReference}'` : '',
        //     ref.proParte ? proParte : '',
        // ].filter(Boolean).join(', ');

    }).filter(Boolean).join('; ');
};
