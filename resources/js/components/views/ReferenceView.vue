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
                    <!-- 書籍詳細資訊 此功能先暫時拿掉 -->
                    <!-- <a v-if="book" class="is-inline"
                       v-on:click="() => showBook = !showBook">
                        <i v-if="!showBook" class="fas fa-caret-down"></i>
                        <i v-else class="fas fa-caret-down"></i>
                    </a> -->
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
                    <div v-if="book" class="box has-background-greyer">
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
        <!-- 文獻編輯紀錄  -->
        <div class="row ">
            <p class="text-[14px] mt-8 mb-2 font-bold is-5 is-inline-block has-text-grey"
                v-on:click="toggleEditLog('reference')">
                {{ $t('common.editReferenceHistory') }} <a><i class="fas" :class="{'fa-chevron-down': referenceEditLogHidden, 'fa-chevron-up': !referenceEditLogHidden}"></i></a>
            </p>
            <div :class="{ hidden: referenceEditLogHidden }">
                <table class="table text-[14px] is-fullwidth max-w-full has-text-grey">
                    <thead class="font-bold">
                    <tr>
                        <th class="w-[80px] has-text-grey" v-text="$t('common.editDate')"/>
                        <th class="w-[70px] has-text-grey" v-text="$t('common.editAction')"/>
                        <th class="w-[270px] has-text-grey" v-text="$t('common.editItem')"/>
                        <th class="w-[90px] has-text-grey" v-text="$t('common.editBy')"/>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="editLog in referenceEditLogs">
                        <td>{{ editLog.createdAt }}</td>
                        <td>{{ editLog.action }}</td>
                        <td>{{ editLog.item }}</td>
                        <td>{{ editLog.by }}</td>
                    </tr>
                    </tbody>
                </table>
                <button v-if=" referenceLogMore === true " v-on:click="fetchEditLog('reference',referenceLogOffset)"  class="button is-small"> more +</button>
            </div>
        </div>
        <!-- 異名表編輯紀錄  -->
        <div class="row">
            <p class="text-[14px] mt-8 mb-2 font-bold is-5 is-inline-block has-text-grey"
                v-on:click="toggleEditLog('usage')">
                {{ $t('common.editUsageHistory') }} <a><i class="fas" :class="{'fa-chevron-down': usageEditLogHidden, 'fa-chevron-up': !usageEditLogHidden}"></i></a>
            </p>
            <div :class="{ hidden: usageEditLogHidden }">
                <table class="table text-[14px] is-fullwidth max-w-full has-text-grey">
                    <thead class="font-bold">
                    <tr>
                        <th class="w-[80px] has-text-grey" v-text="$t('common.editDate')"/>
                        <th class="w-[70px] has-text-grey" v-text="$t('common.editAction')"/>
                        <th class="w-[270px] has-text-grey" v-text="$t('common.editItem')"/>
                        <th class="w-[90px] has-text-grey" v-text="$t('common.editBy')"/>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="editLog in usageEditLogs">
                        <td>{{ editLog.createdAt }}</td>
                        <td>{{ editLog.action }}</td>
                        <td><span v-if=" editLog.editedName !== null ">{{ editLog.editedName }} ({{ editLog.nameStatus }})</span><span v-if=" editLog.item !== null ">: {{ editLog.item }}</span></td>
                        <td>{{ editLog.by }}</td>
                    </tr>
                    </tbody>
                </table>
                <button v-if=" usageLogMore === true " v-on:click="fetchEditLog('usage',usageLogOffset)"  class="button is-small"> more +</button>
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
            usageEditLogs: [],
            usageLogMore: false,
            usageLogOffset: 0,
            usageEditLogHidden: true,
            referenceEditLogs: [],
            referenceLogMore: false,
            referenceLogOffset: 0,
            referenceEditLogHidden: true,
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
    mounted() {
        this.fetchEditLog('usage', 0);
        this.fetchEditLog('reference', 0);
    },
    methods: {
        toggleEditLog(log_type){
            this[`${log_type}EditLogHidden`] = !this[`${log_type}EditLogHidden`];
        },
        fetchEditLog(log_type, offset) {

            this.axios.get(`/edit-logs?log_type=${log_type}&log_id=${this.$route.params.id}&offset=${offset}`)
                .then(({ data: { editLogs, logMore, logOffset }  }) => {
                    this[`${log_type}EditLogs`].push(...editLogs);
                    this[`${log_type}LogMore`] = logMore;
                    this[`${log_type}LogOffset`] = logOffset;
                });
        },
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
    // box-shadow: $shadow;

    img {
        // object-fit: cover;
        height: 100%;
        object-fit: scale-down;
        position: absolute;
        width: 100%;
    }
}

.is-middle-content {
    display: flex;
    align-items: center;
}
</style>
