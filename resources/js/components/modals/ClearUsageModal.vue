<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="title text-center">{{ $t('namespace.clearNamespace') }}</p>
                <p class="info-text text-center">
                    {{ $t('namespace.clearNamespaceInfo') }}
                </p>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button" v-on:click="onClose">{{ $t('common.cancel') }}</button>
            <button class="button" v-on:click="onSubmit">{{ $t('common.submit') }}</button>
        </div>
    </div>
</template>
<script>

import { openNotify } from '../../utils';

export default {
    props: {
        id: {
            type: Number,
            required: true,
        },
        refresh: {
            type: Function,
            default() {

            },
        },

    },
    methods: {
        onSubmit: _.debounce(function () {

            const url =`namespaces/${this.id}/clear`;

            this.axios
                .post(url, {})
                .then(() => {
                    location.reload();
                })
        }),
        onClose(){
            this.$store.commit('closeModal');
        }
    },
    components: {
    }
};



</script>
<style>
.info-text {
    font-size: 1.2rem;
}
</style>