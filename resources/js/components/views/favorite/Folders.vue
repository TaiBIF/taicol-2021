<template>
    <div class="px-4 py-2">
        <ul>
            <li class="flex items-center px-4 py-1 hover:bg-gray-100 cursor-pointer"
                v-on:click="() => onOpenFolder({ id : 0, title: $t('collect.meAdd') })">
                <i class="fas fa-folder cursor-pointer"></i>
                &nbsp;&nbsp;
                <span>{{ $t('collect.meAdd') }}</span>
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
    </div>
</template>
<script>
import GeneralInput from '../../GeneralInput.vue';
import { openNotify } from '../../../utils';

export default {
    data() {
        return {
            folders: [],
        };
    },
    mounted() {
        this.onRefresh();
    },
    methods: {
        onRefresh() {
            this.axios.get('/favorite-folders')
                .then(({ data: { data } }) => {
                    this.folders = data.map((f) => ({
                        id: f.id,
                        title: f.title,
                        isEdit: false,
                    }));
                });
        },
        onOpenFolder(folder) {
            this.$emit('set-folder', folder);
        },
        onEditFolder(folder) {
            this.axios.put(`/favorite-folders/${folder.id}`, { title: folder.title })
                .then(({ data: { data } }) => {
                    folder.isEdit = false;
                    openNotify(this.$t('common.saveSuccess'));
                });
        },
    },
    components: { GeneralInput },
};
</script>
