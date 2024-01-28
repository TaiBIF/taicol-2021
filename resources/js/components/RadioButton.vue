<template>
    <button :class="{'is-dark': value === v, 'is-small': isSmall}" class="button"
            v-on:click="onChangeValue">{{ label }}
    </button>
</template>
<script>
export default {
    props: {
        label: {
            type: String,
            required: true,
        },
        value: {
            type: [Number, String],
        },
        v: {
            type: [Number, String],
            required: true,
        },
        isSmall: {
            type: Boolean,
            default: () => false,
        },
    },
    methods: {
        onChangeValue() {
            this.$emit('input', this.v);
        },
    },
};
</script>
