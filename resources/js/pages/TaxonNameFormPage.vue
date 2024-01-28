<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4">
                <taxon-name-form ref="form"
                                 :on-after-submit="onAfterSubmitForm"
                                 :preset-data="presetData"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button m-0"
                            v-on:click="onSubmit(true)"
                            v-text="$t('common.save')"/>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
import TaxonNameForm from '../components/forms/TaxonNameForm.vue';
import Page from './Page.vue';

export default {
    components: {
        Page,
        TaxonNameForm,
    },
    data() {
        return {
            formStatus: this.$c.PAGE_IS_LOADING,
            presetData: null,
        };
    },
    methods: {
        onSubmit(isPublish) {
            this.$refs.form.submit(isPublish);
        },
        onAfterSubmitForm(data) {
            this.$router.push({ name: 'taxon-name-page', params: { id: data.id } });
        },
        async onPreload() {
            if (this.$route.name === 'taxon-name-create') {
                return 200;
            }

            try {
                const { data: { data } } = await this.axios.get(`/taxon-names/${this.$route.params.id}/info`);
                this.presetData = data;
                return 200;
            } catch ({ status }) {
                return status;
            }
        },
    },
};
</script>
<style lang="scss" scoped>
.form-body {
    height: calc(100vh - #{$navbar-height} - 7rem);
}
</style>
