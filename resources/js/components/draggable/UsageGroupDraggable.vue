<template>
    <draggable v-bind="dragOptions" data-type="per-usage" tag="div">
        <template v-for="perUsages in usageGroup">
            <template v-for="(perUsage, index) in perUsages">

            </template>
        </template>
    </draggable>
</template>

<script>
    import draggable from 'vuedraggable';
    import PerUsageSimpleCard from '../cards/PerUsageSimpleCard';

    export default {
        data() {
            return {
                dragOptions: {
                    animation: 0,
                    group: 'description',
                    disabled: false,
                    threshold: 0,
                    multiDrag: false,
                    selectedClass: 'selected',
                },
                usageGroup: [],
            }
        },
        components: {
            PerUsageSimpleCard,
            draggable,
        },
    }
</script>
