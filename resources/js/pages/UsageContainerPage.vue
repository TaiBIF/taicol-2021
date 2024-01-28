<template>
    <div class="container is-wide">
        <div v-if="$store.getters['breadcrumb/getItems'].length">
            <breadcrumb></breadcrumb>
        </div>
        <div :class="{'flex-row': showMyFavorite}" class="flex">
            <div v-if="showMyFavorite" class="w-2/5">
                <div class="panel flex items-center mt-12">
                    <my-favorite-panel/>
                </div>
            </div>
            <div class="w-full">
                <router-view/>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, inject, ref } from '@vue/composition-api';
import Breadcrumb from '../components/Breadcrumb.vue';
import MyFavoritePanel from '../components/MyFavoritePanel.vue';
import PerUsageDraggable from '../components/draggable/PerUsageDraggable.vue';
import { truncate } from '../utils';

export default defineComponent({
    setup(props, context) {
        const app: any = context.root;
        const axios: any = inject('axios');

        const { id } = app.$route.params;

        const showMyFavorite = ref<boolean>(true);

        const showNamespaceUsage = () => {
            showMyFavorite.value = true;
            axios
                .get(`/namespaces/${id}`)
                .then(({ data }) => {
                    app.$store.commit('breadcrumb/SET_ITEMS', [
                        {
                            url: '/namespaces',
                            name: app.$t('namespace.myNamespaces'),
                            to: { name: 'namespace-list' },
                        },
                        {
                            url: `/namespaces/${id}/usages`,
                            name: data.title,
                            to: { name: 'namespace-usage-list', params: { id } },
                        },
                    ]);
                });
        };

        const showReferenceUsage = () => {
            showMyFavorite.value = false;
            axios
                .get(`/references/${id}`)
                .then(({ data: { data } }) => {
                    const items = [
                        {
                            url: `/reference/${id}`,
                            name: truncate(data.title, 80, true),
                            to: { name: 'reference-page', params: { id } },
                        },
                    ];

                    app.$store.commit('breadcrumb/SET_ITEMS', items);
                });
        };

        if (app.$route.meta.type === 'namespace') {
            showNamespaceUsage();
        } else {
            showReferenceUsage();
        }

        return {
            showMyFavorite,
        };
    },
    beforeDestroy() {
        const store: any = this.$store;
        store.commit('breadcrumb/CLEAR_ITEMS');
    },
    components: { Breadcrumb, PerUsageDraggable, MyFavoritePanel },
});
</script>
<style lang="scss" scoped>
.panel {
    height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height} - 4rem);
}
</style>
