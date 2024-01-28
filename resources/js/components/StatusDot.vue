<template>
    <div class="dot-container">
        <div :class="['dot', `dot--${status}`]"></div>
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@vue/composition-api';

export default defineComponent({
    props: {
        status: {
            type: String,
            required: true,
            validator: (value: string) => ['accepted', 'not-accepted', 'misapplied', 'undetermined'].includes(value),
        },
    },
});
</script>
<style lang="scss" scoped>
.dot-container {
    display: inline-block;
    text-align: center;
    width: 10px;

    .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-bottom: 1px;
    }

    .dot--accepted {
        @apply bg-green-400;
    }

    .dot--not-accepted {
        @apply bg-red-400;
    }

    .dot--misapplied {
        @apply bg-yellow-300;
    }

    .dot--undetermined {
        @apply bg-gray-300;
    }
}
</style>
