<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div>
                <p class="is-danger">{{ $t('common.checkDuplicates') }}</p>

                <!-- AI 匯入：可選擇相似文獻直接綁定 -->
                <template v-if="isFromAi">
                    <label v-for="item in duplicates" :key="item.id" class="flex items-start gap-2 my-2"
                           :class="isSelectable(item) ? 'cursor-pointer' : 'opacity-60'">
                        <input type="radio" class="mt-1" :value="item" v-model="selected" :disabled="!isSelectable(item)"/>
                        <router-link target="_blank" :to="{name: 'reference-page', params: {id: item.id}}" class="my-link">
                            <b class="text-base">{{ item.title }}</b>
                            <span v-if="statusText(item.status)" class="ml-1 text-sm"
                                  :class="isSelectable(item) ? 'text-orange-600' : 'text-red-500'">{{ statusText(item.status) }}</span>
                            <br>
                            <span class="my-subtitle">{{ item.subtitle }}</span>
                        </router-link>
                    </label>
                    <p class="help mt-2">{{ onForceSave ? $t('aiImport.duplicates.hint', { publish: $t('common.publish') }) : $t('aiImport.similar.hint') }}</p>
                    <p v-if="bindError" class="help is-danger mt-1" v-html="bindError"></p>

                    <!-- 已有 PDF、無學名使用：確認後可覆蓋 -->
                    <div v-if="fileConflict" class="box mt-2">
                        <p>{{ $t('aiImport.overwrite.message') }}</p>
                        <p>{{ $t('aiImport.overwrite.existingPdf') }}<a v-if="fileConflict.fileUrl" :href="fileConflict.fileUrl" target="_blank" class="my-link">{{ $t('aiImport.overwrite.openExisting') }}</a></p>
                        <p>{{ $t('aiImport.overwrite.uploadedPdf') }}<a v-if="fileConflict.newFileUrl" :href="fileConflict.newFileUrl" target="_blank" class="my-link">{{ $t('aiImport.overwrite.openUploaded') }}</a></p>
                        <div class="flex gap-2 mt-2">
                            <button class="button is-primary" :class="{ 'is-loading': isBinding }" :disabled="isBinding"
                                    v-on:click="onBind(true)">{{ $t('aiImport.overwrite.confirm') }}</button>
                            <button class="button" v-on:click="fileConflict = null">{{ $t('common.cancel') }}</button>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div v-for="item in duplicates" :key="item.id">
                        <router-link target="_blank" :to="{name: 'reference-page', params: {id: item.id}}" class="my-link">
                            <b class="text-base">{{ item.title }}</b>
                            <br>
                            <span class="my-subtitle">{{ item.subtitle }}</span>
                        </router-link>
                    </div>
                </template>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <template v-if="isFromAi">
                <button class="button" :disabled="!selected" v-on:click="selected = null">{{ $t('aiImport.action.clearSelection') }}</button>
                <button class="button is-primary"
                        :class="{ 'is-loading': isBinding }"
                        :disabled="!selected || isBinding"
                        v-on:click="onBind(false)">{{ $t('aiImport.action.bind') }}</button>
            </template>
            <!-- 文獻已完全相同存在時不提供繼續新增 -->
            <button v-if="onForceSave" class="button" :class="{ 'is-primary': !isFromAi }" v-on:click="handleForceSave">{{ $t('common.publish') }}</button>
            <button class="button" v-on:click="onClose">{{ $t('common.goBack') }}</button>
        </div>
    </div>
</template>
<script>
import { serverMessage } from '../../utils/serverMessage';

export default {
    props: {
        duplicates: {
            type: Array,
            default: () => []
        },
        // 接收父元件傳來的強制存檔函式
        onForceSave: {
            type: Function,
            required: false
        },
        // AI 匯入時才有：本次上傳 PDF 的 log id
        aiLogId: {
            type: [Number, String],
            default: null,
        },
    },
    data() {
        return {
            selected: null,
            isBinding: false,
            bindError: '',
            fileConflict: null,
        };
    },
    watch: {
        selected() {
            this.fileConflict = null;
            this.bindError = '';
        },
    },
    computed: {
        isFromAi() {
            return !!this.aiLogId;
        },
    },
    methods: {
        // 已有學名使用、解析中、草稿不可綁定
        isSelectable(item) {
            return !item.status || ['bindable', 'has_file'].includes(item.status);
        },
        statusText(status) {
            return ['has_file', 'has_usage', 'processing', 'draft'].includes(status)
                ? this.$t(`aiImport.status.${status}`)
                : '';
        },
        onClose(){
            this.$store.commit('closeModal');
        },
        // 觸發強制存檔並關閉 Modal
        handleForceSave() {
            if (this.onForceSave) {
                this.onClose();
                this.onForceSave();
            }
        },
        refLink(payload) {
            if (!payload) return '';
            const href = this.$router.resolve({ name: 'reference-page', params: { id: payload.id } }).href;
            return `<a href="${href}" target="_blank" class="my-link">${payload.title}</a>`;
        },
        // 綁定相似文獻 → 關閉新增文獻表單 → 開啟學名使用解析
        onBind(overwrite = false) {
            if (!this.selected) return;

            this.isBinding = true;
            this.bindError = '';

            this.axios.post('/fetch/reference/ai/bind', {
                referenceId: this.selected.id,
                aiLogId: this.aiLogId,
                overwrite,
            })
            .then(({ data }) => {
                this.$store.commit('setBindReferenceData', data.data);
                this.$store.commit('closeModal');
                this.$store.commit('layer/CLOSE');
                this.$store.commit('openModal', {
                    component: () => import('./BindReferenceModal.vue'),
                });
            })
            .catch((err) => {
                const { status, data } = err;
                if (status === 409 && data && data.code === 'REF_WITH_FILE') {
                    this.fileConflict = data.payload;
                } else if (status === 409 && data) {
                    const prefix = {
                        REF_HAS_USAGE: this.$t('validation.referenceUsagePrefix'),
                        REF_PROCESSING: this.$t('aiImport.conflict.processing'),
                        DRAFT_EXISTS: this.$t('aiImport.conflict.draft'),
                    }[data.code] || '';
                    this.bindError = `${prefix} ${this.refLink(data.payload)}`;
                } else {
                    this.bindError = serverMessage(err, this.$t('aiImport.error.bindFailed'));
                }
            })
            .finally(() => {
                this.isBinding = false;
            });
        },
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