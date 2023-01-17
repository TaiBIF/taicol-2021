import { PersonDetail, PersonListRow } from '../../types';

export const personListRowResource = (personRow): PersonListRow => ({
    abbreviationName: personRow.abbreviationName ? personRow.abbreviationName : '',
    originalFullName: personRow.originalFullName ? personRow.originalFullName : '',
    yearOfPublication: personRow.yearOfPublication ? personRow.yearOfPublication : '',
    firstName: personRow.firstName ? personRow.firstName : '',
    lastName: personRow.lastName ? personRow.lastName : '',
    middleName: personRow.middleName ? personRow.middleName : '',
    biologicalGroup: personRow.biologicalGroup ? personRow.biologicalGroup : '',
});

export const personDetailResource = (person?): PersonDetail => ({
    id: person?.id ?? null,
    abbreviationName: person?.abbreviationName ?? '',
    originalFullName: person?.originalFullName ?? '',
    yearOfPublication: person?.yearOfPublication ?? '',
    firstName: person?.firstName ?? '',
    lastName: person?.lastName ?? '',
    middleName: person?.middleName ?? '',

    otherNames: person?.otherNames ?? '',
    nationality: person?.nationality ?? null,
    yearLife: person?.yearLife ?? '',
    yearOfBirth: person?.yearOfBirth ?? '',
    yearOfDeath: person?.yearOfDeath ?? '',
    biologyDepartments: person?.biologyDepartments ?? [],
    biologicalGroup: person?.biologicalGroup ?? '',
});

export const fullName = (
    { lastName, firstName, middleName }: { lastName: string; firstName: string; middleName: string },
): string => [
    lastName,
    [firstName, middleName]
        .filter(Boolean)
        .join(' '),
]
    .join(', ');

export default fullName;
