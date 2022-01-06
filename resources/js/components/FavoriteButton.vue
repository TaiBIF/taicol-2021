<template>
    <div class="inline">
        <a class="g-button is-small text-base"
           v-on:click="e => onAddToFavorite(e)">
            <i class="fas fa-plus"></i>
        </a>
    </div>
</template>
<script>
    export default {
        props: {
            type: {
                type: Number,
                required: true,
            },
            id: {
                type: Number,
                required: true,
            }
        },
        methods: {
            onAddToFavorite() {
                this.$store.commit('openModal', {
                    component: () => import('./modals/FavoriteModel'),
                    props: {
                        id: this.id,
                        type: this.type,
                    }
                });
            }
        }
    }
</script>
<style lang="scss">

</style>
