<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4">
                <div class="py-3 flex items-center">
                    <p v-if="!presetData" class="ml-3 font-bold text-3xl inline">{{ $t('taxonName.create') }}</p>
                    <p v-else class="ml-3 font-bold text-3xl inline">{{ $t('taxonName.edit') }}</p>

                </div>
                <taxon-name-form ref="form"
                                 :on-after-submit="onAfterFormSubmit"
                                 :preset-data="presetData"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button m-0"
                            :class="{ 'is-loading': isLoading }"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button class="button m-0"
                            :class="{ 'is-loading': isLoading }"
                            v-if="!isPublished" 
                            v-on:click="onSubmit(false)"
                            v-text="$t('common.saveAsDraft')"/>
                    <button class="button m-0"
                            v-if="!isPublished"
                            :class="{ 'is-loading': isLoading }"
                            v-on:click="onSubmit(true)"
                            v-text="$t('common.publish')"/>
                    <button class="button m-0"
                            :class="{ 'is-loading': isLoading }"
                            v-if="isPublished"
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
            isLoading: false
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
        onSubmit(isPublish) {
            this.isLoading = true;
            this.$refs.form.submit(isPublish);
        },
        onAfterFormSubmit(data) {


            this.isLoading = false;
            // 如果是草稿的話 留在編輯頁面
            if (data.isPublish == false){
                this.$router.push({ name: 'taxon-name-edit', params: { id: data.id } });
            } else {
                this.$router.push({ name: 'taxon-name-page', params: { id: data.id } });
            }
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
