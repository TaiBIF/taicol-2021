<template>
    <div class="container pt-6">
        <div class="shadow-md bg-white w-full h-full">
            <div class="border-b py-3 px-4">
                <p class="has-text-weight-bold is-inline is-size-6">
                    <span>收藏參考區</span>
                </p>

                <div v-if="targetFolder" class="is-pulled-right">
                    <router-link :to="`/favorite-folders`"> <i class="fas fa-folder cursor-pointer fa-home" ></i></router-link>&nbsp;&nbsp;>&nbsp;&nbsp;
                    <i class="fas fa-folder cursor-pointer fa-folder-open"></i>
                    <span>{{ targetFolder.title }}</span>
                </div>
            </div>
            <folder-edit v-if="targetFolder" :folder="targetFolder" v-on:go-folders="onCloseFolder" />
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
            targetFolder: null,
        }
    },
    mounted() {
        this.onFetchFolder();
    },
    methods: {
        onFetchFolder() {
            const folderId = parseInt(this.$route.params.id);
            if (folderId !== 0) {
                this.axios.get(`/favorite-folders/${ folderId }`)
                    .then(({data: {data}}) => {
                        this.targetFolder = data;
                    });
            } else {
                this.targetFolder = {
                    id: 0,
                    title: '我建立的學名/文獻',
                }
            }

        },
        onCloseFolder() {
            this.targetFolder = null;
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
