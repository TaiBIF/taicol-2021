<template>
    <div>
        <div v-for="(modal, idx) in $store.state.modals"
             :key="idx"
             class="modal is-active"
             :style="{ zIndex: modal.zIndex }">
            <div class="modal-background"></div>
            <div class="modal-content">
                <div class="shadow-md bg-white min-h-6 relative">
                    <button aria-label="close" class="modal-close is-large sticky top-5 float-right"
                            v-on:click="close"></button>
                    <p v-if="modal.title" class="title" v-text="modal.title"/>
                    <component :is="modal.component" v-bind="modal.props"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    methods: {
        close() {
            this.$store.commit('closeModal');
        },
    },
};
</script>
<style lang="scss" scoped>
.modal {
    .modal-content {
        max-width: 80vw;
        max-height: 80vh;
        width: auto;
        .min-h-12 { min-height: 60vh; }
        .modal-close {
            z-index: 10;
            &:after, &:before { background-color: $black; }
        }
    }
}
</style>