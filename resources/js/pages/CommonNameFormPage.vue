<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4">
                <common-name-form ref="form"
                                 :on-after-submit="onAfterFormSubmit"
                                 :preset-data="presetData"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button m-0"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button class="button m-0"
                            v-on:click="onSubmit(true)"
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
    computed: {
        isPublished(){
            if (this.presetData){
                return this.presetData.isPublish ?? false
            } else {
                return false
            }
        }
    },
    methods: {
        goBack(){
            window.history.back();
        },
        onSubmit() {
            this.$refs.form.submit();
        },
        async onPreload() {
            if (this.$route.name === 'common-name-create') {
                return 200;
            }

            try {
                const { data: { data } } = await this.axios.get(`/common-names/${this.$route.params.id}/info`);
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