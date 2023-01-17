<template>
    <div class="flex gap-1">
        <span v-if="isInTaiwan"
              class="c-tag">存在於臺灣</span>
        <span v-if="isEndemic"
              class="c-tag">臺灣特有</span>

        <span v-if="alienType === 'native'"
              class="c-tag">原生</span>
        <span v-if="alienType === 'naturalized'"
              class="c-tag">歸化</span>
        <span v-if="alienType === 'invasive'"
              class="c-tag">入侵</span>
        <span v-if="alienType === 'cultured'"
              class="c-tag">栽培豢養</span>

        <span v-if="isFossil"
              class="c-tag">化石種</span>
        <span v-if="isTerrestrial"
              class="c-tag">陸生</span>
        <span v-if="isFreshwater"
              class="c-tag">淡水</span>
        <span v-if="isBrackish"
              class="c-tag">半鹹水域</span>
        <span v-if="isMarine"
              class="c-tag">海洋</span>
    </div>
</template>
<script lang="ts">
export default {
    name: 'UsagePropertyTags',
    props: {
        alienType: {
            type: String,
            default: false,
        },
        isInTaiwan: {
            type: [Boolean, Number],
            default: false,
        },
        isEndemic: {
            type: [Boolean, Number],
            default: false,
        },
        isFossil: {
            type: [Boolean, Number],
            default: false,
        },
        isTerrestrial: {
            type: [Boolean, Number],
            default: false,
        },
        isFreshwater: {
            type: [Boolean, Number],
            default: false,
        },
        isBrackish: {
            type: [Boolean, Number],
            default: false,
        },
        isMarine: {
            type: [Boolean, Number],
            default: false,
        },
    },
};
</script>
<style lang="scss" scoped>
.c-tag {
    @apply bg-gray-50 px-3 ml-2;
}
</style>
