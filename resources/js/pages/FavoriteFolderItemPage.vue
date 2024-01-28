<template>
    <div class="container pt-6">
        <div class="shadow-md bg-white w-full h-full overflow-y-scroll">
            <div class="border-b py-3 px-4 sticky top-0 bg-white z-50">
                <p class="has-text-weight-bold is-inline is-size-6">
                    <span>{{ $t('collect.myCollections') }}</span>

                </p>

                <div v-if="targetFolder" class="is-pulled-right">
                    <router-link :to="{name: 'favorite-folder-list'}">
                        <i class="fas fa-folder cursor-pointer fa-home"></i>
                    </router-link>&nbsp;&nbsp;>&nbsp;&nbsp;
                    <i class="fas fa-folder cursor-pointer fa-folder-open"></i>
                    <span>{{ targetFolder.title }}</span>
                </div>
            </div>
            <folder-edit v-if="targetFolder" :folder="targetFolder" class="z-10" v-on:go-folders="onCloseFolder"/>
        </div>
    </div>
</template>
<script>
import GeneralInput from '../components/GeneralInput.vue';
import Folders from '../components/views/favorite/Folders.vue';
import FolderEdit from '../components/views/favorite/FolderEdit.vue';

export default {
    components: { FolderEdit, Folders, GeneralInput },
    data() {
        return {
            targetFolder: null,
        };
    },
    mounted() {
        this.onFetchFolder();
    },
    methods: {
        onFetchFolder() {
            const folderId = parseInt(this.$route.params.id, 10);
            if (folderId !== 0) {
                this.axios.get(`/favorite-folders/${folderId}`)
                    .then(({ data: { data } }) => {
                        this.targetFolder = data;
                    });
            } else {
                this.targetFolder = {
                    id: 0,
                    title: this.$t('collect.meAdd'),
                };
            }
        },
        onCloseFolder() {
            this.targetFolder = null;
        },
    },
};
</script>
<style lang="scss" scoped>
.container {
    height: calc(100vh - #{$navbar-height} - 1.5rem);
}

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
