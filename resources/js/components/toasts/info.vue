<template>
    <div :class="{danger: type === 'is-danger'}" class="bg-green-500 py-2 px-8">
        {{ message }}
    </div>
</template>
<script lang="ts">

import { defineComponent } from '@vue/composition-api';

export default defineComponent({
    name: 'Info-Toast',
    props: {
        message: {
            type: String,
        },
        type: {
            type: String,
            default: 'is-success',
        },
    },
});
</script>

<style lang="scss">
.danger {
    @apply bg-red-600;
}
</style>
