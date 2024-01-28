<template>
    <div class="layer-wrapper">
        <div class="layer-header">
            <button class="button is-text is-inline" v-on:click="close">
                <i class="fas fa-times"></i>
            </button>
            <p class="title is-inline" v-text="$t('reference.create')"/>
        </div>
        <div class="layer-content box">
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
                    v-on:click="submit"
                    v-text="$t('common.submit')">
            </button>
            <button class="button float-right"
                    v-on:click="() => onFetchDOIReference()"
                    v-text="$t('reference.doiImport')"/>
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
        submit() {
            this.$refs.form.submit(true);
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
}
</style>
