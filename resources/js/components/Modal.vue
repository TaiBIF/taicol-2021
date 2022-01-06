<template>
    <div :class="{'is-active': $store.state.modal.isActive}" class="modal">
        <div class="modal-background"></div>
        <div class="modal-content">
            <div class="shadow-md bg-white min-h-12 relative">
                <button aria-label="close" class="modal-close is-large sticky top-5 float-right" v-on:click="close"></button>
                <p class="title" v-if="$store.state.modal.title" v-text="$store.state.modal.title"/>
                <component :is="$store.state.modal.component" v-bind="$store.state.modal.props"/>
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
    }
</script>
<style lang="scss" scoped>
    .modal {
        z-index: 101;
        .modal-content {
            max-width: 80vw;
            max-height: 80vh;
            width: auto;

            .min-h-12 {
                min-height: 60vh;
            }

            .modal-close {
                z-index: 10;
                &:after, &:before {
                    background-color: $black;
                }
            }
        }
    }
</style>
