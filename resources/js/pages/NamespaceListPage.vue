<template>
    <div class="container">
        <br/>
        <table class="table is-fullwidth is-hoverable">
            <thead>
            <tr>
                <td></td>
                <td v-text="$t('functions.myNamespaces')"/>
                <td v-text="$t('forms.lastUpdatedAt')"/>
                <td>
                    <button class="button is-small is-text" v-on:click="isShowAddColumn = !isShowAddColumn">
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
            </tr>
            </thead>
            <tbody>
            <tr v-if="isShowAddColumn">
                <td></td>
                <td>
                    <div class="field is-grouped">
                        <div class="control is-expanded">
                            <general-input :placeholder="$t('forms.namespace.titlePlaceholder')"
                                           v-model="newNamespaceTitle"
                                           v-on:pressEnter="onAddNewNamespace"
                            />
                        </div>
                        <button class="button" v-on:click="onAddNewNamespace"
                                v-text="$t('forms.actions.submit')"></button>
                    </div>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr v-for="(namespace, index) in myNamespaces" v-on:click="onGoNamespace(namespace)">
                <td></td>
                <td v-if="namespace.isEdit" width="50%">
                    <div class="field is-grouped">
                        <div class="control is-expanded">
                            <general-input :placeholder="$t('forms.namespace.titlePlaceholder')"
                                           class="is-expanded"
                                           v-model="namespace.title"
                                           v-on:pressEnter="onEditNamespace(namespace)"
                            />
                        </div>
                        <button class="button"
                                v-on:click="() => onEditNamespace(namespace)"
                                v-text="$t('forms.actions.submit')"/>
                    </div>
                </td>
                <td v-else width="50%">
                    <span v-text="namespace.title"></span>
                    <button class="button is-small is-text has-text-grey-lighter"
                            v-on:click="(e) => onToggleEditNamespace(e, index)">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
                <td v-text="namespace.updatedAt"></td>
                <td>
                    <b-tooltip :active="namespace.isConfirmDelete"
                               :auto-close="['escape', 'outside']"
                               position="is-top"
                               type="is-light">
                        <button class="button is-text is-small has-text-grey-lighter"
                                v-on:click="(e) => onShowConfirmDelete(e, index)">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <template v-slot:content>
                            <p class="has-text-weight-bold">
                                {{ $t('forms.deleteConfirmQuestion') }}
                            </p>
                            <div class="buttons">
                                <button class="button is-small is-danger is-outlined"
                                        v-on:click="(e) => onDeleteNamespace(e, index, namespace)"
                                >
                                    {{ $t('forms.actions.deleteConfirmed') }}
                                </button>
                                <button class="button is-small is-primary is-outlined"
                                        v-on:click="(e) => onCancelDeleteNamespace(e, namespace)"
                                >
                                    {{ $t('forms.actions.cancel') }}
                                </button>
                            </div>
                        </template>
                    </b-tooltip>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
    import GeneralInput from '../components/GeneralInput';
    import { openNotify } from '../utils';

    export default {
        components: { GeneralInput },
        data() {
            return {
                myNamespaces: [],
                pageStatus: this.$c.PAGE_IS_LOADING,
                isShowAddColumn: false,
                newNamespaceTitle: '',
            }
        },
        mounted() {
            this.axios.get(`/namespaces`)
                .then(({ data: { data } }) => {
                    this.myNamespaces = data.map(n => ({ ...n, isConfirmDelete: false }));
                    this.pageStatus = this.$c.PAGE_IS_SUCCESS;
                });
            this.$store.commit('breadcrumb/SET_ITEMS', [
                {
                    url: '#',
                    name: this.$t('functions.myNamespaces'),
                },
            ]);
        },
        methods: {
            onToggleEditNamespace(e, index) {
                e.stopPropagation();
                this.myNamespaces[index].isEdit = true;
                this.$forceUpdate();
            },
            onEditNamespace(namespace) {
                this.axios.put(`/namespaces/${namespace.id}`, { title: namespace.title })
                    .then(() => {
                        openNotify(this.$t('forms.saveSuccess'));
                        namespace.isEdit = false;
                        this.$forceUpdate();
                    })
                    .catch(() => {
                        // TODO
                    });
            },
            onAddNewNamespace() {
                this.axios.post(`/namespaces`, { title: this.newNamespaceTitle })
                    .then(({ data: { data } }) => {
                        this.myNamespaces.unshift(data);
                        this.isShowAddColumn = false;
                        this.newNamespaceTitle = '';
                        openNotify(this.$t('forms.saveSuccess'));
                    });
            },
            onShowConfirmDelete(e, index) {
                e.stopPropagation();
                this.myNamespaces = this.myNamespaces.map(n => ({ ...n, isConfirmDelete: false }));
                this.myNamespaces[index].isConfirmDelete = true;
            },
            onDeleteNamespace(e, index, namespace) {
                e.stopPropagation();
                namespace.isConfirmDelete = true;
                this.axios.delete(`/namespaces/${namespace.id}`)
                    .then(({ data: { data } }) => {
                        namespace.isConfirmDelete = false;
                        this.myNamespaces.splice(index, 1);
                        openNotify(this.$t('forms.deleteSuccess'));
                    });
            },
            onCancelDeleteNamespace(e, namespace) {
                e.stopPropagation();
                namespace.isConfirmDelete = false;
                this.$forceUpdate();
            },
            onGoNamespace(namespace) {
                if(!namespace.isEdit) {
                    this.$router.push(`/namespaces/${namespace.id}`);
                }
            },
        },
    }
</script>
<style lang="scss" scoped>
    tbody {
        tr {
            cursor: pointer;
        }
    }

    .buttons {
        margin-bottom: .2rem;
        margin-top: .2rem;
    }
</style>
