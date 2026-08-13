<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="is-danger">{{ $t('common.checkDuplicates') }}</p>
                <div v-for="item in duplicates" :key="item.id">
                    <router-link target="_blank" :to="{name: 'reference-page', params: {id: item.id}}" class="my-link">
                        <b class="text-base">{{ item.title }}</b>
                        <br> 
                        <span class="my-subtitle">{{ item.subtitle }}</span>
                    </router-link>
                </div>
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
            default: () => []
        },
        // [新增] 接收父元件傳來的強制存檔函式
        onForceSave: {
            type: Function,
            required: false // 設為非必填以免報錯，但邏輯上要有
        }
    },   
    methods: {
        onClose(){
            this.$store.commit('closeModal');
        },
        // [新增] 觸發強制存檔並關閉 Modal
        handleForceSave() {
            if (this.onForceSave) {
                this.onClose(); // 先關視窗
                this.onForceSave(); // 執行父元件的存檔動作
            }
        }
    }
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
