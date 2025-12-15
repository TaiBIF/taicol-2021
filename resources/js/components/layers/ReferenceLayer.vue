<template>
    <div class="layer-wrapper">
        <div class="layer-header">
            <button class="button is-text is-inline" v-on:click="close">
                <i class="fas fa-times"></i>
            </button>
            <p class="title is-inline" v-text="$t('reference.create')"/>            
            <br>
            <button class="button button-margin"
                    v-if="!isFromAiImport"
                    v-on:click="() => onFetchDOIReference()"
                    v-text="$t('reference.doiImport')"/>
        </div>
        <div class="layer-content box box-margin">
            <reference-form ref="form"
                            :errors="errors"
                            :on-after-submit="onAfterFormSubmit"/>
        </div>
        <div class="layer-footer">
            <button class="button"
                    v-on:click="close"
                    v-text="$t('common.close')">
            </button>
            <button class="button"
                    v-if="!isReferenceUsageEdit && !isPublished && !isFromAiImport" 
                    v-on:click="onSubmit(false)"
                    v-text="$t('common.saveAsDraft')">
            </button>
            <button class="button"
                    :class="{'is-loading': isLoading}"
                    v-if="!isPublished"
                    v-on:click="onSubmit(true)"
                    v-text="$t('common.publish')">
            </button>
            <button class="button"
                    :class="{'is-loading': isLoading}"
                    v-if="isPublished"
                    v-on:click="onSubmit(true)"
                    v-text="$t('common.save')">
            </button>
        </div>
    </div>
</template>
<script>
import ReferenceForm from '../forms/ReferenceForm.vue';

export default {
    components: {
        ReferenceForm,
    },
    props: {
        onAfterSubmit: {
            type: Function,
            required: true,
        },
        presetData: {
            type: Object,
            default: null,
        },
        usePresetFromStore: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            errors: {},
            storePresetData: null,
            isLoading: false,
        };
    },
    computed: {
        isReferenceUsageEdit() {
            return this.$route.name.includes('reference-usages-list') || this.$route.name.includes('reference-usages-edit')
        },
        isPublished(){
            const data = this.usePresetFromStore ? this.storePresetData : this.presetData;
            if (data){
                return data.isPublish ?? false
            } else {
                return false
            }
        },
        finalPresetData() {
            return this.usePresetFromStore ? this.storePresetData : this.presetData;
        },
        isFromAiImport() {
            return this.finalPresetData && this.finalPresetData.fromAiImport === true;
        },
    },
    methods: {
        onFetchDOIReference() {
            this.$store.commit('layer/ADD', {
                template: () => import('./DoiLayer.vue'),
                props: {
                    onOverwrite: (data) => {
                        this.$refs.form.onOverwrite(data);
                    },
                },
            });
        },
        close() {
            this.$emit('close');
        },
        onSubmit(isPublish) {
            this.isLoading = true;
            this.$refs.form.submit(isPublish);
        },
        onAfterFormSubmit(data) {
            this.isLoading = false;
            this.onAfterSubmit(data);

            if (!this.isFromAiImport) {
                this.close();
            }

            this.close();
        },
    },
    watch: {
        finalPresetData: {
            handler(newData) {
                if (newData) {
                    this.$nextTick(() => {
                        if (this.$refs.form && typeof this.$refs.form.onOverwrite === 'function') {
                            this.$refs.form.onOverwrite(newData);
                        } else {
                            console.error('Form ref or onOverwrite method not available');
                        }
                    });
                }
            },
            immediate: true,
        },
    },
    mounted() {
        // 如果使用 store 模式，從 store 獲取資料
        if (this.usePresetFromStore) {
            this.storePresetData = this.$store.state.referencePresetData || null;
            
            // 清除 store 中的資料
            this.$store.commit('setReferencePresetData', null);
        }
        
        // 使用 $nextTick 確保 DOM 完全載入
        this.$nextTick(() => {
            const dataToUse = this.finalPresetData;
            if (dataToUse && this.$refs.form) {
                this.$refs.form.onOverwrite(dataToUse);
            }
        });
    },
    updated() {
        // 如果 ref 還沒準備好，在 updated 時再嘗試
        const dataToUse = this.finalPresetData;
        if (dataToUse && this.$refs.form && !this._dataFilled) {
            this.$refs.form.onOverwrite(dataToUse);
            this._dataFilled = true;
        }
    },
};
</script>
<style lang="scss" scoped>
.layer-wrapper {
    height: 100%;

    .layer-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: white;
        padding: 1.5rem;
        height: 5.5rem;
    }

    .layer-content {
        overflow-x: hidden;
        height: calc(100% - 11rem);
        position: relative;
    }

    .layer-footer {
        position: fixed;
        bottom: 0;
        height: 5.5rem;
        width: 100%;
        padding: 1.5rem;
    }

    .button-margin {
        margin: 0.5rem;
    }

    .box-margin {
        margin-top: 0.5rem;
    }

}
</style>