import { comboLast, fullNameAbbreviation } from './person';
import typeSpecimenKind from '../options/typeSpecimenKind';
import { groupBy } from 'lodash';

/**
 * 標本顯示邏輯
 * [type_use]: [type_sex] [type_country],
 * [type_locality]([type_locality_verbatim]),
 * [collection_day] [collection_month] [collection_year],
 * [collector] [collector_number]
 * ([herbarium] [[accession_number]], iso[type_use] [[accession_number]]).
 * @param typeSpecimen
 */
const renderGeneralTypeSpecimen = (typeSpecimen) => {
    const s = typeSpecimen;

    // [type_sex] [type_country],
    const sexCountry = [
        s.sex?.name,
        s.country?.display['en-us'].toUpperCase(),
    ].filter(Boolean).join(' ')

    // [type_locality]([type_locality_verbatim]),
    const locality = [
        s.locality,
        s.localityVerbatim ? `(${s.localityVerbatim})` : '',
    ].join('');

    // [collection_day] [collection_month] [collection_year],
    const collectionInfo = [
        s.collectionDay,
        s.collectionMonth,
        s.collectionYear,
    ].filter(Boolean).join(' ');

    // [collector] [collector_number] ([herbarium] [[accession_number]],
    const collectors = s.collectors.map(c => fullNameAbbreviation(c, true)).join(', ');
    const collectorsInfo = [
        collectors,
        s.collectorNumber,
    ].filter(Boolean).join(' ');

    // 模式標本 ([herbarium] [[accession_number]], iso[type_use] [[accession_number]]).
    const ss = s.specimens.map((s, i) => {
        return [s.herbarium, `${s.accessionNumber ? `[${s.accessionNumber}]` : ''}`].filter(Boolean).join(' ');
    });
    const useString = typeSpecimen.use.toLowerCase();
    const specimens = ss.length ? `(${(ss.slice(0, 2).join(`, iso${useString}: `) + ss.slice(2).join(' '))})` : '';

    return [
        [
            sexCountry,
            locality,
            collectionInfo,
            collectorsInfo,
        ].filter(Boolean).join(', '),
        specimens,
    ].filter(Boolean).join(' ');
}

const renderGeneralOtherTypeSpecimen = (typeSpecimen) => {
    // [citation_note_number] [type_kind]
    const kindObject = typeSpecimenKind.find(k => typeSpecimen.kind === k.id) || null;
    return [
        typeSpecimen.citationNoteNumber,
        kindObject ? `[${kindObject.key}]` : '',
    ].join(' ');
}

/**
 * Lectotype 顯示邏輯
 * 1. 非指定 => [citation_note_number] [type_kind] designated by [lecto_designated_ref]: [lecto_cite_page].
 * 2. 在此指定 => [citation_note_number] [type_kind] here designated.
 * @param typeSpecimen
 */
const renderLectotypeOtherTypeSpecimen = (typeSpecimen) => {
    const kindObject = typeSpecimenKind.find(k => typeSpecimen.kind === k.id) || null;

    if (typeSpecimen.isDesignated) {
        return [
            typeSpecimen.citationNoteNumber,
            kindObject ? `[${kindObject.key}]` : '',
            'here designated',
        ].join(' ');
    }

    return [
        typeSpecimen.citationNoteNumber,
        kindObject ? `[${kindObject.key}]` : '',
        'designated by',
        [
            [
                comboLast(typeSpecimen.lectoDesignatedReference?.authors || []),
                typeSpecimen.lectoDesignatedReference?.publishYear,
            ].join(', '),
            typeSpecimen.lectoCitePage,
        ].filter(Boolean).join(': '),
    ].filter(Boolean).join(' ');
}

const renderLectotypeTypeSpecimen = (typeSpecimen) => {
    if (typeSpecimen.isDesignated) {
        return [
            renderGeneralTypeSpecimen(typeSpecimen),
            'here designated',
        ].join(' ');
    }

    return [
        renderGeneralTypeSpecimen(typeSpecimen),
        'designated by',
        [
            [
                comboLast(typeSpecimen.lectoDesignatedReference?.authors || []),
                typeSpecimen.lectoDesignatedReference?.publishYear,
            ].join(', '),
            typeSpecimen.lectoCitePage,
        ].filter(Boolean).join(': '),
    ].filter(Boolean).join(' ');
}

export const combo = (typeSpecimens) => {
    if (!typeSpecimens.length) {
        return '';
    }

    return Object.entries(groupBy(typeSpecimens, 'use'))
        .filter(([useKey]) => useKey !== 'null')
        .map(([useKey, typeSpecimens]) => {
            // 首字大寫
            const useString = useKey.charAt(0).toUpperCase() + useKey.slice(1);

            let typeSpecimensString = '';
            if (useKey === 'lectotype') {
                typeSpecimensString = typeSpecimens
                    .map(typeSpecimen => {
                        // [kind] 「標本」與其他類型的差別
                        if (typeSpecimen.kind === 1) {
                            return renderLectotypeTypeSpecimen(typeSpecimen);
                        } else {
                            return renderLectotypeOtherTypeSpecimen(typeSpecimen);
                        }
                    }).join('; ');
            } else {
                typeSpecimensString = typeSpecimens
                    .map(typeSpecimen => {
                        // [kind] 「標本」與其他類型的差別
                        if (typeSpecimen.kind === 1) {
                            return renderGeneralTypeSpecimen(typeSpecimen);
                        } else {
                            return renderGeneralOtherTypeSpecimen(typeSpecimen);
                        }
                    }).join('; ');
            }

            return `${useString}: ${typeSpecimensString}`;
        }).join('. ');
};
