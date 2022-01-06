<template>
    <div class="form">
        <div class="columns">
            <div class="column is-3">
                <div class="field">
                    <label class="label" v-text="$t('forms.reference.cover')"/>
                    <div class="image-container">
                        <img v-if="tempCoverUrl" :src="tempCoverUrl"/>
                        <img v-else-if="reference.coverPath" :src="reference.coverPath"/>
                        <img v-else src="/images/no-image.jpg"/>
                        <button v-if="reference.coverPath || tempCoverUrl" v-on:click="onRemoveImage"
                                class="button is-small is-danger is-outlined remove">
                            移除圖片
                        </button>
                    </div>
                    <b-upload v-model="reference.cover">
                        <div class="uploader is-fullwidth">
                            <div class="content has-text-centered">
                                <p>
                                    <i class="fas fa-file-image"></i>&nbsp;&nbsp;{{ $t('forms.uploadImage') }}
                                </p>
                                <span v-if="reference.cover" class="file-name">
                                    {{ reference.cover.name }}
                                </span>
                            </div>
                        </div>
                    </b-upload>
                </div>
            </div>
            <div class="column is-3">
                <div class="field">
                    <label class="label is-marked" v-text="$t('forms.reference.type')"/>
                    <reference-type-select v-model="reference.type" :errors="errors['type']"/>
                </div>
            </div>
        </div>
        <div v-if="reference.type !== 0" class="columns">
            <div class="column is-6">
                <div class="field">
                    <label class="label is-marked" v-text="$t('forms.reference.author')"/>
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
                    <label class="label is-marked" v-text="$t('forms.reference.publishYear')"/>
                    <general-input v-model="reference.publishYear" :errors="errors.publishYear"/>
                </div>
            </div>
        </div>

        <template v-if="reference.type === 1">
            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.articleTitle')"/>
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
                        <label class="label is-marked" v-text="$t('forms.reference.journal')"/>
                        <div class="control">
                            <book-select v-model="targetBook"
                                         v-on:input="onChangeBook"
                                         :errors="errors['propertiesBookTitle']"/>
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.journalAbbr')"/>
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
                        <label class="label is-marked" v-text="$t('forms.reference.volume')"/>
                        <general-input v-model="reference.properties.volume"
                                       :errors="errors['propertiesVolume']"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.issue')"/>
                        <general-input v-model="reference.properties.issue"
                                       :errors="errors['propertiesIssue']"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.pagesRange')"/>
                        <general-input v-model="reference.properties.pagesRange"
                                       :errors="errors['propertiesPagesRange']"/>
                    </div>
                </div>

                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.articleNumber')"/>
                        <general-input v-model="reference.properties.articleNumber"
                                       :errors="errors['propertiesArticleNumber']"/>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.doi')"/>
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
                            <label class="label" v-text="$t('forms.reference.articleTitle')"/>
                            <general-input v-model="reference.properties.articleTitle"
                                           :errors="errors['properties.articleTitle']"/>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label is-marked" v-text="$t('forms.reference.bookTitle')"/>
                            <book-select v-model="targetBook"
                                         v-on:input="onChangeBook"
                                         :errors="errors['propertiesBookTitle']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.bookTitleAbbr')"/>
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
                            <label class="label" v-text="$t('forms.reference.edition')"/>
                            <general-input v-model="reference.properties.edition"
                                           :errors="errors['propertiesEdition']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.volumeBook')"/>
                            <general-input v-model="reference.properties.volume"
                                           :errors="errors['propertiesVolume']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.pagesRange')"/>
                            <general-input v-model="reference.properties.pagesRange"
                                           :errors="errors['propertiesPagesRange']"/>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.chapter')"/>
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
                            <label class="label is-marked" v-text="$t('forms.reference.bookTitle')"/>
                            <book-select v-model="targetBook"
                                         v-on:input="onChangeBook"
                                         :errors="errors['propertiesBookTitle']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.bookTitleAbbr')"/>
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
                            <label class="label" v-text="$t('forms.reference.edition')"/>
                            <general-input v-model="reference.properties.edition"
                                           :errors="errors['propertiesEdition']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.volumeBook')"/>
                            <general-input v-model="reference.properties.volume"
                                           :errors="errors['propertiesVolumeBook']"/>
                        </div>
                    </div>
                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.pagesRange')"/>
                            <general-input v-model="reference.properties.pagesRange"
                                           :errors="errors['propertiesPagesRange']"/>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label" v-text="$t('forms.reference.chapter')"/>
                            <general-input v-model="reference.properties.chapter"
                                           :errors="errors['propertiesChapter']"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-if="reference.type !== 0">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.url')"/>
                        <general-input v-model="reference.properties.url"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.language')"/>
                        <language-select v-model="targetLanguage"/>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.copyright')"/>
                        <general-input v-model="reference.properties.copyright"/>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-12">
                    <div class="field">
                        <label class="label" v-text="$t('forms.reference.note')"/>
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
    import BookSelect from '../selects/BookSelect';
    import GeneralInput from '../GeneralInput';
    import ReferenceTypeSelect from '../selects/ReferenceTypeSelect';
    import PersonSelect from '../selects/PersonSelect';
    import LanguageSelect from '../selects/LanguageSelect';
    import { openNotify, getBase64 } from '../../utils';
    import { subTitle, title } from '../../utils/preview/reference';

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
                        bookTitleAbbreviation: this.reference.properties.bookTitleAbbreviation || this.targetBook?.titleAbbreviation || this.targetBook.title,
                    },
                }
                return {
                    ...r,
                    title: title(r),
                    subtitle: subTitle(r),
                }
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
            }
        },
        methods: {
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
            onAppendAuthors(person) {
                this.reference.authors.push(person.id);
            },
            onAfterCreateAPerson(author) {
                this.targetAuthors.push(author)
            },
            async submit(isPublish) {
                const app = this;
                const method = app.$route.name === 'reference-edit' ? 'PUT' : 'POST';
                const url = app.$route.name === 'reference-edit' ? `/references/${app.reference.id}` : '/references';
                const data = {
                    ...app.reference,
                    title: title(app.reference),
                    subtitle: subTitle(app.reference),
                    isPublish,
                    authors: app.targetAuthors.map(author => author.id),
                    language: app.targetLanguage?.id || '',
                    properties: {
                        ...app.reference.properties,
                        bookTitle: app.targetBook?.title || null,
                        bookTitleAbbreviation: app.reference.properties?.bookTitleAbbreviation || app.targetBook?.titleAbbreviation || app.targetBook?.title,
                    },
                    image: app.reference.cover ? await getBase64(app.reference.cover) : null,
                };

                return new Promise(async function (resolve, reject) {
                    app
                        .axios({ method, url, data })
                        .then(({ data }) => {
                            app.onAfterSubmit(data);
                            resolve();
                        })
                        .catch(({ errors }) => {
                            app.errors = errors;
                            reject();
                        });
                });
            },
        },
        components: {
            LanguageSelect,
            GeneralInput,
            BookSelect,
            ReferenceTypeSelect,
            PersonSelect,
        },
    }

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
