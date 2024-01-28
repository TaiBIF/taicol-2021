/**
 * 全名
 * @param person object
 */
export const fullName = (person) => [
    person.lastName,
    [person.firstName, person.middleName]
        .filter(Boolean)
        .join(' '),
]
    .join(', ');

/**
 * 全名的縮寫
 * [last] Li  [first] Hui Lin >> Li, H.-L.
 * @param person object
 */
export const fullNameAbbreviation = (person, isOpposite = false) => {
    const firstNameAbbr = person.firstName
        .match(/(\w{1}).*[\s|-](\w{1}).*/, '$1.-$2.') ?
        person.firstName.replace(/(\w{1}).*[\s|-](\w{1}).*/, '$1.-$2.')
        : person.firstName.replace(/(\w{1}).*/, '$1.');

    if (isOpposite) {
        return [
            [
                firstNameAbbr,
                person.middleName ? `${person.middleName.replace(/(\w{1}).*/, '$1.')}` : '',
            ]
                .filter(Boolean)
                .join(' '),
            person.lastName,
        ]
            .join(' ');
    }
    return [
        person.lastName,
        [firstNameAbbr, person.middleName ? `${person.middleName.replace(/(\w{1}).*/, '$1.')}` : '']
            .filter(Boolean)
            .join(' '),
    ]
        .join(', ');
};

/**
 * 連續「全名縮寫」
 * @param persons array
 * @returns {string}
 */

export const comboFull = (persons) => persons.map((person) => fullNameAbbreviation(person)).join(', ');

/**
 * 連續「姓氏」縮寫
 * 兩人 [last_name] & [last_name]
 * 三人以上 [last_name] et al.
 * @param persons array
 * @returns {string}
 */
export const comboLast = (persons) => {
    const key = 'lastName';
    const names = (key ? persons.map((person) => person[key]) : []);

    if (names.length >= 3) {
        return `${names[0]} et al.`;
    }

    return names.filter(Boolean).join(' & ');
};

export const comboFullLast = (persons) => {
    const key = 'lastName';
    const names = (key ? persons.map((person) => person[key]) : []);
    return names.filter(Boolean)
        .join(', ')
        .replace(/(,\s)(?!.*,\s)/, ' & ');
};

/**
 * 連續「縮寫名」
 * @param persons array
 * @returns {string}
 */
export const comboAbbr = (persons) => {
    const key = 'abbreviationName';
    const names = (key ? persons.map((person) => person[key]) : []);

    if (names.length >= 3) {
        return `${names[0]} et al.`;
    }

    return names.join(' & ');
};

export const comboFullAbbr = (persons) => {
    const key = 'abbreviationName';
    const names = (key ? persons.map((person) => person[key]) : []);

    return names.filter(Boolean)
        .join(', ')
        .replace(/(,\s)(?!.*,\s)/, ' & ');
};

export const factory = (type) => {
    if (type === 'animal') {
        return comboFullLast;
    }
    if (type === 'plant') {
        return comboFullAbbr;
    }
    if (type === 'bacteria') {
        return comboLast;
    }

    return comboFull;
};

/**
 * 命名者邏輯
 */

export const animalAuthorNames = (persons, expersons = [], originName = null, publishYear = '', taxonName = null) => {
    // 若有「原始組合名」以原始組合名加上括號為「命名者」
    if (originName) {
        // 同屬內變動
        if (
            (
                taxonName?.species?.properties?.latinGenus &&
                taxonName?.species?.properties?.latinGenus === originName.properties?.latinGenus
            )
            || (
                !!taxonName?.species &&
                !!originName?.species &&
                taxonName?.species.properties?.latinGenus === originName.species.properties.latinGenus
            )
            || (
                !!taxonName &&
                !!originName?.species &&
                taxonName?.properties?.latinGenus === originName.species.properties.latinGenus
            )
        ) {
            return animalAuthorNames(originName.authors, originName.exAuthors, null, originName.publishYear);
        }

        return `(${animalAuthorNames(originName.authors, originName.exAuthors, null, originName.publishYear)})`;
    }

    // 動物的命名者加上「年份」
    return [
        [
            factory('animal')(expersons),
            factory('animal')(persons),
        ].filter(Boolean).join(' ex '), publishYear,
    ]
        .filter(Boolean)
        .join(', ');
};

export const plantAuthorNames = (persons, expersons = [], originName = null) => [// 若有「原始組合名」以原始組合名加上括號為「命名者」
    originName ? `(${plantAuthorNames(originName.authors, originName.exAuthors, null)})` : '',
    [
        factory('plant')(expersons),
        factory('plant')(persons),
    ]
        .filter(Boolean)
        .join(' ex '),
].filter(Boolean).join(' ');

export const bacteriaAuthorNames = (persons, expersons = [], originName = null, publishYear = '', taxonName = null) => {
    // 若有「原始組合名」以原始組合名加上括號為「命名者」
    let originNamePart = '';
    if (originName) {
        // 同屬內變動
        if (
            taxonName?.species?.properties?.latinGenus === originName.properties?.latinGenus
            || (
                !!taxonName?.species &&
                !!originName?.species &&
                taxonName?.species.properties?.latinGenus === originName.species.properties.latinGenus
            )
            || (
                !!taxonName &&
                !!originName?.species &&
                taxonName?.properties?.latinGenus === originName.species.properties.latinGenus
            )
        ) {
            originNamePart
                = bacteriaAuthorNames(
                    originName.authors,
                    originName.exAuthors,
                    null,
                    originName.publishYear,
                );
        }

        originNamePart = `(${bacteriaAuthorNames(
            originName.authors,
            [], // originName.exAuthors,
            null,
            originName.publishYear,
        )})`;
    }

    const exPersonsResult = [
        factory('bacteria')(expersons) ?? '',
        taxonName?.properties.initialYear ?? '',
    ].filter(Boolean).join(' ');

    // 細菌的命名者加上「年份」
    return [
        originNamePart,
        [
            exPersonsResult ? `(ex ${exPersonsResult})` : '',
            factory('bacteria')(persons),
        ].filter(Boolean).join(' '), publishYear,
        taxonName?.properties?.isApprovedList ? '(Approved Lists 1980)' : '',
    ]
        .filter(Boolean)
        .join(' ');
};

export const renderAuthorNames = (persons, type, originName, publishYear = '', expersons = []) => {
    // 動物的命名者加上年份，若有原始組合名以原始組合名未命名者
    if (originName) {
        return `(${renderAuthorNames(originName.authors, type, null, originName.publishYear, originName.exAuthors)})`;
    }

    return [
        [
            factory(type)(expersons),
            factory(type)(persons),
        ]
            .filter(Boolean)
            .join(' ex '),
        type === 'animal' ? publishYear : '',
    ]
        .filter(Boolean)
        .join(', ');
};

export const authorNameStringFactory = (type, authors, exAuthors, originalTaxonName, taxonName, publishYear = '') => {
    switch (type) {
        case 'animal':
            return animalAuthorNames(
                authors,
                exAuthors,
                originalTaxonName,
                publishYear,
                taxonName,
            );
        case 'plant':
            return plantAuthorNames(
                authors,
                exAuthors,
                originalTaxonName,
            );

        case 'bacteria':
            return bacteriaAuthorNames(
                authors,
                exAuthors,
                originalTaxonName,
                publishYear,
                taxonName,
            );
        case 'virus':
            return '';
        default:
            return '';
    }
};
