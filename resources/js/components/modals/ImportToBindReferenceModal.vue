<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="title text-center">{{ $t('namespace.importToReference') }}</p>
                <p class="select-margin">{{ $t('namespace.referenceToBind') }}</p>
                <reference-select
                    v-model="selectedReference"
                />
                <br>
                <p>{{ $t('namespace.importToReferenceNotice') }}</p>

            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button" v-on:click="onClose">{{ $t('common.cancel') }}</button>
            <button class="button" :class="{ 'is-loading': isLoading }"
                    v-on:click="onSubmit">{{ $t('reference.importNames') }}</button>
        </div>
    </div>
</template>
<script>
import { openNotify } from '../../utils';
import ReferenceSelect from '../selects/ReferenceSelect.vue';

export default {
    props: {
        reference: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            selectedReference: null,
            isLoading: false,
        }
    },
    mounted() {
        // 使用傳入的 props
        this.selectedReference = this.reference;
        console.log('從 props 取得的 reference:', this.selectedReference?.id);
    },
    methods: {
        onClose(){
            this.$store.commit('closeModal');
        },


        onSubmit() {

            if (!this.selectedReference){
                openNotify('請先選擇文獻','is-danger')
                return;
            }

            this.isLoading = true;

            // 先確認文獻是否已有usage
            this.axios.post(`/namespaces/import/${ this.selectedReference.id}`, {
                ids: [this.$route.params.id],
                overwrite: false,
            }).then((response) => {
                if (response.data.data === true) {
                    openNotify('此文獻已有學名使用存在，不得匯入', 'is-danger');
                    this.isLoading = false;
                    return;
                }
                // 這邊不會有重複匯入的情況 因為一旦有學名使用就不得匯入

                this.$router.push({ name: 'reference-page', params: { id: this.selectedReference.id } });
                this.$store.commit('closeModal');
            }).catch((error) => {
                console.log(error);
                this.isLoading = false;
                const message = error?.message || '處理失敗，請聯絡管理員';
                openNotify(message, 'is-danger');
            });

        },
    },
    components: {
        ReferenceSelect
    }
};
</script>

<style lang="scss" scoped>

.select-margin {
   margin-bottom: .75rem;
}
</style>
