<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">

            <div class="box overflow-y-auto px-10 py-4 min-100">
                <div class="py-3 flex items-center">
                    <p class="ml-3 font-bold text-3xl inline">{{ $t('header.collectionMenu.buildClassification') }}</p>
                </div>
                <classification-form ref="form" @update:isReferenceSelected="isReferenceSelected = $event"/>
            </div>
            <div class="form-footer">
                <div class="flex justify-content-between">
                <!-- 左邊按鈕 -->
                <div>
                    <button class="button m-0"
                            v-on:click="clear()"
                            v-text="$t('common.clear')"/>
                </div>
                <div>
                    <button class="button m-0"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button class="button m-0" :disabled="!isReferenceSelected"
                            v-on:click="onOpenImportMadal()"
                            v-text="$t('classification.generateChecklist')"/>
                </div>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
import ClassificationForm from '../components/forms/ClassificationForm.vue';
import Page from './Page.vue';

export default {
    components: {
        Page,
        ClassificationForm,
    },
    data() {
        return {
            formStatus: this.$c.PAGE_IS_LOADING,
            presetData: null,
            isReferenceSelected: false
        };
    },
    methods: {
        clear(){
            location.reload();
        },
        goBack(){
            window.history.back();
        },
        onOpenImportMadal() {

            this.$store.commit('openModal', {
                component: () => import('../components/modals/ChecklistGenerateModal.vue'),
                props: {
                    form: this.$refs.form,
                }
            });
        },
        async onPreload() {
        },
    },
};
</script>
<style lang="scss" scoped>
.min-100 {
    min-height: 90%;
}

.justify-content-between {
    justify-content: space-between;
}

</style>