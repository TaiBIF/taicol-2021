<template>
    <transition-group class="layer-holder" name="slide-out" tag="div">
        <template v-for="(layer, index) in layers">
            <div :key="'overlay' + index" class="layer-overlay"></div>
            <div :key="'layer' + index"
                 :class="{last: isLastLayer(index)}"
                 class="layer">
                <component :is="layer.template"
                           :on-after-submit="layer.events ? layer.events.onAfterSubmit : null"
                           :presetData="layer.default"
                           :title="layer.title"
                           v-bind="layer.props"
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
import { mapGetters } from 'vuex';

export default {
    computed: {
        ...mapGetters({
            layers: 'layer/getItems',
        }),
    },
    methods: {
        isLastLayer(index) {
            return index === this.layers.length - 1;
        },
        closeLayer() {
            this.$store.commit('layer/CLOSE');
        },
    },
};
</script>
