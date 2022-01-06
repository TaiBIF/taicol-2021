<template>
    <div class="container">
        <div class="page-h-limit pt-8 relative">
            <div class="box h-full content-h-limit overflow-y-auto">
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
                            :class="{'is-loading': isLoading}"
                            v-on:click="() => submit(true)"
                            v-text="$t('forms.actions.publish')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { debounce } from 'lodash';
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
                isLoading: false,
                formStatus: this.$c.PAGE_IS_INITIAL,
            }
        },

        mounted() {
            if (this.$route.name === 'reference-edit') {
                this.fetchReference().then(() => {
                    if (this.presetData) {
                        this.$store.commit('breadcrumb/SET_ITEMS', [{
                            url: `/references/${this.presetData.id}`,
                            name: this.presetData ? title(this.presetData) : '',
                        }, {
                            url: '#',
                            name: this.$t('functions.editReference'),
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
        destroyed() {
            this.$store.commit('breadcrumb/CLEAR_ITEMS');
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
            submit: debounce(function (isPublish) {
                this.isLoading = true;
                this.$refs.form
                    .submit(isPublish)
                    .catch(() => {
                        this.isLoading = false;
                    });
            }),
            onAfterFormSubmit(data) {
                this.$router.push(`/references/${data.id}`);
            },
        },
    }
</script>
<style lang="scss" scoped>
.page-h-limit {
    min-height: calc(100vh - #{$navbar-height});
}
.content-h-limit {
    height: calc(100vh - #{$navbar-height} - 7rem);
    max-height: calc(100vh - #{$navbar-height} - 7rem);
}
</style>
