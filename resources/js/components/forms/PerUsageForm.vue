<template>
    <div class="box">
        <div class="buttons is-right">
            <a class=" g-button"
               v-on:click="onCollapsePerUsage">
                <i class="fas fa-minus"></i>
            </a>
            <a class="close-button"
               v-on:click="onRemovePerUsage">
            </a>
        </div>
        <div class="columns is-small">
            <div class="column is-8">
                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label is-marked">
                                文獻
                                <label class="label is-pulled-right">
                                    <input id="proParte" v-model="perUsage.proParte"
                                           class="checkbox"
                                           type="checkbox"/>
                                    <span v-text="'部分引用/排除'"/>
                                </label>
                            </label>
                            <reference-select
                                v-model="perUsage.target"
                                :errors="errors[`perUsages${index}ReferenceId`]"/>
                            <div v-if="perUsage.target" class="box">
                                <simple-reference-view v-bind="perUsage.target"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column is-6">
                        <div class="field">
                            <label class="label">學名出現頁碼</label>
                            <general-input v-model="perUsage.showPage"
                                           :errors="errors[`perUsages${index}ShowPage`]"/>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="field">
                            <label class="label">圖號</label>
                            <general-input v-model="perUsage.figure"
                                           :errors="errors[`perUsages${index}Figure`]"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="field">
                    <label class="label">文獻中學名寫法</label>
                    <general-input v-model="perUsage.nameInReference"
                                   :errors="errors[`perUsages${index}.customNameRemark`]"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import GeneralInput from '../GeneralInput';
    import ReferenceSelect from '../selects/ReferenceSelect';
    import SimpleReferenceView from '../views/SimpleReferenceView';

    export default {
        props: {
            index: {
                type: Number,
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
        components: { SimpleReferenceView, ReferenceSelect, GeneralInput },
    }
</script>
