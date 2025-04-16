<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4 min-100">
                <div class="py-3 flex items-center">
                    <p class="ml-3 font-bold text-3xl inline">{{ $t('taxonName.createCommonName') }}</p>
                </div>
                <common-name-form ref="form" />
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button m-0"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button class="button m-0"
                            v-on:click="onSubmit()"
                            v-text="$t('common.save')"/>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
import CommonNameForm from '../components/forms/CommonNameForm.vue';
import Page from './Page.vue';

export default {
    components: {
        Page,
        CommonNameForm,
    },
    data() {
        return {
            formStatus: this.$c.PAGE_IS_LOADING,
            presetData: null,
        };
    },
    methods: {
        goBack(){
            window.history.back();
        },
        onSubmit() {
            this.$refs.form.submit();
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
</style>