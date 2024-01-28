<template>
    <div class="tags">
        <span v-if="p.isInTaiwan === 1 || p.isInTaiwan === true" class="tag">{{
                $t('usage.alienTypeShortTag.isInTaiwan')
            }}</span>
        <span v-else-if="p.isInTaiwan === 0 || p.isInTaiwan === false"
              class="tag">{{ $t('usage.alienTypeShortTag.nonIsInTaiwan') }}</span>

        <span v-if="p.isEndemic" class="tag">{{ $t('usage.alienTypeShortTag.endemic') }}</span>

        <span v-if="p.alienType === 'native'" class="tag">{{ $t('usage.alienTypeShortTag.native') }}</span>
        <span v-if="p.alienType === 'naturalized'" class="tag">{{ $t('usage.alienTypeShortTag.naturalized') }}</span>
        <span v-if="p.alienType === 'invasive'" class="tag">{{ $t('usage.alienTypeShortTag.invasive') }}</span>
        <span v-if="p.alienType === 'cultured'" class="tag">{{ $t('usage.alienTypeShortTag.cultured') }}</span>

        <span v-if="p.isFossil" class="tag">{{ $t('usage.alienTypeShortTag.fossil') }}</span>
        <span v-if="p.isTerrestrial" class="tag">{{ $t('usage.alienTypeShortTag.terrestrial') }}</span>
        <span v-if="p.isFreshwater" class="tag">{{ $t('usage.alienTypeShortTag.freshWater') }}</span>
        <span v-if="p.isBrackish" class="tag">{{ $t('usage.alienTypeShortTag.brackish') }}</span>
        <span v-if="p.isMarine" class="tag">{{ $t('usage.alienTypeShortTag.marine') }}</span>
    </div>
</template>
<script>
import { defineComponent } from '@vue/composition-api';

export default defineComponent({
    props: {
        p: {
            type: Object,
            required: true,
        },
    },
});
</script>

<style lang="scss">
.tag {
    border: 1px solid #ccc;
    @apply px-1;
}
</style>
