import { fullNameAbbreviation } from './person';

export const combo = (typeSpecimens) => {
    /**
     * [type_use]: [type_sex] [type_country],
     * [type_locality]([type_locality_verbatim]),
     * [collection_day] [collection_month] [collection_year],
     * [collector] [collector_number]
     * ([herbarium] [[accession_number]], iso[type_use] [[accession_number]]).
     */
    if (!typeSpecimens) {
        return '';
    }

    return typeSpecimens.map((s) => {
        if (!s.use) {
            return '';
        }

        // [type_sex] [type_country],
        const sexCountry = [
            s.sex?.name,
            s.country?.display['en-us'].toUpperCase(),
        ].filter(Boolean).join(' ')

        // [type_locality]([type_locality_verbatim]),
        const locality = `${
            s.locality
        }${
            s.localityVerbatim ? `(${s.localityVerbatim})` : ''
        }`;

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

        const details = [
            sexCountry,
            locality,
            collectionInfo,
            collectorsInfo,
        ].filter(Boolean).join(', ');

        // 模式標本 ([herbarium] [[accession_number]], iso[type_use] [[accession_number]]).
        const ss = s.specimens.map((s, i) => {
            return [s.herbarium, `${s.accessionNumber ? `[${s.accessionNumber}]` : ''}`].filter(Boolean).join(' ');
        });
        const specimens = ss ? `(${(ss.slice(0, 2).join(', isotype: ') + ss.slice(2).join(' '))})` : '';

        // 首字大寫
        const useString = s.use.charAt(0).toUpperCase() + s.use.slice(1);

        const typeSpeciemens = [
            details,
            specimens,
        ].filter(Boolean).join(' ');

        return `${useString}: ${typeSpeciemens}`.trim();
    }).join(';');
};
