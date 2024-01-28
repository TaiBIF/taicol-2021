<template>
    <div class="container flex flex-col h-full py-6 mb-4">
        <div class="box h-full content-h-limit overflow-y-auto">
            <not-found-view v-if="formStatus === $c.PAGE_IS_NOTFOUND"/>
            <reference-form v-else-if="formStatus === $c.PAGE_IS_SUCCESS"
                            ref="form"
                            :errors="errors"
                            :on-after-submit="onAfterFormSubmit"
                            :preset-data="presetData"
            />
            <loading v-else-if="formStatus === $c.PAGE_IS_LOADING"/>
        </div>
        <div class="form-footer flex">
            <div class="flex gap-2 grow">
                <button :class="{'is-loading': isLoading}"
                        class="button"
                        v-on:click="() => onFetchDOIReference(true)"
                        v-text="$t('reference.doiImport')"/>
            </div>
            <div class="flex gap-2 justify-end">
                <button :class="{'is-loading': isLoading}"
                        class="button"
                        v-on:click="() => submit(true)"
                        v-text="$t('common.save')"/>
            </div>
        </div>
    </div>
</template>
<script>
import { debounce } from 'lodash';
import ReferenceForm from '../components/forms/ReferenceForm.vue';
import Breadcrumb from '../components/Breadcrumb.vue';
import Loading from '../components/LoadingSection.vue';
import NotFoundView from '../components/views/NotFoundView.vue';
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
        };
    },

    mounted() {
        if (this.$route.name === 'reference-edit') {
            this.fetchReference().then(() => {
                if (this.presetData) {
                    this.$store.commit('breadcrumb/SET_ITEMS', [{
                        url: `/references/${this.presetData.id}`,
                        name: this.presetData ? title(this.presetData) : '',
                        to: {
                            name: 'reference-page',
                            params: {
                                id: this.presetData.id,
                            },
                        },
                    }, {
                        url: '#',
                        name: this.$t('reference.edit'),
                        to: {
                            name: 'reference-edit',
                            params: {
                                id: this.presetData.id,
                            },
                        },
                    }]);
                }
            });
        } else {
            this.formStatus = this.$c.PAGE_IS_SUCCESS;
            this.$store.commit('breadcrumb/SET_ITEMS', [{
                url: '#',
                name: this.$t('reference.create'),
                to: {
                    name: 'reference-create',
                },
            }]);
        }
    },
    destroyed() {
        this.$store.commit('breadcrumb/CLEAR_ITEMS');
    },
    methods: {
        onFetchDOIReference() {
            this.$store.commit('openModal', {
                component: () => import('../components/modals/DoiModal.vue'),
                props: {
                    onOverwrite: (data) => {
                        this.$refs.form.onOverwrite(data);
                    },
                },
            });
        },
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
            this.$router.push({
                name: 'reference-page',
                params: {
                    id: data.id,
                },
            });
        },
    },
};
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
