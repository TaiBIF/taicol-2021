<template>
    <div class="layer-wrapper">
        <div class="layer-header">
            <button class="button is-text is-inline" v-on:click="close">
                <i class="fas fa-times"></i>
            </button>
            <p class="title is-inline" v-text="$t('reference.create')"/>            
            <br>
                <button class="button button-margin"
                        v-on:click="() => onFetchDOIReference()"
                        v-text="$t('reference.doiImport')"/>
        </div>
        <div class="layer-content box box-margin">
            <reference-form ref="form"
                            :errors="errors"
                            :on-after-submit="onAfterFormSubmit"/>
        </div>
        <div class="layer-footer">
            <button class="button"
                    v-on:click="close"
                    v-text="$t('common.close')">
            </button>
            <button class="button"
                    v-if="!isReferenceUsageEdit && !isPublished" 
                    v-on:click="onSubmit(false)"
                    v-text="$t('common.saveAsDraft')">
            </button>
            <button class="button"
                    v-if="!isPublished"
                    v-on:click="onSubmit(true)"
                    v-text="$t('common.publish')">
            </button>
            <button class="button"
                    v-if="isPublished"
                    v-on:click="onSubmit(true)"
                    v-text="$t('common.save')">
            </button>
            <!-- <button class="button float-right"
                    v-on:click="() => onFetchDOIReference()"
                    v-text="$t('reference.doiImport')"/> -->
        </div>
    </div>
</template>
<script>
import ReferenceForm from '../forms/ReferenceForm.vue';

export default {
    components: {
        ReferenceForm,
    },
    props: {
        onAfterSubmit: {
            type: Function,
            required: true,
        },
    },
    data() {
        return {
            errors: {},
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
        onFetchDOIReference() {
            this.$store.commit('layer/ADD', {
                template: () => import('./DoiLayer.vue'),
                props: {
                    onOverwrite: (data) => {
                        this.$refs.form.onOverwrite(data);
                    },
                },
            });
        },
        close() {
            this.$emit('close');
        },
        onSubmit(isPublish) {
            this.$refs.form.submit(isPublish);
        },
        onAfterFormSubmit(data) {
            this.onAfterSubmit(data);
            this.close();
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

    .button-margin {
        margin: 0.5rem;
    }

    .box-margin {
        margin-top: 0.5rem;
    }

}
</style>
