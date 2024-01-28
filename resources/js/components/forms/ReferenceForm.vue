<template>
    <div class="form">
        <div class="columns">
            <div class="column is-3">
                <div class="field">
                    <label class="label" v-text="$t('reference.cover')"/>
                    <div class="image-container">
                        <img v-if="tempCoverUrl" :src="tempCoverUrl"/>
                        <img v-else-if="reference.coverPath" :src="reference.coverPath"/>
                        <img v-else src="/images/no-image.jpg"/>
                        <button v-if="reference.coverPath || tempCoverUrl"
                                class="button is-small is-danger is-outlined remove"
                                v-on:click="onRemoveImage">
                            {{ $t('reference.removeImage') }}
                        </button>
                    </div>
                    <b-upload v-model="reference.cover">
                        <div class="uploader is-fullwidth">
                            <div class="content text-center">
                                <p>
                                    <i class="fas fa-file-image"></i>&nbsp;&nbsp;{{ $t('reference.uploadImage') }}
                                </p>
                                <span v-if="reference.cover" class="file-name">
                                    {{ reference.cover.name }}
                                </span>
                            </div>
                        </div>
                    </b-upload>
                    <p v-for="m in errors['image']" class="is-danger">{{ m }}</p>
                </div>
            </div>
            <div class="column is-3">
                <div class="field">
                    <label class="label is-marked" v-text="$t('reference.type')"/>
                    <reference-type-select v-model="reference.type" :errors="errors['type']"/>
                </div>
            </div>
            <div class="column is-3" v-if="reference.type === ReferenceTypes.TYPE_CHECKLIST">
                <div class="field">
                    <label class="label is-marked" v-text="$t('reference.checkListType')"/>
                    <reference-check-list-type-select
                        v-model="reference.properties.checkListType"
                        :errors="errors['propertiesCheckListType']"
                    />
                </div>
            </div>
        </div>
        <div v-if="reference.type !== 0" class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked" v-text="$t('reference.author')"/>
                    <div class="control">
                        <person-select
                            ref="personSelect"
                            v-model="targetAuthors"
                            :errors="errors.authors"
                        />
                    </div>
                </div>
            </div>
            <div class="column is-3">
                <div class="field">
                    <label class="label is-marked" v-text="$t('reference.publishYear')"/>
                    <general-input v-model="reference.publishYear" :errors="errors.publishYear"/>
                </div>
            </div>
        </div>

        <template v-if="reference.type === 1">
            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('reference.articleTitle')"/>
                        <div class="control">
                            <general-input v-model="reference.properties.articleTitle"
                                           :errors="errors['properties.articleTitle']"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label is-marked" v-text="$t('reference.journal')"/>
                        <div class="control">
                            <book-select v-model="targetBook"
                                         :errors="errors['propertiesBookTitle']"
                                         v-on:input="onChangeBook"/>
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('reference.journalAbbreviation')"/>
                        <div class="control">
                            <general-input v-model="reference.properties.bookTitleAbbreviation"
                                           :errors="errors['propertiesBookTitleAbbreviation']"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-3">
                    <div class="field">
                        <label class="label is-marked" v-text="$t('reference.volume')"/>
                        <general-input v-model="reference.properties.volume"
                                       :errors="errors['propertiesVolume']"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('reference.issue')"/>
                        <general-input v-model="reference.properties.issue"
                                       :errors="errors['propertiesIssue']"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('reference.pagesRange')"/>
                        <general-input v-model="reference.properties.pagesRange"
                                       :errors="errors['propertiesPagesRange']"/>
                    </div>
                </div>

                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('reference.articleNumber')"/>
                        <general-input v-model="reference.properties.articleNumber"
                                       :errors="errors['propertiesArticleNumber']"/>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" v-text="$t('reference.doi')"/>
                        <div class="field has-addons">
                            <p class="is-static is-pre">http://doi.org/</p>
                            <general-input v-model="reference.properties.doi" :errors="errors['properties.doi']"
                                           style="width: 100%"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template v-else-if="reference.type === 2">
            <div>
                <div class="columns">
                    <div class="column is-12">
                        <div class="field">
                            <label class="label" v-text="$t('reference.articleTitle')"/>
                            <general-input v-model="reference.properties.articleTitle"
                                           :errors="errors['properties.articleTitle']"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('reference.bookTitle')"/>
                            <book-select v-model="targetBook"
                                         :errors="errors['propertiesBookTitle']"
                                         v-on:input="onChangeBook"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.bookTitleAbbreviation')"/>
                            <div class="control">
                                <general-input v-model="reference.properties.bookTitleAbbreviation"
                                               :errors="errors['propertiesBookTitleAbbreviation']"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.edition')"/>
                            <general-input v-model="reference.properties.edition"
                                           :errors="errors['propertiesEdition']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.volumeBook')"/>
                            <general-input v-model="reference.properties.volume"
                                           :errors="errors['propertiesVolume']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.pagesRange')"/>
                            <general-input v-model="reference.properties.pagesRange"
                                           :errors="errors['propertiesPagesRange']"/>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.chapter')"/>
                            <general-input v-model="reference.properties.chapter"
                                           :errors="errors['propertiesChapter']"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template v-else-if="reference.type === 3">
            <div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('reference.bookTitle')"/>
                            <book-select v-model="targetBook"
                                         :errors="errors['propertiesBookTitle']"
                                         v-on:input="onChangeBook"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.bookTitleAbbreviation')"/>
                            <div class="control">
                                <general-input v-model="reference.properties.bookTitleAbbreviation"
                                               :errors="errors['propertiesBookTitleAbbreviation']"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="columns">
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.edition')"/>
                            <general-input v-model="reference.properties.edition"
                                           :errors="errors['propertiesEdition']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.volumeBook')"/>
                            <general-input v-model="reference.properties.volume"
                                           :errors="errors['propertiesVolumeBook']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.pagesRange')"/>
                            <general-input v-model="reference.properties.pagesRange"
                                           :errors="errors['propertiesPagesRange']"/>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.chapter')"/>
                            <general-input v-model="reference.properties.chapter"
                                           :errors="errors['propertiesChapter']"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="reference.type === 5">
            <div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('reference.checklistTitle')"/>
                            <book-select v-model="targetBook"
                                         :errors="errors['propertiesBookTitle']"
                                         v-on:input="onChangeBook"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('reference.checklistAbbreviation')"/>
                            <div class="control">
                                <general-input v-model="reference.properties.bookTitleAbbreviation"
                                               :errors="errors['propertiesBookTitleAbbreviation']"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-if="reference.type !== 0">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" v-text="$t('reference.url')"/>
                        <general-input v-model="reference.properties.url" :errors="errors['propertiesUrl']"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('reference.language')"/>
                        <language-select v-model="targetLanguage"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('reference.copyright')"/>
                        <general-input v-model="reference.properties.copyright"/>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('reference.note')"/>
                        <div class="control">
                            <textarea v-model="reference.note" class="textarea"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
