<template>
    <div class="flex gap-1">
        <span v-if="isInTaiwan === 1"
              class="c-tag">{{ $t('usage.inTaiwan') }}</span>
        <span v-if="isNewRecord === true"
              class="c-tag">{{ $t('usage.newRecord') }}</span>
        <span v-if="isEndemic"
              class="c-tag">{{ $t('usage.endemic') }}</span>

        <span v-if="alienType === 'native'"
              class="c-tag">{{ $t('usage.alienTypeOptions.native') }}</span>
        <span v-if="alienType === 'naturalized'"
              class="c-tag">{{ $t('usage.alienTypeOptions.naturalized') }}</span>
        <span v-if="alienType === 'invasive'"
              class="c-tag">{{ $t('usage.alienTypeOptions.invasive') }}</span>
        <span v-if="alienType === 'cultured'"
              class="c-tag">{{ $t('usage.alienTypeOptions.cultured') }}</span>

        <span v-if="isFossil"
              class="c-tag">{{ $t('usage.fossil') }}</span>
        <span v-if="isTerrestrial"
              class="c-tag">{{ $t('usage.terrestrial') }}</span>
        <span v-if="isFreshwater"
              class="c-tag">{{ $t('usage.freshWater') }}</span>
        <span v-if="isBrackish"
              class="c-tag">{{ $t('usage.brackish') }}</span>
        <span v-if="isMarine"
              class="c-tag">{{ $t('usage.marine') }}</span>
    </div>
</template>
<script lang="ts">
export default {
    name: 'UsagePropertyTags',
    props: {
        alienType: {
            type: String,
            default: '',
        },
        isInTaiwan: {
            type: [Boolean, Number],
            default: false,
        },
        isNewRecord: {
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
