<template>
    <div tabindex="-1" class="sort-button " v-on:click="onClickSort">
        <slot></slot>
        <i v-if="sortby !== id" class="fas fa-sort"></i>
        <i v-if="direction === 'asc' && sortby === id" class="fas fa-arrow-down"></i>
        <i v-else-if="direction === 'desc' && sortby === id" class="fas fa-arrow-up"></i>
    </div>
</template>
<script>
    export default {
        props: {
            sortby: {
                type: String,
                required: true,
            },
            id: {
                type: String,
                required: true,
            },
            onClick: {
                type: Function,
                required: true,
            },
            direction: {
                type: String,
                required: true,
            }
        },
        methods: {
            onClickSort() {
                this.onClick(this.id, this.direction === 'asc' ? 'desc' : 'asc');
            }
        }
    }
</script>
<style lang="scss" scoped>
    .sort-button {
        cursor: pointer;
        outline: none;
        box-shadow: none;
    }
</style>
