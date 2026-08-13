<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <p class="is-danger">您輸入的內容於資料庫中已有以下類似的資料，請先確認是否相同以避免重複建立：</p>
            <div v-for="item in duplicates" :key="item.id">
                <router-link target="_blank" :to="{ name: 'person-page', params: { id: item.id } }" class="my-link">
                    <b class="text-base">{{ item.title }}</b>
                    <br>
                    <span class="my-subtitle">{{ item.subtitle }}</span>
                </router-link>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button class="button is-primary" v-on:click="handleForceSave">{{ $t('common.publish') }}</button>
            <button class="button" v-on:click="onClose">{{ $t('common.goBack') }}</button>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        duplicates: {
            type: Array,
            default: () => [],
        },
        onForceSave: {
            type: Function,
            required: false,
        },
    },
    methods: {
        onClose() {
            this.$store.commit('closeModal');
        },
        handleForceSave() {
            if (this.onForceSave) {
                this.onClose();
                this.onForceSave();
            }
        },
    },
};
</script>
<style lang="scss" scoped>
.my-link {
    margin: 5px 0;
}
.my-subtitle {
    color: darkgray;
    font-size: 13px;
}
</style>