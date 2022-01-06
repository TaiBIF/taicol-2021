<template>
    <div class="container pt-2">
        <div class="buttons is-right mb-2">
            <button class="button" v-on:click="isShowAddRow = !isShowAddRow">建立收藏夾</button>
        </div>
        <div class="shadow-md bg-white w-full h-full overflow-y-scroll">
            <div class="border-b py-3 px-4 sticky top-0 bg-white z-10">
                <p class="has-text-weight-bold is-inline is-size-6">
                    <span>收藏參考區</span>
                </p>
            </div>
            <ul class="z-0">
                <li class="flex items-center px-4 py-1 hover:bg-gray-100 cursor-pointer"
                     v-on:click="() => onOpenFolder(0)">
                    <i class="fas fa-folder cursor-pointer"></i>
                    &nbsp;&nbsp;
                    <span>我建立的學名/文獻</span>
                </li>
                <li v-for="folder in folders">
                    <div class="flex items-center px-4 py-1 hover:bg-gray-100 cursor-pointer"
                         v-on:click="() => onOpenFolder(folder)">
                        <i class="fas fa-folder cursor-pointer"></i>
                        &nbsp;&nbsp;
                        <span>{{ folder.title }}</span>
                    </div>
                </li>
            </ul>
            <div class="sticky bottom-0 p-4 bg-white border-t" v-if="isShowAddRow">
                <div class="flex">
                    <general-input
                        class="w-full mr-2"
                        :placeholder="$t('forms.namespace.titlePlaceholder')"
                        v-model="newFolderTitle"
                        v-on:pressEnter="onAddNewFolder"
                    />
                    <button class="button" v-on:click="onAddNewFolder"
                            v-text="$t('forms.actions.submit')"></button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from '../components/GeneralInput';
import Folders from "../components/views/favorite/Folders";
import FolderEdit from "../components/views/favorite/FolderEdit";

export default {
    components: {FolderEdit, Folders, GeneralInput },
    data() {
        return {
            isShowAddRow: false,
            newFolderTitle: '',
            folders: [],
        };
    },
    mounted() {
        this.onRefresh();
    },
    methods: {
        onAddNewFolder() {
            if (this.newFolderTitle === '') {
                return;
            }

            this.axios.post(`/favorite-folders`, {title: this.newFolderTitle})
                .then(({data: {data}}) => {
                    const folder = {id: data.id, title: data.title, isChecked: true};
                    this.folders.push(folder);
                    this.isShowAddRow = false;
                    this.newFolderTitle = '';
                });
        },
        onRefresh() {
            this.axios.get(`/favorite-folders`)
                .then(({data: {data}}) => {
                    this.folders = data.map((f) => {
                        return {
                            id: f.id,
                            title: f.title,
                            isEdit: false,
                        }
                    });
                });
        },
        onOpenFolder(folder) {
            this.targetFolder = folder;
            this.$router.push(`/favorite-folders/${folder.id ?? 0}`);
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
.container {
    height: calc(100vh - #{$navbar-height} - 5rem);
}
</style>
