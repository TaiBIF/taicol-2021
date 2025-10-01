<template>
    <div class="container flex flex-col h-full py-6 mb-4">
        <div class="box h-full content-h-limit overflow-y-auto">
            <div class="py-3 flex items-center">
                    <p v-if="!presetData" class="ml-3 font-bold text-3xl inline">{{ $t('header.importMenu.literatureParsing') }}</p>
                    <p v-else class="ml-3 font-bold text-3xl inline" v-text="$t('reference.edit')"/>

            </div>
            <button :class="{'is-loading': isLoading}"
                    class="button ml-3 button-margin"
                    v-on:click="() => onFetchDOIReference(true)"
                    v-text="$t('reference.doiImport')"/>

            <not-found-view v-if="formStatus === $c.PAGE_IS_NOTFOUND"/>
            <reference-form v-else-if="formStatus === $c.PAGE_IS_SUCCESS"
                            ref="form"
                            :errors="errors"
                            :on-after-submit="onAfterFormSubmit"
                            :preset-data="presetData"
            />
            <loading v-else-if="formStatus === $c.PAGE_IS_LOADING"/>
        </div>
        <div class="form-footer flex justify-end">
            <div class="flex gap-2">
                <button class="button m-0"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button class="button m-0"
                            v-if="!isPublished" 
                            v-on:click="onSubmit(false)"
                            v-text="$t('common.saveAsDraft')"/>
                    <button class="button m-0"
                            v-if="!isPublished"
                            v-on:click="onSubmit(true)"
                            v-text="$t('common.publish')"/>
                    <button class="button m-0"
                            v-if="isPublished"
                            v-on:click="onSubmit(true)"
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
    computed: {
        isPublished(){
            if (this.presetData){
                return this.presetData.isPublish ?? false
            } else {
                return false
            }
        }
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
        goBack(){
            window.history.back();
        },
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
            return this.axios.get(`/references/${this.$route.params.id}/info`)
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
        onSubmit(isPublish) {
            this.$refs.form.submit(isPublish);
        },
        onAfterFormSubmit(data) {
            // 如果是草稿的話 留在編輯頁面
            if (data.isPublish == false){
                // this.reload();
                this.$router.push({ name: 'reference-edit', params: { id: data.id } });

            } else {
                this.$router.push({ name: 'reference-page', params: { id: data.id } });
            }
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

.button-margin {
   margin-top: .25rem;
   margin-bottom: 1rem;
}
</style>
