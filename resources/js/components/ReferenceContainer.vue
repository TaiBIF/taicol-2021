<template>
    <div>
        <div class="box">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label is-marked">{{ $t('forms.taxonName.reference') }}</label>
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
                        <label class="label" v-text="$t('forms.taxonName.nameInReference')"/>
                        <general-input v-model="usage.nameInReference" :errors="errors['usageNameInReference']"/>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-4">
                    <div class="field">
                        <label class="label is-marked">{{ $t('forms.taxonName.showPage') }}</label>
                        <general-input v-model="usage.showPage" :errors="errors['usageShowPage']"/>
                    </div>
                </div>
                <div class="column is-2">
                    <div class="field">
                        <label class="label" v-text="$t('forms.taxonName.figure')"/>
                        <input v-model="usage.figure" :errors="errors['usageFigure']" class="input" type="text"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="box">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="label is-marked">{{ $t('forms.taxonName.reference') }}
                            <small>(若文獻尚未歸檔，請將文獻以「純文字」填於此欄)</small>
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
    import GeneralInput from './GeneralInput';
    import SimpleReferenceView from './views/SimpleReferenceView';
    import ReferenceSelect from './selects/ReferenceSelect';
    import { factory } from '../utils/preview/reference';

    export default {
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
            }
        },
        watch: {
            usage: {
                deep: true,
                handler(usage) {
                    const referenceName = factory(this.type)([usage]);
                    this.$emit('update:referenceName', referenceName);
                    this.referenceCustomName = referenceName;
                },
            },
            reference(value) {
                const usage = {
                    ... this.usage,
                    target: value,
                };
                this.$emit('update:usage', usage);
            },
            referenceCustomName(value) {
                this.$emit('update:referenceName', value);
            }
        },
        methods: {
            onAddNewReference(data) {
                this.$emit('update:usage', {
                    target: data,
                    ... this.usage
                });
            },
        },
        components: { ReferenceSelect, SimpleReferenceView, GeneralInput },
    }
</script>
