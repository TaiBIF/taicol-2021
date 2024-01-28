<template>
    <div class="flex flex-col">
        <div class="w-full mb-2 sticky top-0 z-10">
            <div class="text-xl font-bold pl-5 inline-block">
                <template v-if="$route.meta.type === 'reference'">
                    {{ $t('reference.editNameArea') }}
                </template>
                <template v-else>
                    {{ $t('namespace.checklistEditArea') }}
                </template>
                <tooltip>
                    <i class="fas fa-info-circle"></i>
                    <template v-slot:body>
                        <div class="w-[700px] z-10">
                            <img :src="usageInfoImagePath" height="auto" width="800px">
                        </div>
                    </template>
                </tooltip>

            </div>
            <div v-if="!isLoading" class="buttons float-right inline-block">
                <button
                    :class="{
                            'has-background-grey': isShowTaxonName,
                            'has-text-white': isShowTaxonName,
                            'has-background-grey-lighter': !isShowTaxonName
                        }"
                    class="button is-small"
                    v-on:click="onToggleTaxonNameField"
                >
                    {{ $t('namespace.insertNameCard') }}
                </button>
                <button class="button is-small"
                        v-on:click="onOpenPropertiesModal">
                    {{ $t('namespace.setAllProperties') }}
                </button>
                <button v-if="configs.type === 'namespace'"
                        class="button is-small"
                        v-on:click="onImportUsages">
                    {{ $t('namespace.importUsages') }}
                </button>
                <button class="button is-small"
                        v-on:click="onToggleSimpleForm">
                    {{ isListSimple ? $t('namespace.listDetail') : $t('namespace.listSimple') }}
                </button>
                <button v-if="configs.type === 'namespace'"
                        class="button is-small"
                        v-on:click="onDownloadDoc">
                    {{ $t('namespace.downloadDoc') }}
                </button>
            </div>
        </div>
        <div id="usage-container" class="grow flex flex-col z-0">
            <div v-if="isShowTaxonName" class="border-b bg-white sticky top-0 z-50">
                <div class="field is-horizontal px-4 py-4">
                    <taxon-name-select v-model="newTaxonName"
                                       class="taxon-name-column"/>
                    &nbsp;
                    <button class="button" v-on:click="onAddTaxonName">{{ $t('namespace.add') }}</button>
                </div>
                <div v-if="newTaxonName" class="w-full bg-white px-4 pb-4 flex shadow-md">
                    <status-select v-model="newTaxonNameStatus" class="w-[100px] mr-4"></status-select>
                    <div v-if="newTaxonNameStatus === 'accepted'" class="flex">
                        <div class="flex items-center">
                            <span class="font-bold mr-2">
                                {{ $t('usage.isInTaiwan') }}
                            </span>
                        </div>
                        <div class="buttons has-addons">
                            <radio-button v-model="newTaxonNameIsInTaiwan"
                                          :label="$t('usage.yes')" :v="1"
                            />
                            <radio-button v-model="newTaxonNameIsInTaiwan"
                                          :label="$t('usage.unknown')" :v="2"
                            />
                            <radio-button v-model="newTaxonNameIsInTaiwan"
                                          :label="$t('usage.no')" :v="0"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div ref="usageContainer"
                 class="form-body bg-white shadow-md p-0 w-full overflow-y-auto grow">
                <div id="usage-content-container" class="p-4 min-h-full">
                    <draggable :list="usages" class="item-container draggable-container"
                               data-type="main"
                               handle=".handle"
                               tag="div"
                               v-bind="dragOptions"
                               v-on:change="onChange"
                    >

                        <div v-for="(usage,index) in usages"
                             v-if="!usage.isDeleted"
                             :key="`usage_${index}`"
                             :class="{
                                     'is-title': usage.isTitle,
                                     'is-indent': usage.isIndent,
                                     'bg-red-50': isInvalidUsage(usage, index),
                                 }"
                             :data-index="index"
                             class="usage-row bg-white"
                             tabindex="1"
                             v-on:keypress.tab.prevent="(e) => e.preventDefault()"
                             v-on:keydown.tab.prevent="(e) => onTab(e, index)"
                             v-on:keyup.tab.prevent="(e) => e.preventDefault()"
                        >
                            <span class="handle"></span>
                            <div class="usage-content" v-on:dblclick="() => goUsage(usage)">
                                <status-dot :status="usage.status"/>
                                <template v-if="usage.nameRemark && !isListSimple && !usage.isTitle">
                                    <p v-if="usage.customNameRemark"
                                       v-html="usage.customNameRemark"/>

                                    <usage-preview
                                        v-else
                                        ref="nameRemark"
                                        :indications="getIndications(usage.properties.indications)"
                                        :per-usages="usage.perUsages"
                                        :status="usage.status"
                                        :taxon-name="usage.taxonName"
                                        :type-name="usage.typeName"
                                        :type-specimens="usage.typeSpecimens"
                                    />
                                </template>
                                <template v-else>
                                    <usage-preview
                                        ref="nameRemark"
                                        :indications="getIndications(usage.properties.indications)"
                                        :is-simple="true"
                                        :per-usages="usage.perUsages"
                                        :status="usage.status"
                                        :taxon-name="usage.taxonName"
                                        :type-name="usage.typeName"
                                        :type-specimens="usage.typeSpecimens"
                                    />
                                </template>
                            </div>
                            <div class="is-right">
                                <usage-property-short-tags :p="usage.properties"></usage-property-short-tags>
                            </div>
                            <div class="buttons bg-white bg-opacity-25 is-right">

                                <a v-if="!isUsageFormSimple" v-show="!usage.isIndent"
                                   class="button is-small is-text"
                                   v-on:click="e => onToggleTitle(e, index)"
                                >
                                    {{ $t('namespace.usageTitle') }}
                                </a>
                                <a class="close-button is-small"
                                   v-on:click="e => onRemove(e, index)">
                                </a>
                            </div>
                        </div>
                    </draggable>
                </div>
            </div>
            <div class="sticky bottom-0 bg-white p-4 w-full border-t">
                <div class="buttons is-right">
                    <a :class="{ 'is-loading': isLoading }"
                       class="button"
                       v-on:click="onSave"
                       v-text="$t('common.complete')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import draggable from 'vuedraggable';
