<template>
    <div>
        <div class="px-16 pt-6 w-[400px] text-center">
            <p>
                {{ $t('usage.pleaseFinishEditing') }}
            </p>
        </div>
        <div class="px-6 py-4 w-[400px]">
            {{ $t('usage.validateTips') }}
            <ol class="px-6">
                <li>{{ $t('usage.validateTip1') }}</li>
                <li>{{ $t('usage.validateTip2') }}</li>
                <li>{{ $t('usage.validateTip3') }}</li>
            </ol>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button" v-on:click="onLeave">{{ $t('common.later') }}</button>
            <button class="button is-success" v-on:click="onClose">{{ $t('common.continueEditing') }}</button>
        </div>
    </div>
</template>
<script lang="ts">
import {
    defineComponent, PropType,
} from '@vue/composition-api';

export default defineComponent({
    name: 'confirm-leave-modal',
    props: {
        onLeave: {
            type: Function as PropType<(data) => void>,
            required: true,
        },
    },
    setup(props, context) {
        const app: any = context.root;

        const onClose = () => {
            app.$store.commit('closeModal');
        };

        return {
            onClose,
        };
    },
});
</script>
<style lang="scss" scoped>
ol {
  list-style-type: decimal !important;
}

</style>