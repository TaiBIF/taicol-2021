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
                                {{ $t('common.reference') }}
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
            </div>
            <div class="column">
                <div class="columns">
                    <div class="field">
                        <label class="label">{{ $t('reference.showPage') }}</label>
                        <general-input v-model="perUsage.showPage"
                                        :errors="errors[`perUsages${index}ShowPage`]"/>
                    </div>
                </div>
                <div class="columns">
                    <div class="field">
                        <label class="label label-margin">{{ $t('reference.figure') }}</label>
                        <general-input v-model="perUsage.figure"
                                        :errors="errors[`perUsages${index}Figure`]"/>
                    </div>
                </div>
                <div class="columns">
                    <div class="field">
                        <label class="label label-margin">{{ $t('taxonName.nameInReference') }}</label>
                        <general-input v-model="perUsage.nameInReference"
                                    :errors="errors[`perUsages${index}.customNameRemark`]"/>
                    </div>
                </div>
                <div class="columns">
                    <div class="field">
                        <label class="label label-margin">{{ $t('usage.proParteNote') }}</label>
                        <pro-parte-select v-model="perUsage.proParteType"/>
                        <div>
                            <label class="label label-margin" :class="{ 'is-marked': requiresInput }">{{ $t('usage.proParteTextNote') }}</label>
                            <general-input 
                                v-model="perUsage.proParteText"
                                type="text"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from '../GeneralInput.vue';
import ReferenceSelect from '../selects/ReferenceSelect.vue';
import ProParteSelect from '../selects/ProParteSelect.vue';
import SimpleReferenceView from '../views/SimpleReferenceView.vue';

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
    watch: {
        perUsage: {
            deep: true,
            handler(value) {
                if (value.proParteType != '' && value.proParteType != null){
                    this.perUsage.proParte = true;
                } else {
                    this.perUsage.proParte = false;
                }
            }
        }
    },
    computed: {
        requiresInput() {

            if(this.perUsage?.proParteType)
            // 必填
            return this.perUsage.proParteType=='excl. ＿＿' || this.perUsage.proParteType=='quoad ＿＿';
        }
    },
    components: { SimpleReferenceView, ReferenceSelect, GeneralInput, ProParteSelect },
};
</script>
<style lang="scss">
.label-margin {
    margin-top: 0.5rem;
}
</style>
