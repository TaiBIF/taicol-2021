export declare type PersonListRow = {
    abbreviationName: string;
    originalFullName: string;
    yearOfPublication: string;
    firstName: string;
    lastName: string;
    middleName: string;
    biologicalGroup: string;
}

export declare type PersonDetail = {
    id: number,
    abbreviationName: string;
    originalFullName: string;
    yearOfPublication: string;
    firstName: string;
    lastName: string;
    middleName: string;

    otherNames: string;
    nationality: any;
    yearLife: string;
    yearOfBirth: string;
    yearOfDeath: string;
    biologyDepartments: string[];
    biologicalGroup: string;
}

export declare type ReferenceModel = {
    id: number;
    coverPath: number;
    type: number;

    title: string;
    subtitle: string;
    publishYear: string;
    language: string;
    properties: object;
    book: object;
    note: string;
    authors: object[];
}
