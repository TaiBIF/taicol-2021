<template>
    <div :class="{
        'is-danger': hasErrors,
    }"
         class="box is-simple"
    >
        <div class="buttons is-pulled-right">
            <a class="g-button"
               v-on:click="onCollapsePerUsage">
                <i class="fas fa-pen"></i>
            </a>
            <a class="close-button"
               v-on:click="onRemovePerUsage">
            </a>
        </div>
        <per-usage-view :per-usage="perUsage" :taxon-name="taxonName"/>
    </div>
</template>
<script>
import PerUsageView from '../views/PerUsageView.vue';

export default {
    computed: {
        hasErrors() {
            return !!Object
                .keys(this.errors)
                .find((i) => !!i.match(`perUsages${this.index}`));
        },
    },
    props: {
        index: {
            type: Number,
            required: true,
        },
        taxonName: {
            type: Object,
            required: true,
        },
        perUsage: {
            type: Object,
            required: true,
        },
        errors: {
            type: Object,
        },
        onRemovePerUsage: {
            type: Function,
            required: true,
        },
        onCollapsePerUsage: {
            type: Function,
            required: true,
        },
    },
    components: { PerUsageView },
};
</script>
