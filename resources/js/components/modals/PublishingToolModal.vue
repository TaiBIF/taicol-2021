<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="title text-center">{{ $t('namespace.publishingTool') }}</p>
                <p>
                    {{ $t('namespace.publishingToolInfo') }}
                </p>
            </div>

            <!-- <div class="min-h-3/5 w-full">
            </div> -->
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button" v-on:click="onClose">{{ $t('common.cancel') }}</button>
            <a class="button" 
            :href="`https://biota.biodiv.tw/publications?namespace_id=${namespaceId || ''}`" 
            target="_blank">
                {{ $t('common.submit') }}
            </a>        
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, PropType, ref } from '@vue/composition-api';
import GeneralInput from '../GeneralInput.vue';
import DeselectableRadioButton from '../DeselectableRadioButton.vue';

export default defineComponent({
    name: 'usage-property-modal',
    props: {
        namespaceId: {
            type: [String, Number],
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
    components: { DeselectableRadioButton, GeneralInput },
});
</script>
