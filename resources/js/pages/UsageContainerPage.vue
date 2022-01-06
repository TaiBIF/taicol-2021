<template>
    <div class="container is-wide">
        <div v-if="$store.getters['breadcrumb/getItems'].length">
            <breadcrumb></breadcrumb>
        </div>
        <div class="flex flex-row">
            <div class="w-2/5">
                <div class="panel flex items-center mt-12">
                    <my-favorite-panel />
                </div>
            </div>
            <div class="w-full">
                <router-view />
            </div>
        </div>
    </div>
</template>

<script>
    import Breadcrumb from "../components/Breadcrumb";
    import MyFavoritePanel from "../components/MyFavoritePanel";
    import PerUsageDraggable from './../components/draggable/PerUsageDraggable';

    export default {
        data() {
            return {
                showMyFavorite: true,
            }
        },
        created() {
            const { id } = this.$route.params;
            this.axios
                .get(`/namespaces/${id}`)
                .then(({ data }) => {
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
                });
        },
        destroyed() {
            this.$store.commit('breadcrumb/CLEAR_ITEMS');
        },
        components: { Breadcrumb, PerUsageDraggable, MyFavoritePanel },
    }
</script>

<style lang="scss" scoped>
    .panel {
        height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height} - 4rem);
    }
</style>
