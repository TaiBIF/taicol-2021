<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="title text-center">{{ $t('namespace.bindReference') }}</p>
                <router-link v-if="reference" target="_blank" :to="{name: 'reference-page', params: {id: reference.id}}" class="my-link">
                    {{ reference.title }}
                </router-link>

            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button" v-on:click="onClose">{{ $t('common.cancel') }}</button>
            <button class="button" v-on:click="onSubmit">{{ $t('namespace.continueToImportChecklist') }}</button>
        </div>
    </div>
</template>
<script>

import { openNotify } from '../../utils';

export default {
    data() {
        return {
            reference: null,
            isLoading: false,
            // errors: {},
            // result: null
        }
    },
    mounted() {
        this.reference = this.$store.state.bindReferenceData;
        console.log('從 store 取得的 reference:', this.reference.id);
        // 清除 store 資料
        this.$store.commit('setBindReferenceData', null);
    },
    methods: {
        onClose(){
            this.$store.commit('closeModal');
        },
        onSubmit(){
            // TODO 待處理

            console.log( this.reference)

            console.log('this.reference.id', this.reference.id)
            // // 發送 AI 請求
            // this.isLoading = true;

            const referenceId = this.reference.id;
            console.log('this.reference.id', referenceId)

            this.axios.post(`/fetch/namespace/usage/ai`, {
                referenceId: referenceId,
            })
            .then(({ data }) => {
                openNotify(data.message)
            })
            .catch(({ errors: e, status, message }) => {
                openNotify(data.message, 'is-danger')
            });

            this.$store.commit('closeModal');

        }
    }
};



</script>
<style>
.info-text {
    font-size: 1.2rem;
}
</style>