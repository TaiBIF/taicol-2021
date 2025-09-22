<template>
    <div>
        <div class="px-16 py-12 w-[400px] text-center">
            <p>
                {{ $t('taxonName.commonNameSaved') }}
            </p>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button is-success" v-on:click="continueEditing">{{ $t('common.continueEditing') }}</button>        
            <router-link
                v-on:click.native="closeModal"
                :to="{name: 'taxon-name-page', params: {id: taxonNameId}}"
                class="button is-success">
                {{ $t('taxonName.viewTaxonName') }}
            </router-link>

        </div>
    </div>
</template>
<script>
export default {
    props: {
        taxonNameId: {
            type: Number,
            required: true,
        },
        onContinueEditing: {
            type: Function,
            default: null,
        }
    },
    methods: {
        continueEditing() {
            // 點選繼續編輯時，執行回調並關閉 modal
            if (this.onContinueEditing) {
                this.onContinueEditing();
            }
            this.$store.commit('closeModal');
        },
        closeModal() {
            this.$store.commit('closeModal');
        },
    },
};
</script>