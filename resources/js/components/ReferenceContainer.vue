<template>
    <div>
        <p class="subtitle mb-3"> • {{ $t('taxonName.linkToReference') }}</p>
        <div class="box">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label">{{ $t('taxonName.publication') }}</label>
                        <reference-select
                            v-model="reference"
                            :after-create="onAddNewReference"
                            :errors="errors['usageReferenceId']"
                        />
                        <div v-if="usage.target" class="box">
                            <simple-reference-view v-bind="usage.target"/>
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field">
                        <label class="label" v-text="$t('taxonName.nameInReference')"/>
                        <general-input v-model="usage.nameInReference" :errors="errors['usageNameInReference']"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-4">
                    <div class="field">
                        <label :class="{'is-marked': !!reference}" class="label">
                            {{ $t('reference.showPage') }}
                        </label>
                        <general-input v-model="usage.showPage" :errors="errors['usageShowPage']"/>
                    </div>
                </div>
                <div class="column is-2">
                    <div class="field">
                        <label class="label" v-text="$t('reference.figure')"/>
                        <input v-model="usage.figure" :errors="errors['usageFigure']" class="input" type="text"/>
                    </div>
                </div>
            </div>
        </div>
        <p class="subtitle mb-3"> • {{ $t('taxonName.saveAsPlainText') }}</p>
        <div class="box">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label">{{ $t('taxonName.publication') }}
                            <small>{{ $t('taxonName.referenceNotApplyInfo') }}</small>
                        </label>
                        <general-input v-model="referenceCustomName"
                                       :disabled="(!!usage.target)"
                                       :errors="errors.referenceName"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import GeneralInput from './GeneralInput.vue';
import SimpleReferenceView from './views/SimpleReferenceView.vue';
import ReferenceSelect from './selects/ReferenceSelect.vue';
import { subTitle } from '../utils/preview/reference';

export default {
    name: 'reference-container',
    props: {
        usage: {
            type: Object,
            required: true,
        },
        referenceName: {
            type: String,
            required: true,
        },
        type: {
            type: String,
            required: true,
        },
        errors: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            reference: this.usage?.target,
            referenceCustomName: this.referenceName,
        };
    },
    watch: {
        usage: {
            deep: true,
            handler(usage) {
                const newUsage = usage;
                newUsage.target.properties.pagesRange = '';
                const referenceName = [subTitle(newUsage.target), usage.showPage].filter(Boolean).join(': ');
                this.$emit('update:referenceName', referenceName);
                this.referenceCustomName = referenceName;
            },
        },
        reference(value) {
            const usage = {
                ...this.usage,
                target: value,
            };
            this.$emit('update:usage', usage);
        },
        referenceCustomName(value) {
            this.$emit('update:referenceName', value);
        },
    },
    methods: {
        onAddNewReference(data) {
            this.$emit('update:usage', {
                target: data,
                ...this.usage,
            });
        },
    },
    components: { ReferenceSelect, SimpleReferenceView, GeneralInput },
};
</script>
