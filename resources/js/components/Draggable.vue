<template>
    <draggable
        v-bind="dragOptions"
        :list="list"
        :move="onMove"
        :value="value"
        class="item-container"
        data-type="main"
        ghost-class="ghost"
        tag="div"
        @input="emitter"
    >
        <div class="usage-row"
             :class="{'is-title': el.isTitle}"
             v-for="(el,index) in realValue" :key="`usage_${index}`">
            <div class="usage-content">
                <span class="utitle">{{ el.taxonName.name }}</span>
                <span class="usubtitle">{{ el.taxonName.authors }}</span>
            </div>

            <button class="button is-pulled-right is-small is-text"
                    v-on:click="onToggleTitle(index)">
                標題
            </button>

            <button class="button is-pulled-right is-small is-text"
                    v-if="!el.title"
                    v-on:click="goUsage(el.id)">
                <i class="fas fa-pen"></i>
            </button>

            <button class="button is-pulled-right is-small is-text"
                    v-on:click="onRemove(el)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </draggable>
</template>

<script>
    import draggable from 'vuedraggable';

    export default {
        name: 'nested-draggable',
        methods: {
            emitter(value) {
                this.$emit('input', value);
            },

            onToggleTitle(index) {
                this.$emit('toggleTitle', index);
            }
        },
        computed: {
            dragOptions() {
                return {
                    animation: 0,
                    group: 'description',
                    disabled: false,
                    ghostClass: 'ghost',
                    threshold: 0,
                };
            },
            realValue() {
                return this.value ? this.value : this.list;
            },
        },
        props: {
            value: {
                required: false,
                type: Array,
                default: null,
            },
            list: {
                required: false,
                type: Array,
                default: null,
            },
        },
        components: {
            draggable,
        },
    };
</script>
<style lang="scss" scoped>



    .title {
        margin-left: 1rem;
    }


</style>
