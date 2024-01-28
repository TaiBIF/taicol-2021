<template>
    <div class="w-screen-xl max-w-[85%] my-0 mx-auto container">
        <div class="flex flex-row-reverse py-2">
            <button class="button items-end flex justify-center items-center"
                    v-on:click="isShowAddColumn = !isShowAddColumn">
                <i class="fas fa-plus"></i>&nbsp;{{ $t('namespace.create') }}
            </button>
        </div>
        <div class="h-full overflow-y-auto">
            <table class="table is-fullwidth is-hoverable table-fixed">
                <thead class="font-bold">
                <tr>
                    <th class="w-[170px]" v-text="$t('namespace.type')"/>
                    <th class="pl-2" v-text="$t('namespace.title')"/>
                    <th class="w-[180px]" v-text="$t('namespace.lastUpdatedTime')"/>
                    <th class="w-[270px]"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="isShowAddColumn">
                    <td class="pl-2">
                        <namespace-type-select v-model="newNamespaceType"
                                               :errors="errors"
                        />
                    </td>
                    <td>
                        <div class="field is-grouped">
                            <div class="control is-expanded">
                                <general-input v-model="newNamespaceTitle"
                                               :placeholder="$t('namespace.titlePlaceholder')"
                                               v-on:pressEnter="onAddNewNamespace"
                                />
                            </div>
                            <button class="button" v-on:click="onAddNewNamespace"
                                    v-text="$t('common.save')"></button>
                            <button class="button" v-on:click="() => { isShowAddColumn = false; }"
                                    v-text="$t('common.cancel')"></button>
                        </div>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr v-for="(namespace, index) in myNamespaces">
                    <template v-if="namespace.isEdit">
                        <td>
                            <span class="pl-2" v-text="$t(`namespace.typeOptions.${namespace.type}`)"></span>
                        </td>
                        <td>
                           <div class="field is-grouped">
                               <div class="control is-expanded">
                                   <general-input v-model="namespace.title"
                                                  :placeholder="$t('namespace.titlePlaceholder')"
                                                  class="is-expanded"
                                                  v-on:pressEnter="onEditNamespace(index, namespace)"
                                   />
                               </div>
                               <button class="button"
                                       v-on:click="() => onEditNamespace(index, namespace)"
                                       v-text="$t('common.save')"/>
                               <button class="button"
                                       v-on:click="() => namespace.isEdit = false"
                                       v-text="$t('common.cancel')"/>
                           </div>
                        </td>
                    </template>
                    <template v-else>
                        <td>
                            <span class="pl-2" v-text="$t(`namespace.typeOptions.${namespace.type}`)"></span>
                        </td>
                        <td>
                            <router-link :to="{name: 'namespace-usage-list', params: {id: namespace.id}}"
                                         class="my-link pl-2">
                                <span v-text="namespace.title"></span>
                            </router-link>
                        </td>
                    </template>
                    <td v-text="namespace.updatedAt"></td>
                    <td class="function">
                        <div class="flex gap-2">
                            <button class="p-0 border-0 is-small ml-4 text-gray-500"
                                    v-on:click="(e) => onToggleEditNamespace(e, index)">
                                <i class="fas fa-edit"></i> {{ $t('namespace.editName') }}
                            </button>
                            <tooltip>
                                <button class="p-0 border-0 is-small text-gray-500">
                                    <i class="fas fa-trash-alt"></i>&nbsp;{{ $t('common.delete') }}
                                </button>
                                <template v-slot:body>
                                    <div class="w-[150px]">
                                        <p class="font-bold">
                                            {{ $t('common.deleteConfirmQuestion') }}
                                        </p>
                                        <div class="buttons">
                                            <button class="text-sm text-red-600"
                                                    v-on:click="(e) => onDeleteNamespace(e, index, namespace)"
                                            >
                                                {{ $t('common.deleteConfirmed') }}
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </tooltip>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script lang="ts">
import {
    defineComponent, inject, onBeforeUnmount, onMounted, ref,
} from '@vue/composition-api';
import GeneralInput from '../components/GeneralInput.vue';
import NamespaceTypeSelect from '../components/selects/NamespaceTypeSelect.vue';
import Tooltip from '../components/Tooltip.vue';
import { openNotify } from '../utils';

export default defineComponent({
    setup(props, context) {
        const axios: any = inject('axios');

        const app: any = context.root;
        const store = app.$store;

        const myNamespaces = ref<{ isConfirmDelete: boolean, isEdit: boolean }[]>([]);
        const newNamespaceTitle = ref<string>('');
        const newNamespaceType = ref<number>(0);
        const isShowAddColumn = ref<boolean>(false);

        const onAddNewNamespace = () => {
            axios
                .post('/namespaces', { title: newNamespaceTitle.value, type: newNamespaceType.value })
                .then(({ data: { data } }) => {
                    myNamespaces.value.unshift(data);
                    isShowAddColumn.value = false;
                    newNamespaceTitle.value = '';
                    openNotify(app.$t('common.createSuccess'));
                }).catch(() => {
                // ..
                });
        };

        const onEditNamespace = (index, namespace) => {
            axios.put(`/namespaces/${namespace.id}`, { title: namespace.title })
                .then(() => {
                    myNamespaces.value[index] = { ...myNamespaces.value[index], isEdit: false };
                    myNamespaces.value = [...myNamespaces.value];
                    openNotify(app.$t('common.saveSuccess'));
                })
                .catch(() => {
                    // TODO
                });
        };

        const onToggleEditNamespace = (e, index) => {
            e.stopPropagation();
            myNamespaces.value[index] = { ...myNamespaces.value[index], isEdit: true };
            myNamespaces.value = [...myNamespaces.value];
        };

        const onDeleteNamespace = (e, index, namespace: any) => {
            e.stopPropagation();
            myNamespaces.value[index].isConfirmDelete = true;
            axios
                .delete(`/namespaces/${namespace.id}`)
                .then(() => {
                    myNamespaces.value.splice(index, 1);
                    openNotify(app.$t('common.deleteSuccess'));
                });
        };

        onBeforeUnmount(() => {
            store.commit('breadcrumb/CLEAR_ITEMS');
        });

        onMounted(() => {
            store.commit('breadcrumb/SET_ITEMS', [
                {
                    url: '#',
                    name: app.$t('namespace.myNamespace'),
                    to: { name: 'namespaces' },
                },
            ]);

            axios
                .get('/namespaces')
                .then(({ data: { data } }) => {
                    myNamespaces.value = data.map((n) => ({ ...n, isConfirmDelete: false }));
                });
        });

        return {
            myNamespaces,
            isShowAddColumn,
            newNamespaceTitle,
            newNamespaceType,
            onAddNewNamespace,
            onEditNamespace,
            onToggleEditNamespace,
            onDeleteNamespace,

        };
    },
    methods: {},
    components: { Tooltip, GeneralInput, NamespaceTypeSelect },

});
</script>
<style lang="scss" scoped>
.container {
    height: calc(100vh - #{$navbar-height} - 5rem);
}

.table {
    border-collapse: separate;

    thead {
        background: white;
        @apply sticky top-0;
    }

    tbody {
        tr {
            cursor: pointer;
        }

        td {
            height: 57.5px;
            vertical-align: middle;
        }

        td.function {
            vertical-align: middle;
        }
    }
}

.buttons {
    margin-bottom: .2rem;
    margin-top: .2rem;
}
</style>
