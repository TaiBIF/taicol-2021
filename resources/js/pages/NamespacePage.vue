<template>
    <div class="container">
        <div class="form">
            <div class="form-header">
                <div class="title is-5">異名表編輯區
                    <div class="buttons is-pulled-right">
                        <button :class="{
                            'has-background-grey': isShowTaxonName,
                            'has-text-white': isShowTaxonName,
                            'has-background-grey-lighter': !isShowTaxonName
                        }"
                                class="button is-pulled-right is-small"
                                v-on:click="onToggleTaxonNameField"
                        >
                            學名卡片
                        </button>
                        <button :class="{
                            'has-background-grey': !isListSimple,
                            'has-text-white': !isListSimple,
                            'has-background-grey-lighter': isListSimple
                        }"
                                class="button is-small is-pulled-right"
                                v-on:click="onToggleSimpleForm">
                            <i class="fas fa-file-alt"></i>&nbsp;&nbsp;&nbsp;
                            {{ $t('forms.namespace.listDetail') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-body box">
                <div>
                    <draggable v-bind="dragOptions" class="item-container"
                               data-type="main"
                               :list="usages"
                               ghost-class="ghost"
                               tag="div"
                    >
                        <div v-for="(usage,index) in usages"
                             v-if="!usage.isDeleted"
                             :key="`usage_${index}`"
                             :class="{
                                     'is-title': usage.isTitle,
                                     'is-indent': usage.isIndent,
                                 }"
                             class="usage-row"
                             tabindex="1"
                             :data-index="index"
                             v-on:keypress.tab.prevent="(e) => e.preventDefault()"
                             v-on:keydown.tab.prevent="(e) => onTab(e, index)"
                             v-on:keyup.tab.prevent="(e) => e.preventDefault()"
                        >
                            <div v-if="usage.nameRemark && !isListSimple && !usage.isTitle" class="usage-content">
                                <span v-if="usage.customNameRemark"
                                      v-html="usage.customNameRemark"/>
                                <span v-else
                                      v-html="usage.nameRemark"/>
                            </div>
                            <div v-else class="usage-content">
                                <span class="utitle">
                                    <!-- 屬以上的學名不斜體 -->
                                    <template v-if="usage.taxonName.rank.order >= 30">
                                        <i>{{ usage.taxonName.name }}</i>
                                    </template>
                                    <template v-else>
                                        {{ usage.taxonName.name }}
                                    </template>
                                </span>
                                <span class="usubtitle">
                                    <author-name v-bind="{
                                        authors: usage.taxonName.authors,
                                        exAuthors: usage.taxonName.exAuthors,
                                        type: usage.taxonName.nomenclature.group,
                                        originalTaxonName: usage.taxonName.originalTaxonName,
                                    }">
                                    </author-name>
                                </span>
                            </div>

                            <button v-if="!usage.isTitle"
                                    class="button is-pulled-right is-small is-text"
                                    v-on:click="goUsage(usage.id)">
                                <i class="fas fa-pen"></i>&nbsp;編輯
                            </button>

                            <button v-if="!usage.isIndent"
                                    :class="{'is-outlined': !usage.isTitle}"
                                    class="button is-pulled-right is-small is-primary"
                                    v-on:click="onToggleTitle(index)"
                            >
                                標題
                            </button>

                            <button class="button is-pulled-right is-small is-text"
                                    v-on:click="onRemove(index)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </draggable>
                </div>
                <div v-if="isShowTaxonName">
                    <div class="field is-horizontal">
                        <taxon-name-select v-model="newTaxonName" class="taxon-name-column"/>
                        &nbsp;
                        <button class="button" v-on:click="onAddTaxonName">加入</button>
                    </div>
                </div>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button"
                            :class="{ 'is-loading': isLoading }"
                            v-on:click="onSubmit"
                            v-text="$t('forms.actions.save')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import TaxonNameSelect from '../components/selects/TaxonNameSelect';
    import draggable from 'vuedraggable';
    import { factory } from '../utils/preview/person';
    import { openNotify } from '../utils';
    import AuthorName from '../components/AuthorName';

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
                    group: 'description',
                    disabled: false,
                    ghostClass: 'ghost',
                    threshold: 0,
                    multiDrag: false,
                    selectedClass: 'selected',
                };
            },
        },
        methods: {
            onTab(e, index) {
                e.preventDefault();
                this.usages[index].isIndent = !this.usages[index].isIndent;
                this.$nextTick(function() {
                    this.onSubmit();
                })
            },
            goUsage(usageId) {
                this.$router.push(`/namespaces/${this.namespace.id}/usages/${usageId}`);
            },
            onToggleTitle(index) {
                this.usages[index].isTitle = !this.usages[index].isTitle;
                this.$nextTick(function() {
                    this.onSubmit();
                })
            },
            onRemove(index) {
                this.usages[index].isDeleted = true;
                this.$forceUpdate();
            },
            onToggleSimpleForm() {
                this.isListSimple = !this.isListSimple;
            },
            onToggleTaxonNameField() {
                this.isShowTaxonName = !this.isShowTaxonName;
            },
            onAddTaxonName() {
                const item = {
                    id: null,
                    taxonNameId: this.newTaxonName.id,
                    taxonName: {
                        name: this.newTaxonName.name,
                        formattedName: this.newTaxonName.formattedName,
                        authors: this.newTaxonName.formattedAuthors,
                        publishUsage: this.newTaxonName.usage.length ? this.newTaxonName.usage[0] : null,
                        publishReferenceName : this.newTaxonName.referenceName,
                    },
                    isTitle: false,
                    isIndent: false,
                };
                this.usages.push(item);
                this.onSubmit();
            },
            onSubmit() {
                const { id } = this.$route.params;
                this.isLoading = true;

                this.axios
                    .post(`namespaces/${id}/usages`, this.usages)
                    .then(() => {
                        this.isLoading = false;
                        this.refresh();
                        openNotify(this.$t('forms.saveSuccess'));
                    });
            },
            refresh() {
                this.isLoading = true;
                const { id } = this.$route.params;
                this.axios
                    .get(`/namespaces/${id}`)
                    .then(({ data }) => {
                        this.isLoading = false;

                        this.namespace = data;
                        this.$store.commit('breadcrumb/SET_ITEMS', [
                            {
                                url: '/namespaces',
                                name: this.$t('functions.myNamespaces'),
                            },
                            {
                                url: '#',
                                name: data.title,
                            },
                        ]);
                        this.usages = data.usages.map(u => {
                            return {
                                ...u,
                                taxonNameId: u.taxonName?.id,
                                parentTaxonNameId: u.parentTaxonName?.id,
                            }
                        });
                    });
            },
            renderPersonNames: (persons, type) => factory(type)(persons)
        },
        components: {
            AuthorName,
            TaxonNameSelect,
            draggable,
        },
    }
</script>
<style lang="scss" scoped>
    .taxon-name-column {
        width: 100%;
    }

    .ghost {
        opacity: 80%;
        background-color: whitesmoke;

        .title {
            display: none;
        }
    }

    .usage-row {
        border: 1px solid $light-grey;
        padding: .25rem 1rem;
        margin-bottom: .8rem;
        cursor: pointer;
        min-height: 1rem;
        display: flex;
        box-shadow: 0 0.25em .5em -0.125em rgba(85, 85, 85, 0.1), 0 0px 0 1px rgba(85, 85, 85, 0.02);

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
        }
    }
</style>