<script>
import BookSelect from '../selects/BookSelect.vue';
import GeneralInput from '../GeneralInput.vue';
import ReferenceTypeSelect from '../selects/ReferenceTypeSelect.vue';
import PersonSelect from '../selects/PersonSelect.vue';
import LanguageSelect from '../selects/LanguageSelect.vue';
import { getBase64, openNotify } from '../../utils';
import { subTitle, title } from '../../utils/preview/reference';
import { ReferenceTypes } from '../../utils/consts/reference';
import ReferenceCheckListTypeSelect from '../selects/ReferenceCheckListTypeSelect.vue';

export default {
    props: {
        presetData: {
            type: Object,
        },
        onAfterSubmit: {
            type: Function,
            required: true,
        },
    },
    computed: {
        ReferenceTypes() {
            return ReferenceTypes;
        },
        previewData() {
            this.reference.coverPath = this.tempCoverUrl || this.reference.coverPath;

            const r = {
                ...this.reference,
                authors: this.targetAuthors,
                book: this.targetBook,
                language: this.targetLanguage,
                properties: {
                    ...this.reference.properties,
                    bookTitle: this.targetBook?.title || null,
                    bookTitleAbbreviation: (
                        this.reference.properties.bookTitleAbbreviation
                        || this.targetBook?.titleAbbreviation
                        || this.targetBook.title
                    ),
                },
            };
            return {
                ...r,
                title: title(r),
                subtitle: subTitle(r),
            };
        },
        tempCoverUrl() {
            return this.reference.cover ? window.URL.createObjectURL(this.reference.cover) : '';
        },
    },
    data() {
        return {
            targetBook: this.presetData?.book || null,
            targetAuthors: this.presetData?.authors || [],
            targetLanguage: this.presetData?.language ? { id: this.presetData.language } : null,
            errors: {},
            reference: this.presetData || {
                coverPath: null,
                type: 0,
                publishYear: '',
                title: '',
                note: '',
                properties: {
                    bookTitle: '',
                    bookTitleAbbreviation: this.presetData?.properties?.bookTitleAbbreviation,
                },
            },
        };
    },
    methods: {
        onOverwrite({
            type,
            authors,
            publishYear,
            articleTitle,
            bookTitle,
            bookTitleAbbreviation,
            volume,
            issue,
            page,
            doi,
            url,
            language,
        }) {
            this.reference.type = type;
            this.reference.publishYear = publishYear;
            this.reference.properties.volume = volume;
            this.reference.properties.pagesRange = page;

            this.targetAuthors = authors;

            if (type === ReferenceTypes.TYPE_JOURNAL) {
                this.targetBook = { title: bookTitle };
                this.reference.properties.articleTitle = articleTitle;
                this.reference.properties.bookTitleAbbreviation = bookTitleAbbreviation;
                this.reference.properties.issue = issue;
                this.reference.properties.doi = doi;
            } else if (type === ReferenceTypes.TYPE_BOOK_ARTICLE) {
                this.reference.properties.articleTitle = articleTitle;
                this.reference.properties.url = url;
            } else if (type === ReferenceTypes.TYPE_BOOK) {
                this.reference.properties.url = url;
            }

            const lanMapping = {
                en: 'en-us',
                jp: 'jp-jp',
                de: 'de-de',
                fr: 'fr-fr',
                lat: 'lat',
            };

            if (language && !!lanMapping[language]) {
                this.targetLanguage = { id: lanMapping[language] };
            }
        },
        onRemoveImage() {
            this.reference.cover = '';
            this.reference.coverPath = '';
        },
        onChangeBook(v) {
            if (v) {
                this.reference.properties.bookTitleAbbreviation = v.titleAbbreviation;
            } else {
                this.reference.properties.bookTitleAbbreviation = '';
            }
        },
        async submit(isPublish) {
            const app = this;
            const method = app.$route.name === 'reference-edit' ? 'PUT' : 'POST';
            const url = app.$route.name === 'reference-edit' ? `/references/${app.reference.id}` : '/references';

            const reference = {
                ...app.reference,
                isPublish,
                authors: app.targetAuthors.map((author) => author.id),
                language: app.targetLanguage?.id || '',
                properties: {
                    ...app.reference.properties,
                    bookTitle: app.targetBook?.title || null,
                    bookTitleAbbreviation: (app.reference.properties?.bookTitleAbbreviation
                        || app.targetBook?.titleAbbreviation
                        || app.targetBook?.title),
                },
                image: app.reference.cover ? await getBase64(app.reference.cover) : null,
            };

            // compute reference title and subtitle with properties
            const data = {
                ...reference,
                title: title(reference),
                subtitle: subTitle({ ...reference, authors: this.targetAuthors }),
            };

            return new Promise((resolve, reject) => {
                app
                    .axios({ method, url, data })
                    .then(({ data }) => {
                        app.onAfterSubmit(data);
                        resolve();
                    })
                    .catch(({ errors, status }) => {
                        if (status === 409) {
                            openNotify(this.$t('reference.exist'), 'is-danger');
                        } else {
                            app.errors = errors;
                        }
                        reject();
                    });
            });
        },
    },
    components: {
        ReferenceCheckListTypeSelect,
        LanguageSelect,
        GeneralInput,
        BookSelect,
        ReferenceTypeSelect,
        PersonSelect,
    },
};

</script>
<style lang="scss" scoped>
.file-name {
    max-width: 100%;
}

.image-container {
    position: relative;
    height: 120px;
    box-shadow: $shadow;

    img {
        object-fit: cover;
        position: absolute;
        width: 100%;
        height: 100%;
    }

    .remove {
        position: absolute;
        bottom: 0;
        right: 0;
    }
}
</style>
