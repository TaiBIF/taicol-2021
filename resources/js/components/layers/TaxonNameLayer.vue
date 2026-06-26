<template>
    <div class="layer-wrapper">
        <div class="layer-header">
            <button class="button is-text is-inline" v-on:click="close">
                <i class="fas fa-times"></i>
            </button>
            <p class="title is-inline" v-text="title"/>
        </div>
        <div class="layer-content box">
            <taxon-name-form
                ref="form"
                :errors="errors"
                :on-after-submit="onAfterFormSubmit"
                :on-continue-editing="onContinueEditing"
                :preset-data="presetData"/>
        </div>
        <div class="layer-footer">
            <button class="button"
                    :class="{ 'is-loading': isLoading }"
                    v-on:click="close"
                    v-text="$t('common.close')">
            </button>
            <button class="button"
                    :class="{ 'is-loading': isLoading }"
                    v-if="!isReferenceUsageEdit && !isPublished" 
                    v-on:click="onSubmit(false)"
                    v-text="$t('common.saveAsDraft')">
            </button>
            <button class="button"
                    :class="{ 'is-loading': isLoading }"
                    v-if="!isPublished"
                    v-on:click="onSubmit(true)"
                    v-text="$t('common.publish')">
            </button>
            <button class="button"
                    :class="{ 'is-loading': isLoading }"
                    v-if="isPublished"
                    v-on:click="onSubmit(true)"
                    v-text="$t('common.save')">
            </button>
        </div>
    </div>
</template>
<script>
import TaxonNameForm from '../forms/TaxonNameForm.vue';

export default {
    components: {
        TaxonNameForm,
    },
    props: {
        title: {
            type: String,
            default() {
                return this.$t('taxonName.create');
            },
        },
        presetData: {
            type: Object,
            default: null,
        },
        onAfterSubmit: {
            type: Function,
            required: true,
        },
    },
    data() {
        return {
            errors: {},
            isLoading: false
        };
    },
    computed: {
        isReferenceUsageEdit() {
            return this.$route.name.includes('reference-usages-list') || this.$route.name.includes('reference-usages-edit')
        },
        isPublished(){
            if (this.presetData){
                return this.presetData.isPublish ?? false
            } else {
                return false
            }
        },
    },
    methods: {
        close() {
            this.$emit('close');
        },
        onAfterFormSubmit(data) {
            this.isLoading = false;
            this.onAfterSubmit(data);
            this.close();
        },
        onSubmit(isPublish) {
            this.isLoading = true;
            const submitTask = this.$refs.form.submit(isPublish);

            // 加上安全判斷，接住被 throw 出來的失敗 Promise
            if (submitTask) {
                submitTask.catch(() => {
                    this.isLoading = false;
                });
            }
        },
        onContinueEditing() {
            this.isLoading = false;
        },
    },
};
</script>
<style lang="scss" scoped>
.layer-wrapper {
    height: 100%;

    .layer-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: white;
        padding: 1.5rem;
        height: 5.5rem;
    }

    .layer-content {
        overflow-x: hidden;
        height: calc(100% - 11rem);
        position: relative;
    }

    .layer-footer {
        position: fixed;
        bottom: 0;
        height: 5.5rem;
        width: 100%;
        padding: 1.5rem;
    }
}
</style>
