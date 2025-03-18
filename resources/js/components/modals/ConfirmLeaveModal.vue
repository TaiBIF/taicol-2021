<template>
    <div>
        <div class="px-16 py-12 w-[400px] text-center">
            <p>
                {{ $t('usage.pleaseFinishEditing') }}
            </p>
            <br>
            <p>
            1. 有效學名必須點進學名卡片勾選「存在於臺灣？」
            <br>
            2. 若有同模式學名/異名出現在不同分類群當中，請確認是否要加入「部分引用」相關標註，或確認是否有同名（homonym）的情形。
            </p>

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
