<template>
    <div class="container">
        <div class="form">
            <div class="form-body box">
                <not-found-view v-if="formStatus === $c.PAGE_IS_NOTFOUND"/>
                <reference-form :errors="errors"
                                :on-after-submit="onAfterFormSubmit"
                                :preset-data="presetData"
                                ref="form"
                                v-else-if="formStatus === $c.PAGE_IS_SUCCESS"/>
                <loading v-else-if="formStatus === $c.PAGE_IS_LOADING"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button"
                            v-on:click="() => submit(true)"
                            v-text="$t('forms.actions.publish')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import ReferenceForm from '../components/forms/ReferenceForm';
    import Breadcrumb from '../components/Breadcrumb';
    import Loading from '../components/LoadingSection';
    import NotFoundView from '../components/views/NotFoundView';
    import { title } from '../utils/preview/reference';

    export default {
        components: {
            Breadcrumb,
            ReferenceForm,
            Loading,
            NotFoundView,
        },
        data() {
            return {
                errors: {},
                presetData: null,
                formStatus: this.$c.PAGE_IS_INITIAL,
            }
        },

        mounted() {
            if (this.$route.name === 'reference-edit') {
                this.fetchReference().then(() => {
                    if (this.presetData) {
                        this.$store.commit('breadcrumb/SET_ITEMS', [{
                            url: '',
                            name: this.$t('functions.editReference'),
                        }, {
                            url: this.$route.url,
                            name: title(this.presetData),
                        }]);
                    }
                });
            } else {
                this.formStatus = this.$c.PAGE_IS_SUCCESS;
                this.$store.commit('breadcrumb/SET_ITEMS', [{
                    url: '#',
                    name: this.$t('functions.createReference'),
                }]);
            }
        },
        methods: {
            fetchReference() {
                return this.axios.get(`/references/${this.$route.params.id}`)
                    .then(({ data: { data } }) => {
                        this.presetData = data;
                        this.formStatus = this.$c.PAGE_IS_SUCCESS;
                    })
                    .catch(({ status }) => {
                        if (status === 401) {
                            this.$router.replace({
                                path: '/login',
                                query: { redirect: this.$route.fullPath },
                            });
                        } else if (status === 404) {
                            this.formStatus = this.$c.PAGE_IS_NOTFOUND;
                        }
                    });
            },
            openPreviewModal() {
                this.$store.commit('openModal', {
                    component: () => import('./../components/views/ReferenceView'),
                    props: this.$refs.form.previewData,
                })
            },
            submit(isPublish) {
                this.$refs.form.submit(isPublish);
            },
            onAfterFormSubmit(data) {
                this.$router.push(`/references/${data.id}`);
            },
        },
    }
</script>
