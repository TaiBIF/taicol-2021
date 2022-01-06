<template>
    <div>
        <div class="columns row">
            <div class="column is-3">
                <div class="cover">
                    <img :src="coverPath || '/images/no-image.jpg'"/>
                </div>
            </div>
            <div class="column is-9 is-middle-content">
                <div>
                    <p class="label">{{ title }}</p>
                    <p class="subtitle is-6">{{ subtitle }}</p>
                </div>
            </div>
        </div>
        <hr/>
        <div class="row">
            <div class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.type')"/>
                </div>
                <div class="column is-9">
                    <p>{{ typeDisplay }}</p>
                </div>
            </div>

            <div class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.publishYear')"/>
                </div>
                <div class="column is-9" v-text="publishYear"/>
            </div>
            <div class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.author')"/>
                </div>
                <div class="column is-9">
                    <p v-for="author in authors">
                        <router-link :key="`author_${author.id}`"
                                     :to="`/persons/${author.id}`"
                                     class="my-link"
                                     v-text="author.fullName"/>
                    </p>
                </div>
            </div>

            <div class="columns" v-if="properties.bookTitle">
                <div class="column is-3">
                    <label class="label is-inline" v-text="$t(`forms.reference.${type === 1 ? 'journal' : 'bookTitle'}`)"/>
                    <a class="is-inline" v-if="book"
                       v-on:click="() => showBook = !showBook">
                        <i class="fas fa-caret-down" v-if="!showBook"></i>
                        <i class="fas fa-caret-down" v-else></i>
                    </a>
                </div>
                <div class="column is-9">
                            <span v-if="properties.bookTitleAbbreviation"
                                  v-text="`${properties.bookTitleAbbreviation} = `"/>
                    {{ properties.bookTitle }}
                </div>
            </div>
            <div class="columns" v-if="showBook">
                <div class="column is-1"></div>
                <div class="column is-10">
                    <div class="box has-background-grey-lighter" v-if="book">
                        <book-view v-bind="book"></book-view>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-3" v-if="properties.volume">
                    <label class="label"
                           v-text="type === 1 ? $t('forms.reference.volume') : $t('forms.reference.volumeBook')"/>
                </div>
                <div class="column is-9"
                     v-if="properties.volume"
                     v-text="properties.volume"/>
            </div>
            <div class="columns" v-if="properties.edition">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.edition')"/>
                </div>
                <div class="column is-9" v-text="properties.edition"></div>
            </div>
            <div class="columns">
                <div class="column is-3" v-if="properties.issue">
                    <label class="label" v-text="$t('forms.reference.issue')"/>
                </div>
                <div class="column is-9" v-if="properties.issue" v-text="properties.issue"></div>
            </div>
            <div class="columns">
                <div class="column is-3" v-if="properties.articleNumber">
                    <label class="label" v-text="$t('forms.reference.articleNumber')"/>
                </div>
                <div class="column is-9" v-if="properties.articleNumber"
                     v-text="properties.articleNumber"/>
            </div>
            <div class="columns">
                <div class="column is-3"
                     v-if="properties.doi">
                    <label class="label" v-text="$t('forms.reference.doi')"/>
                </div>
                <div class="column is-9"
                     v-if="properties.doi">
                    <a :href="`http://doi.org/${properties.doi}`"
                       target="_blank"
                       v-text="`http://doi.org/${properties.doi}`"/>
                </div>
            </div>
            <div class="columns">
                <div class="column is-3" v-if="properties.chapter">
                    <label class="label" v-text="$t('forms.reference.chapter')"/>
                </div>
                <div class="column is-9"
                     v-if="properties.chapter"
                     v-text="properties.chapter"/>
            </div>
            <div class="columns" v-if="properties.pagesRange">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.pagesRange')"/>
                </div>
                <div class="column is-9" v-text="properties.pagesRange"/>
            </div>
            <div class="columns" v-if="properties.url">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.url')"/>
                </div>
                <div class="column is-9" v-if="properties.url">
                    <a class="word-break" :href="properties.url" v-text="properties.url" target="_blank"/>
                </div>
            </div>
            <div class="columns" v-if="language">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.language')"/>
                </div>
                <div class="column is-9" v-text="`${$t(`forms.reference.languages.${language.id}`)}`"/>
            </div>
            <div class="columns" v-if="properties.copyright">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.copyright')"/>
                </div>
                <div class="column is-9" v-text="properties.copyright"/>
            </div>
            <div class="columns" v-if="note">
                <div class="column is-3">
                    <label class="label" v-text="$t('forms.reference.note')"/>
                </div>
                <div class="column is-9" v-text="note"/>
            </div>
            <div class="columns" v-if="showImport">
                <div class="column is-3">

                </div>
                <div class="column">
                    <router-link :to="`/references/${id}/edit`"
                                 class="button"
                                 v-text="$t('functions.editReference')"
                    ></router-link>
                    <button class="button" v-if="authenticated"
                        v-on:click="onShowImportUsage">匯入異名表</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import BookView from './BookDetailView';
    import referenceTypes from '../../utils/options/referenceTypes';
    import { mapGetters } from "vuex";

    export default {
        props: {
            id: {
                type: Number,
            },
            type: {
                type: Number,
                required: true,
            },
            publishYear: {
                type: String,
                required: true,
            },
            properties: {
                type: Object,
                required: true,
            },
            title: {
                type: String,
                required: true,
            },
            subtitle: {
                type: String,
                required: true,
            },
            language: {
                type: Object,
            },
            authors: {
                type: Array,
                required: true,
            },
            book: {
                type: Object,
            },
            note: {
                type: String,
                required: true,
            },
            coverPath: {
                type: String,
            },
            showImport: {
                default: false,
            }
        },
        components: {
            BookView,
        },
        data() {
            return {
                showBook: false,
            }
        },
        computed: {
            ...mapGetters({
                authenticated: 'auth/authenticated',
            }),
            typeDisplay() {
                const typeObject = referenceTypes.find(type => type.value === this.type);
                return typeObject ? this.$t(`forms.reference.typeOptions.${typeObject.value}`) : '';
            },
        },
        methods: {
            onShowImportUsage() {
                this.$store.commit('openModal', {
                    component: () => import('./../modals/NamespaceImport'),
                    props: {
                        referenceId: this.id,
                    }
                })
            }
        }
    }
</script>
<style lang="scss" scoped>
    .word-break {
        word-break: break-word;
    }

    .row {
        padding: 0rem 6vw;
    }

    .cover {
        height: 120px;
        box-shadow: $shadow;

        img {
            object-fit: cover;
            position: absolute;
            width: 100%;
            height: 100%;
        }
    }

    .is-middle-content {
        display: flex;
        align-items: center;
    }
</style>
