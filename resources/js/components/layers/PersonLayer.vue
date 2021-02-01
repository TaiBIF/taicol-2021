<template>
    <div class="layer-wrapper">
        <div class="layer-header">
            <button class="button is-text is-inline" v-on:click="close">
                <i class="fas fa-times"></i>
            </button>
            <p class="title is-inline" v-text="$t('forms.person.create')"/>
        </div>
        <div class="layer-content box">
            <person-form :on-after-submit="onAfterFormSubmit"
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
    import PersonForm from './../forms/SimplePersonForm';

    export default {
        components: {
            PersonForm,
        },
        props: {
            onAfterSubmit: {
                type: Function,
                required: true,
            },
        },
        methods: {
            close() {
                this.$emit('close');
            },
            submit() {
                this.$refs.form.submit();
            },
            onAfterFormSubmit(data) {
                this.onAfterSubmit(data);
                this.close();
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
