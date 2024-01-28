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
                    <label class="label" v-text="$t('reference.type')"/>
                </div>
                <div class="column is-9">
                    <p>{{ typeDisplay }}</p>
                </div>
            </div>

            <div class="columns" v-if="type === ReferenceTypes.TYPE_CHECKLIST">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.checkListType')"/>
                </div>
                <div class="column is-9">
                    <p>{{ checkListTypeDisplay }}</p>
                </div>
            </div>

            <div class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.publishYear')"/>
                </div>
                <div class="column is-9" v-text="publishYear"/>
            </div>
            <div class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.author')"/>
                </div>
                <div class="column is-9">
                    <p v-for="author in authors">
                        <router-link :key="`author_${author.id}`"
                                     :to="{ name: 'person-page', params: { id: author.id }}"
                                     class="my-link"
                                     v-text="author.fullName"/>
                    </p>
                </div>
            </div>

            <div v-if="properties.bookTitle" class="columns">
                <div class="column is-3">
                    <label class="label is-inline"
                           v-text="this.$t(`reference.${referenceBookTitleKey(type)}`)"/>
                    <a v-if="book" class="is-inline"
                       v-on:click="() => showBook = !showBook">
                        <i v-if="!showBook" class="fas fa-caret-down"></i>
                        <i v-else class="fas fa-caret-down"></i>
                    </a>
                </div>
                <div class="column is-9">
                            <span v-if="properties.bookTitleAbbreviation"
                                  v-text="`${properties.bookTitleAbbreviation} = `"/>
                    {{ properties.bookTitle }}
                </div>
            </div>
            <div v-if="showBook" class="columns">
                <div class="column is-1"></div>
                <div class="column is-10">
                    <div v-if="book" class="box has-background-grey-lighter">
                        <book-view v-bind="book"></book-view>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div v-if="properties.volume" class="column is-3">
                    <label class="label"
                           v-text="type === 1 ? $t('reference.volume') : $t('reference.volumeBook')"/>
                </div>
                <div v-if="properties.volume"
                     class="column is-9"
                     v-text="properties.volume"/>
            </div>
            <div v-if="properties.edition" class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.edition')"/>
                </div>
                <div class="column is-9" v-text="properties.edition"></div>
            </div>
            <div class="columns">
                <div v-if="properties.issue" class="column is-3">
                    <label class="label" v-text="$t('reference.issue')"/>
                </div>
                <div v-if="properties.issue" class="column is-9" v-text="properties.issue"></div>
            </div>
            <div class="columns">
                <div v-if="properties.articleNumber" class="column is-3">
                    <label class="label" v-text="$t('reference.articleNumber')"/>
                </div>
                <div v-if="properties.articleNumber" class="column is-9"
                     v-text="properties.articleNumber"/>
            </div>
            <div class="columns">
                <div v-if="properties.doi"
                     class="column is-3">
                    <label class="label" v-text="$t('reference.doi')"/>
                </div>
                <div v-if="properties.doi"
                     class="column is-9">
                    <a :href="`http://doi.org/${properties.doi}`"
                       target="_blank"
                       v-text="`http://doi.org/${properties.doi}`"/>
                </div>
            </div>
            <div class="columns">
                <div v-if="properties.chapter" class="column is-3">
                    <label class="label" v-text="$t('reference.chapter')"/>
                </div>
                <div v-if="properties.chapter"
                     class="column is-9"
                     v-text="properties.chapter"/>
            </div>
            <div v-if="properties.pagesRange" class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.pagesRange')"/>
                </div>
                <div class="column is-9" v-text="properties.pagesRange"/>
            </div>
            <div v-if="properties.url" class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.url')"/>
                </div>
                <div v-if="properties.url" class="column is-9">
                    <a :href="properties.url" class="word-break" target="_blank" v-text="properties.url"/>
                </div>
            </div>
            <div v-if="language" class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.language')"/>
                </div>
                <div class="column is-9" v-text="`${$t(`reference.languages.${language.id}`)}`"/>
            </div>
            <div v-if="properties.copyright" class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.copyright')"/>
                </div>
                <div class="column is-9" v-text="properties.copyright"/>
            </div>
            <div v-if="note" class="columns">
                <div class="column is-3">
                    <label class="label" v-text="$t('reference.note')"/>
                </div>
                <div class="column is-9" v-text="note"/>
            </div>
            <div v-if="showImport" class="columns">
                <div class="column is-3">

                </div>
                <div class="column">
                    <router-link :to="{name: 'reference-edit', params: {id: id}}"
                                 class="button"
                                 v-text="$t('reference.edit')"
                    ></router-link>
                    <button v-if="authenticated" class="button"
                            v-on:click="onShowImportUsage">
                        {{ $t('reference.importNames') }}
                    </button>
                    <router-link :to="{name: 'reference-usages-list', params: {id: id}}"
                                 class="button"
                                 v-text="$t('reference.editNames')"
                    ></router-link>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { mapGetters } from 'vuex';
import BookView from './BookDetailView.vue';
import referenceTypes from '../../utils/options/referenceTypes';
import referenceCheckListTypes from '../../utils/options/referenceCheckListTypes';
import { ReferenceTypes } from '../../utils/consts/reference';

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
        },
    },
    components: {
        BookView,
    },
    data() {
        return {
            showBook: false,
        };
    },
    computed: {
        ReferenceTypes() {
            return ReferenceTypes;
        },
        ...mapGetters({
            authenticated: 'auth/authenticated',
        }),
        checkListTypeDisplay() {
            const typeObject = referenceCheckListTypes.find((type) => type.value === this.properties.checkListType);
            return typeObject ? this.$t(`reference.checkListTypeOptions.${typeObject.value}`) : '';
        },
        typeDisplay() {
            const typeObject = referenceTypes.find((type) => type.value === this.type);
            return typeObject ? this.$t(`reference.typeOptions.${typeObject.value}`) : '';
        },
    },
    methods: {
        onShowImportUsage() {
            this.$store.commit('openModal', {
                component: () => import('../modals/NamespaceImport.vue'),
                props: {
                    referenceId: this.id,
                },
            });
        },
        referenceBookTitleKey(type) {
            switch (type) {
                case 1:
                    return 'journal';
                case 2:
                case 3:
                    return 'bookTitle';
                case 5:
                    return 'checklistTitle';
                default:
                    return 'bookTitle';
            }
        },
    },
};
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
