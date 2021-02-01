<template>
    <transition-group class="layer-holder" name="slide-out" tag="div">
        <template v-for="(layer, index) in $store.state.layers">
            <div :key="'overlay' + index" class="layer-overlay"></div>
            <div :class="{last: isLastLayer(index)}"
                 :key="'layer' + index"
                 class="layer">
                <component :is="layer.template"
                           :presetData="layer.default || {}"
                           :on-after-submit="layer.events.onAfterSubmit"
                           v-on:close="closeLayer"
                />
            </div>
        </template>
    </transition-group>
</template>
<style lang="scss" scoped>
    .slide-out-enter-active, .slide-out-leave-active {
        transition: all .4s;

        &.last {
            transition-delay: .1s;
        }
    }

    .slide-out-enter {
        &.last {
            transform: translate(100vw, 0);
        }
    }

    .slide-out-enter-to {
        &.last {
            transform: translate(20vw, 0);
        }
    }

    .slide-out-leave-to {
        &.last {
            transform: translate(100vw, 0);
        }
    }
</style>
<script>
    export default {
        methods: {
            isLastLayer(index) {
                return index === this.$store.state.layers.length - 1;
            },
            closeLayer() {
                this.$store.state.layers.pop();
            },
        },
    }
</script>
