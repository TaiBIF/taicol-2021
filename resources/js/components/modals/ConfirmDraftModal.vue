<template>
    <div>
        <div class="px-16 py-12 w-[400px] text-center">
            <p>
                該筆資料已被建立為草稿，請到我的收藏裡的草稿確認並發布，若該筆不是您建立的草稿，還請聯絡管理員釐清。(catalogueoflife.taiwan@gmail.com)
            </p>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button is-success" v-on:click="onClose">{{ $t('common.continueEditing') }}</button>
        </div>
    </div>
</template>
<script lang="ts">
import {
    defineComponent, PropType,
} from '@vue/composition-api';

export default defineComponent({
    name: 'confirm-draft-modal',
    props: {
        onContinueEditing: {
            type: Function as PropType<() => void>,
            default: null,
        },
    },
    destroyed() { // 只要這個 Modal 被關閉/銷毀，一定會執行這裡
        if (this.onContinueEditing) {
            this.onContinueEditing(); 
        }
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
