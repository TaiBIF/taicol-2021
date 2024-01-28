<template>
    <div>
        <div class="px-16 py-12 w-sc3 min-w-300">
            <div>
                <p class="title text-center">{{ $t('collect.collectTo') }}</p>
            </div>
            <div class="py-4 min-h-3/5">
                <div class="bg-gray-100 ">
                    <ul>
                        <li v-for="folder in folders" class="p-1 px-4 my-2 hover:bg-gray-200 cursor-pointer"
                            v-on:click="() => folder.isChecked ? onUnSaveToList(folder) : onSaveToList(folder)">
                            <div class="flex items-center">
                                <label>
                                    <input v-model="folder.isChecked" type="checkbox"/>
                                    &nbsp;{{ folder.title }}
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="sticky bottom-0 p-4 bg-white border-t">
            <div v-if="isShowAddRow" class="flex">
                <general-input
                    v-model="newFolderTitle"
                    :placeholder="$t('forms.namespace.titlePlaceholder')"
                    class="w-full mr-2"
                    v-on:pressEnter="onAddNewFolder"
                />
                <button class="button" v-on:click="onAddNewFolder"
                        v-text="$t('common.submit')"></button>
            </div>

            <div v-else class="buttons is-right">
                <button v-if="!isShowAddRow"
                        class="button mr-2"
                        v-on:click="isShowAddRow = !isShowAddRow">
                    {{ $t('collect.create') }}
                </button>
                <button class="button mr-2" v-on:click="close">
                    {{ $t('common.close') }}
                </button>
            </div>
        </div>
    </div>
</template>
<script>
import { debounce } from 'lodash';
import { openNotify } from '../../utils';
import GeneralInput from '../GeneralInput';

export default {
    components: { GeneralInput },
    props: {
        type: {
            type: Number,
            required: true,
        },
        id: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            folders: [],
            isShowAddRow: false,
            newFolderTitle: '',
        };
    },
    mounted() {
        this.axios.get('/favorite-folders-status', {
            params: { type: this.type, id: this.id },
        })
            .then(({ data: { data } }) => {
                this.folders = data.map((f) => ({
                    id: f.id,
                    title: f.title,
                    isChecked: f.isExistInTarget,
                }));
            });
    },
    methods: {
        onAddNewFolder() {
            if (this.newFolderTitle === '') {
                return;
            }

            this.axios.post('/favorite-folders', { title: this.newFolderTitle })
                .then(({ data: { data } }) => {
                    const folder = { id: data.id, title: data.title, isChecked: true };
                    this.folders.push(folder);
                    this.isShowAddRow = false;
                    this.newFolderTitle = '';
                    this.onSaveToList(folder);
                });
        },
        onSaveToList: debounce(function (folder) {
            this.axios
                .post(`/favorite-folders/${folder.id}/items`, {
                    type: this.type,
                    id: this.id,
                })
                .then(() => {
                    folder.isChecked = true;
                    openNotify(this.$t('common.saveSuccess'));
                });
        }),
        onUnSaveToList: debounce(function (folder) {
            this.axios.delete(`/favorite-folders/${folder.id}/items/${this.id}`, {
                params: {
                    type: this.type,
                    id: this.id,
                },
            }).then(() => {
                folder.isChecked = false;
            });
        }),
        close() {
            this.$store.commit('closeModal');
        },
    },
};
</script>
