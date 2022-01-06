<template>
    <page :preload="onPreload">
        <div class="form">
            <div class="form-body box">
                <simple-person-form ref="form" :on-after-submit="onAfterSubmit" :presetData="person"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button"
                            v-on:click="onSubmit(true)"
                            v-text="$t('forms.actions.publish')"/>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
    import SimplePersonForm from '../components/forms/SimplePersonForm';
    import Page from './Page';

    export default {
        data() {
            return {
                person: null,
            }
        },
        methods: {
            async onPreload() {
                try {
                    const { data } = await this.axios.get(`/persons/${this.$route.params.id}`)
                    this.person = data;
                    return 200;
                } catch (e) {
                    return e.status;
                }
            },
            onAfterSubmit() {
                this.$router.push(`/persons/${this.person.id}`);
            },
            onSubmit() {
                this.$refs.form.submit(true);
            },
        },
        components: {
            Page,
            SimplePersonForm,
        },
    }
</script>
<style lang="scss" scoped>
    .form-body {
        height: calc(100vh - #{$navbar-height} - 7rem);
    }

</style>
