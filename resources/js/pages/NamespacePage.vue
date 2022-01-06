<template>
    <div class="form">
        <div class="w-full mb-2">
            <div class="text-xl font-bold pl-5 inline-block">名錄編輯區</div>
            <div class="buttons float-right inline-block">
                <button :class="{
                            'has-background-grey': isShowTaxonName,
                            'has-text-white': isShowTaxonName,
                            'has-background-grey-lighter': !isShowTaxonName
                        }"
                        class="button is-small"
                        v-on:click="onToggleTaxonNameField"
                >
                    新增學名卡片
                </button>
                <button class="button is-small"
                        v-on:click="onToggleSimpleForm">
                    {{ isListSimple ? $t('forms.namespace.listDetail') : $t('forms.namespace.listSimple') }}
                </button>
                <button class="button is-small"
                        v-on:click="onDownloadDoc">
                    下載 Word 檔
                </button>
            </div>
        </div>
        <div class="form-body bg-white shadow-md p-0 w-full" id="usage-container" ref="usageContainer">
            <div v-if="isShowTaxonName" class="border-b bg-white sticky top-0 z-50">
                <div class="field is-horizontal px-4 py-4">
                    <taxon-name-select v-model="newTaxonName"
                                       class="taxon-name-column"/>
                    &nbsp;
                    <button class="button" v-on:click="onAddTaxonName">加入</button>
                </div>
            </div>
            <div class="p-4 min-h-full" id="usage-content-container">
                <draggable v-bind="dragOptions" :list="usages"
                           class="item-container draggable-container"
                           data-type="main"
                           tag="div"
                           handle=".handle"
                           v-on:change="onChange"
                >
                    <div v-for="(usage,index) in usages"
                         v-if="!usage.isDeleted"
                         :key="`usage_${index}`"
                         :class="{
                                     'is-title': usage.isTitle,
                                     'is-indent': usage.isIndent,
                                 }"
                         :data-index="index"
                         class="usage-row"
                         tabindex="1"
                         v-on:keypress.tab.prevent="(e) => e.preventDefault()"
                         v-on:keydown.tab.prevent="(e) => onTab(e, index)"
                         v-on:keyup.tab.prevent="(e) => e.preventDefault()"
                    >
                        <span class="handle"></span>
                        <div v-on:dblclick="() => goUsage(usage)" class="usage-content">
                            <template v-if="usage.nameRemark && !isListSimple && !usage.isTitle" >
                                <p v-if="usage.customNameRemark"
                                      v-html="usage.customNameRemark"/>

                                <usage-preview
                                    v-else
                                    ref="nameRemark"
                                    :indications="getIndications(usage.properties.indications)"
                                    :per-usages="usage.perUsages"
                                    :status="usage.status"
                                    :taxon-name="usage.taxonName"
                                    :type-specimens="usage.typeSpecimens"
                                    :type-name="usage.typeName"
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
                                    :type-specimens="usage.typeSpecimens"
                                    :type-name="usage.typeName"
                                />
                            </template>
                        </div>
                        <div class="buttons bg-white bg-opacity-25 is-right" style="min-width: 100px">
                            <a v-show="!usage.isIndent"
                               class="button is-small is-text"
                               v-on:click="e => onToggleTitle(e, index)"
                            >
                                標題
                            </a>
                            <a class="close-button is-small"
                               v-on:click="e => onRemove(e, index)">
                            </a>
                        </div>
                    </div>
                </draggable>
            </div>
            <div class="sticky bottom-0 bg-white p-4 w-full border-t">
                <div class="buttons is-right">
                    <a :class="{ 'is-loading': isLoading }"
                       v-on:click="onSave"
                                 class="button"
                                 v-text="$t('forms.actions.save')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import TaxonNameSelect from '../components/selects/TaxonNameSelect';
    import draggable from 'vuedraggable';
    import { openNotify } from '../utils';
    import AuthorName from '../components/AuthorName';
    import UsagePreview from '../components/UsagePreview';
    import indications from '../components/selects/indications';
    import downloadUsageHtmlToDoc from "../utils/downloadUsageHtmlToDoc";

    export default {
        data() {
            return {
                isLoading: true,
                isListSimple: false,
                isShowTaxonName: true,
                newTaxonName: null,
                namespace: null,
                usages: [],
            }
        },
        mounted() {
            this.refresh();
        },
        computed: {
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
        },
        methods: {
            onSave() {
                this.$router.push(`/namespaces`);
            },
            onChange(event) {
                if (typeof event.added !== 'undefined' && event.added.element.child !== undefined) {
                    const element = event.added.element;
                    const newIndex = event.added.newIndex;

                    this.usages = [ ... this.usages.slice(0, newIndex), element, ... element.child, ...this.usages.slice(newIndex + 1)];
                }

                this.onSubmit();
            },
            onTab(e, index) {
                e.preventDefault();
                this.usages[index].isIndent = !this.usages[index].isIndent;
                this.$nextTick(function () {
                    this.onSubmit();
                })
            },
            goUsage(usage) {
                if (usage.isTitle) {
                    return;
                }

                this.$router.push(`/namespaces/${this.namespace.id}/usages/${usage.id}`);
            },
            onToggleTitle(e, index) {
                e.stopPropagation();
                this.usages[index].isTitle = !this.usages[index].isTitle;
                this.$nextTick(function () {
                    this.onSubmit();
                })
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
                const { id: namespaceId } = this.$route.params;
                this.isLoading = true;

                const usages = [
                    ...this.usages,
                    {
                        taxonNameId: this.newTaxonName.id,
                        taxonName: {
                            ...this.newTaxonName,
                            publishUsage: this.newTaxonName.usage.length ? this.newTaxonName.usage[0] : null,
                            publishReferenceName: this.newTaxonName.referenceName,
                        },
                        properties: { indications: [] },
                        isTitle: false,
                        isIndent: false,
                    },
                ];

                this.axios
                    .post(`namespaces/${namespaceId}/usages`, usages)
                    .then(() => {
                        this.isLoading = false;
                        this.refresh();

                        this.$refs.usageContainer.scrollTo({
                            top: this.$refs.usageContainer.scrollHeight + 10,
                            behavior: 'smooth',
                        });
                    }).catch(() => {
                        openNotify(`發生錯誤，資料儲存失敗`, 'is-danger');
                    });
            },
            async onDownloadDoc() {
                const container = document.getElementById("usage-content-container");
                downloadUsageHtmlToDoc(container, this.namespace.title);
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
                    };

                    if (usage.id) {
                        result['id'] = usage.id;
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
                        }
                    }

                    return result;
                });

                this.axios
                    .post(`namespaces/${id}/usages`, data)
                    .then(() => {
                        this.isLoading = false;
                        this.refresh();
                    }).catch(() => {
                    openNotify(`發生錯誤，資料儲存失敗`, 'is-danger');
                });
            }),
            refresh() {
                this.isLoading = true;
                const { id } = this.$route.params;
                this.axios
                    .get(`/namespaces/${id}/usages`)
                    .then(({ data }) => {
                        this.isLoading = false;
                        this.namespace = data;
                        this.usages = data.usages.map(u => {
                            return {
                                ...u,
                                taxonNameId: u.taxonName?.id,
                                parentTaxonNameId: u.parentTaxonName?.id,
                            }
                        });
                    });
            },
            getIndications(indicationArray) {
                return indicationArray ? indicationArray.map((abbreviation) => {
                    return indications.find(i => i.abbreviation === abbreviation);
                }).filter(Boolean) : [];
            },
        },
        components: {
            UsagePreview,
            AuthorName,
            TaxonNameSelect,
            draggable,
        },
    }
</script>
<style lang="scss" scoped>
    .form-body {
        height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height} - 2.5rem);
    }

    .taxon-name-column {
        width: 100%;
    }

    .usage-row {
        border: 1px solid $light-grey;
        padding: .25rem 1rem;
        margin-bottom: .8rem;
        cursor: pointer;
        min-height: 1rem;
        display: flex;
        box-shadow: 0 0.25em .5em -0.125em rgba(85, 85, 85, 0.1), 0 0px 0 1px rgba(85, 85, 85, 0.02);

        .buttons {
            visibility: hidden;
        }

        &:hover {
            .buttons {
                visibility: visible;
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
