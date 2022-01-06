<template>
    <b-pagination
        v-model="current"
        :icon-next="'chevron-right'"
        :icon-pack="'fas'"
        :icon-prev="'chevron-left'"
        :order="'is-centered'"
        :per-page="perPage"
        :range-after="rangeAfter"
        :range-before="rangeBefore"
        :size="'is-small'"
        :tag="'router-link'"
        :total="total"
        aria-current-label="Current page"
        aria-next-label="Next page"
        aria-page-label="Page"
        aria-previous-label="Previous page"
        v-on:change="onChange"
    />
</template>
<script>
    export default {
        props: {
            rangeBefore: {
                type: Number,
                default: 4,
            },
            rangeAfter: {
                type: Number,
                default: 4,
            },
            total: {
                type: Number,
                required: true,
            },
            currentPage: {
                type: Number,
                required: true,
            },
            perPage: {
                type: Number,
                required: true,
            },
        },
        data() {
            return {
                current: this.currentPage,
            }
        },
        methods: {
            onChange(v) {
                this.$emit('change', v);
            },
        },
    }
</script>
