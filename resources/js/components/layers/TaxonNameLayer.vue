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
                :errors="errors"
                :preset-data="presetData"
                :on-after-submit="onAfterFormSubmit"
                ref="form"/>
        </div>
        <div class="layer-footer">
            <button class="button"
                    v-on:click="close"
                    v-text="$t('forms.actions.close')">
            </button>
            <button class="button"
                    v-on:click="submit"
                    v-text="$t('forms.actions.submit')">
            </button>
        </div>
    </div>
</template>
<script>
    import TaxonNameForm from '../forms/TaxonNameForm';

    export default {
        components: {
            TaxonNameForm,
        },
        props: {
            title: {
                type: String,
                default() {
                    return this.$t('forms.taxonName.create');
                },
            },
            presetData: {
                type: Object,
                default: null
            },
            onAfterSubmit: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                errors: {},
            }
        },
        methods: {
            close() {
                this.$emit('close');
            },
            onAfterFormSubmit(data) {
                this.onAfterSubmit(data);
                this.close();
            },
            submit() {
                this.$refs.form.submit();
            },
        },
    }
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