import TaxonNameSelect from '../components/selects/TaxonNameSelect.vue';
import { openNotify } from '../utils';
import AuthorName from '../components/AuthorName.vue';
import UsagePreview from '../components/UsagePreview.vue';
import Tooltip from '../components/Tooltip.vue';
import indications from '../components/selects/indications';
import downloadUsageHtmlToDoc from '../utils/downloadUsageHtmlToDoc';
import StatusDot from '../components/StatusDot.vue';
import UsagePropertyShortTags from '../components/views/UsagePropertyShortTags.vue';
import { NamespaceType } from '../constants/namespace';
import StatusSelect from '../components/selects/StatusSelect.vue';
import RadioButton from '../components/RadioButton.vue';

export default {
    data() {
        return {
            isLoading: true,
            isListSimple: false,
            isShowTaxonName: true,
            newTaxonName: null,
            model: null,
            usages: [],
            configs: null,

            newTaxonNameStatus: 'accepted',
            newTaxonNameIsInTaiwan: 1,
        };
    },
    beforeMount() {
        this.loadConfigs();
    },
    mounted() {
        this.refresh();

        const { items } = this.$store.state.breadcrumb;

        if (this.$route.meta.type === 'reference' && items[items.length - 1]?.type === 'reference-usages-list') {
            items.splice(-1);
            this.$store.commit('breadcrumb/SET_ITEMS', items);
        }
    },
    computed: {
        usageInfoImagePath() {
            return this.$i18n.locale() === 'zh-tw' ?
                '/images/usage-info.png' : '/images/usage-info-eng.png';
        },
        dragOptions() {
            return {
                animation: 0,
                group: {
                    name: 'usages',
                    pull: false,
                    put: ['favorite-usage', 'favorite-taxon-name'],
                },
                disabled: false,
                selectedClass: 'selected',
            };
        },
        isUsageFormSimple() {
            if (this.configs.type === 'namespace') {
                return this.model.type === NamespaceType.SIMPLE;
            }
            return false;
        },
    },
    methods: {
        isInvalidUsage(usage, index) {
            if (usage.status === '') {
                return true;
            }

            if (
                usage.status === 'accepted' &&
                (!('isInTaiwan' in usage.properties) || usage.properties.isInTaiwan === null)
            ) {
                return true;
            }

            if (usage.status !== 'accepted' && index === 0) {
                return true;
            }

            return false;
        },
        loadConfigs() {
            const { id } = this.$route.params;
            if (this.$route.meta.type === 'reference') {
                this.configs = {
                    type: 'reference',
                    backUrl: {
                        name: 'reference-page',
                        params: { id },
                    },
                    listRoute: 'reference-list-page',
                    usageRoute: 'reference-usages-edit',
                };
            } else {
                this.configs = {
                    type: 'namespace',
                    backUrl: { name: 'namespace-list' },
                    listRoute: 'namespace-list',
                    usageRoute: 'namespace-usages',
                };
            }
        },
        onSave() {
            const app = this;

            let isValid = true;

            this.usages.forEach((usage, index) => {
                if (app.isInvalidUsage(usage, index)) {
                    app.$store.commit('openModal', {
                        component: () => import('../components/modals/ConfirmLeaveModal.vue'),
                        props: {
                            onLeave: () => {
                                app.$store.commit('closeModal');
                                this.$router.push(this.configs.backUrl);
                            },
                        },
                    });
                    isValid = false;
                }
            });

            if (isValid) {
                this.$router.push(this.configs.backUrl);
            }
        },
        onChange(event) {
            if (typeof event.added !== 'undefined' && event.added.element.child !== undefined) {
                const { element } = event.added;
                const { newIndex } = event.added;

                this.usages = [
                    ...this.usages.slice(0, newIndex), element, ...element.child, ...this.usages.slice(newIndex + 1),
                ];
            }

            this.onSubmit();
        },
        onTab(e, index) {
            if (this.isUsageFormSimple) {
                return;
            }

            const newIndent = !this.usages[index].isIndent;
            if (index === 0 && newIndent === true) {
                openNotify(this.$t('validation.usage.firstMustBeAccepted'), 'is-danger');
                return;
            }

            e.preventDefault();
            this.usages[index].isIndent = newIndent;
            this.usages[index].status = newIndent ? 'not-accepted' : 'accepted';
            this.$nextTick(function () {
                this.onSubmit();
            });
        },
        goUsage(usage) {
            if (usage.isTitle) {
                return;
            }

            const { id } = this.$route.params;
            this.$router.push({
                name: this.configs.usageRoute,
                params: {
                    id,
                    usageId: usage.id,
                },
            });
        },
        onToggleTitle(e, index) {
            e.stopPropagation();
            this.usages[index].isTitle = !this.usages[index].isTitle;
            this.$nextTick(function () {
                this.onSubmit();
            });
        },
        onImportUsages() {
            this.$store.commit('openModal', {
                component: () => import('../components/modals/UsagesImportModal.vue'),
                props: {
                    namespaceId: this.model.id,
                    refresh: this.refresh,
                },
            });
        },
        onRemove(e, index) {

            e.stopPropagation();
            this.usages[index].isDeleted = true;
            this.onSubmit();
        },
        onToggleSimpleForm() {
            this.isListSimple = !this.isListSimple;
        },
        onToggleTaxonNameField() {
            this.isShowTaxonName = !this.isShowTaxonName;
        },
        onAddTaxonName() {
            const { id } = this.$route.params;
            this.isLoading = true;

            const newUsage = {
                taxonNameId: this.newTaxonName.id,
                status: this.newTaxonNameStatus,
                taxonName: {
                    ...this.newTaxonName,
                    publishUsage: this.newTaxonName.usage.length ? this.newTaxonName.usage[0] : null,
                    publishReferenceName: this.newTaxonName.referenceName,
                },
                properties: {
                    indications: [],
                    isInTaiwan: this.newTaxonNameStatus === 'accepted' ? this.newTaxonNameIsInTaiwan : null,
                },
                isTitle: false,
                isIndent: this.newTaxonNameStatus === 'not-accepted' || this.newTaxonNameStatus === 'misapplied',
            };

            const usages = [
                ...this.usages,
                newUsage,
            ];

            const saveUrl
                = this.$route.meta.type === 'namespace' ? `namespaces/${id}/usages` : `reference/${id}/usages-edit`;

            this.axios
                .post(saveUrl, usages)
                .then(() => {
                    this.isLoading = false;
                    this.refresh();

                    this.$refs.usageContainer.scrollTo({
                        top: this.$refs.usageContainer.scrollHeight + 10,
                        behavior: 'smooth',
                    });
                })
                .catch(({ data, status }) => {
                    if (status === 422) {
                        openNotify(this.$t('validation.usage.firstMustBeAccepted'), 'is-danger');
                    } else {
                        openNotify(this.$t('common.error'), 'is-danger');
                    }
                });
        },
        onOpenPropertiesModal() {
            this.$store.commit('openModal', {
                component: () => import('../components/modals/UsagePropertyModal.vue'),
                props: {
                    onUpdate: this.onUpdateAllProperties,
                },
            });
        },
        onUpdateAllProperties(data) {
            const { id } = this.$route.params;

            this.axios.put(
                `${this.configs.type === 'reference' ? 'reference' : 'namespaces'}/${id}/usages-properties`,
                data,
            )
                .then(() => {
                    this.isLoading = false;
                    this.refresh();
                })
                .catch(() => {
                    openNotify('發生錯誤，資料儲存失敗', 'is-danger');
                });
        },
        async onDownloadDoc() {
            const container = document.getElementById('usage-content-container');
            downloadUsageHtmlToDoc(container, this.model.title);
        },
        onSubmit: _.debounce(function () {
            const { id } = this.$route.params;
            this.isLoading = true;

            const data = this.usages.map((usage) => {
                let result = {
                    isTitle: usage.isTitle,
                    isIndent: usage.isIndent,
                    taxonNameId: usage.taxonName.id,
                    isDeleted: usage.isDeleted === true,
                    status: usage.status,
                };

                if (usage.id) {
                    result.id = usage.id;
                } else {
                    result = {
                        ...result,
                        customNameRemark: usage.customNameRemark,
                        nameRemark: usage.nameRemark,
                        status: usage.status,
                        typeSpecimens: usage.typeSpecimens,
                        properties: usage.properties,
                        perUsages: usage.perUsages,
                        parentTaxonNameId: usage.parentTaxonNameId,
                    };
                }

                return result;
            });

            const saveUrl
                = this.$route.meta.type === 'namespace' ? `namespaces/${id}/usages` : `reference/${id}/usages-edit`;

            this.axios
                .post(saveUrl, data)
                .then(() => {
                    this.isLoading = false;
                    this.refresh();
                })
                .catch(() => {
                    openNotify('發生錯誤，資料儲存失敗', 'is-danger');
                });
        }),
        refresh() {
            this.isLoading = true;
            const { id } = this.$route.params;

            const url
                = this.configs.type === 'namespace' ? `/namespaces/${id}/usages` : `/references/${id}/usages-edit`;

            this.axios
                .get(url)
                .then(({ data }) => {
                    this.isLoading = false;
                    this.model = data;
                    this.usages = data.usages.map((u) => ({
                        ...u,
                        taxonNameId: u.taxonName?.id,
                        parentTaxonNameId: u.parentTaxonName?.id,
                    }));
                });
        },
        getIndications(indicationArray) {
            return indicationArray ?
                indicationArray.map(
                    (abbreviation) => indications.find((i) => i.abbreviation === abbreviation),
                )
                    .filter(Boolean) : [];
        },
    },
    components: {
        RadioButton,
        StatusSelect,
        UsagePropertyShortTags,
        UsagePreview,
        AuthorName,
        TaxonNameSelect,
        draggable,
        Tooltip,
        StatusDot,
    },
};
</script>
<style lang="scss" scoped>
#usage-container {
    height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height} - 2.5rem);
}

.taxon-name-column {
    width: 100%;
}

.usage-row {
    border: 1px solid $light-grey;
    padding: .25rem .5rem;
    margin-bottom: .8rem;
    cursor: pointer;
    min-height: 1rem;
    display: flex;
    box-shadow: 0 0.25em .5em -0.125em rgba(85, 85, 85, 0.1), 0 0px 0 1px rgba(85, 85, 85, 0.02);

    .buttons {
        display: none;
    }

    &:hover {
        .buttons {
            display: block;
        }
    }

    &:focus, &.selected {
        outline: none;
        background: $light-grey;
    }

    &.is-title {
        border: 0;
        box-shadow: none;
        font-weight: bold;
    }

    &.is-indent {
        margin-left: 2rem;
    }

    .utitle {
        color: $orange;
    }

    .handle {
        margin-right: .5rem;
    }

    .usage-content {
        flex-grow: 1;

        p {
            display: inline;
        }
    }
}
</style>
