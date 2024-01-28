<template>
    <div class="h-full w-full">
        <div class="shadow-md bg-white w-full h-full">
            <div class="border-b py-3 px-4">
                <p class="has-text-weight-bold is-inline is-size-6">
                    <span>{{ $t('collect.myCollections') }}</span>
                </p>

                <div v-if="targetFolder" class="is-pulled-right">
                    <i class="fas fa-folder cursor-pointer fa-home" v-on:click="onCloseFolder"></i>&nbsp;&nbsp;>&nbsp;&nbsp;
                    <i class="fas fa-folder cursor-pointer fa-folder-open"></i>
                    <span>{{ targetFolder.title }}</span>
                </div>
            </div>
            <folders v-if="!targetFolder" v-on:set-folder="onOpenFolder"/>
            <folder v-else :folder="targetFolder" v-on:go-folders="onCloseFolder"/>
        </div>
    </div>
</template>
<script>
import { openNotify } from '../utils';
import Folder from './views/favorite/Folder';
import Folders from './views/favorite/Folders';

export default {
    data() {
        return {
            targetFolder: null,
            newFolderTitle: '',
            isShowAddRow: false,
        };
    },
    methods: {
        onEditFolder(folder) {
            this.axios.put(`/favorite-folders/${folder.id}`, { title: folder.title })
                .then(({ data: { data } }) => {
                    openNotify(this.$t('common.saveSuccess'));
                });
        },
        onChangeSort() {

        },
        onCloseFolder() {
            this.targetFolder = null;
        },
        onOpenFolder(folder) {
            this.targetFolder = folder;
        },
    },
    components: { Folders, Folder },
};
</script>
