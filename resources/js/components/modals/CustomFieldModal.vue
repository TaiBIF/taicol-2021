<template>
    <div class="modal-padding">
        <div class="form">
            <div class="columns">
                <div class="column is-6">
                    <div class="field">
                        <label class="is-marked label inline-block">
                            {{ $t('usage.fieldNameEn') }}
                        </label>
                        <general-input v-model="customFieldNameEn"/>
                    </div>
                </div>

                <div class="column is-6">
                    <div class="field">
                        <label class="label" v-text="$t('usage.fieldNameZh')"/>
                        <general-input v-model="customFieldNameZh"/>
                    </div>
                </div>
            </div>

            <button class="button button-margin "
                    v-on:click="onAddCustomField()"
                    v-text="$t('usage.addCustomField')"
                    >
            </button>
        </div>
    </div>
</template>

<script>
import { openNotify } from '../../utils';
import GeneralInput from '../GeneralInput.vue';

export default {
    data() {
        return {
            customFieldNameEn: this.customFieldNameEn,
            customFieldNameZh: this.customFieldNameZh,
        };
    },
    components: {
        GeneralInput,
    },
    methods: {
        onAddCustomField(){

            if (!this.customFieldNameEn){
                openNotify('英文欄位名稱為必填欄位','is-danger')
            } else {
                this.$emit('custom', {
                    customFieldNameEn: this.customFieldNameEn,
                    customFieldNameZh: this.customFieldNameZh,
                });
            }
        }
    },
};
</script>

<style lang="scss" scoped>
.modal-padding {
    padding: 50px;
}

.button-margin {
        margin: 0 0.75rem;
}

</style>
