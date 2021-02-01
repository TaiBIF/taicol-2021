<template>
    <div class="container">
        <div class="form">
            <div class="form-body box">
                <not-found-view v-if="formStatus === $c.PAGE_IS_NOTFOUND"/>
                <taxon-name-form :on-after-submit="onAfterSubmitForm"
                                 :preset-data="presetData"
                                 ref="form"
                                 v-if="formStatus === $c.PAGE_IS_SUCCESS"/>
                <loading v-else/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button"
                            v-on:click="onSubmit(true)"
                            v-text="$t('forms.actions.publish')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import TaxonNameForm from '../components/forms/TaxonNameForm';
    import TaxonNameView from '../components/views/TaxonNameView';
    import Loading from '../components/LoadingSection';
    import NotFoundView from '../components/views/NotFoundView';

    export default {
        components: {
            TaxonNameForm,
            TaxonNameView,
            Loading,
            NotFoundView,
        },
        data() {
            return {
                formStatus: this.$c.PAGE_IS_LOADING,
                presetData: null,
            }
        },
        mounted() {
            if (this.$route.name === 'taxon-name-edit') {
                this.fetchTaxonName().then(() => {
                    if (this.presetData) {
                        this.$store.commit('breadcrumb/SET_ITEMS', [{
                            url: '#',
                            name: this.$t('functions.editTaxonName'),
                        }]);
                    }
                });
            } else {
                this.formStatus = this.$c.PAGE_IS_SUCCESS;
                this.$store.commit('breadcrumb/SET_ITEMS', [{
                    url: '#',
                    name: this.$t('functions.createTaxonName'),
                }]);
            }
        },
        methods: {
            onSubmit(isPublish) {
                this.$refs.form.submit(isPublish);
            },
            onAfterSubmitForm(data) {
                this.$router.push(`/taxon-names/${data.id}`);
            },
            openPreviewModal() {
                this.$store.commit('openModal', {
                    component: () => import('./../components/views/TaxonNameView'),
                    props: this.$refs.form.previewData,
                })
            },
            fetchTaxonName() {
                return this.axios.get(`/taxon-names/${this.$route.params.id}/info`)
                    .then(({ data: { data } }) => {
                        this.presetData = data;
                        this.formStatus = this.$c.PAGE_IS_SUCCESS;
                    })
                    .catch(({ status }) => {
                        if (status === 401) {
                            this.$router.push({
                                path: '/login',
                                query: { redirect: this.$route.fullPath },
                            });
                        } else if (status === 404) {
                            this.formStatus = this.$c.PAGE_IS_NOTFOUND;
                        }
                    });
            },
        },
    }
</script>
