<template>
    <div class="inline-block" v-on:mouseenter="isShow = true"
         v-on:mouseleave="isShow = false">
        <slot></slot>
        <div v-if="isShow" class="tooltip">
            <slot name="body"></slot>
        </div>
    </div>
</template>
<script lang="ts">

import { defineComponent, ref } from '@vue/composition-api';

export default defineComponent({
    name: 'Tooltip',
    props: {
    },
    setup() {
        const isShow = ref<boolean>();

        return {
            isShow,
        };
    },
});
</script>
