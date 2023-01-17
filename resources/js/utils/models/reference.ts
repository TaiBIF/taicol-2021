import { ReferenceModel } from '../../types';

export const referenceResource = (reference): ReferenceModel => ({
    id: reference?.id ?? null,
    coverPath: reference?.coverPath ?? '',
    type: reference?.type ?? '',

    title: reference?.title ?? '',
    subtitle: reference?.subtitle ?? '',
    publishYear: reference?.publishYear ?? '',
    language: reference?.language ?? null,
    properties: reference?.properties ?? '',
    book: reference?.book ?? '',
    note: reference?.note ?? '',
    authors: reference?.authors ?? '',
});
