<template>
    <div>
        <div class="control">
            <input :class="{'is-danger': errors}"
                   :disabled="disabled"
                   :placeholder="placeholder"
                   :type="type"
                   :accept="accept"
                   class="input is-fullwidth" 
                   v-bind:value="type === 'file' ? undefined : value"
                   v-on:input="type !== 'file' ? onUpdateValue($event.target.value) : null"
                   v-on:change="type === 'file' ? onFileChange($event) : null"
                   v-on:keydown.enter="onPressEnter"
            />
        </div>
        <p v-for="m in errors" class="is-danger">
            <span v-if="containsHtml(m)" v-html="m"></span>
            <span v-else>{{ getErrorMessage(m) }}</span>
        </p>
    </div>
</template>

<script>
export default {
    props: {
        value: {
            type: [String, Number, File],
            default: '',
        },
        type: {
            type: String,
            default: 'text',
        },
        accept: {
            type: String,
            default: '',
        },
        errors: {
            type: Array,
        },
        placeholder: {
            type: String,
            default: '',
        },
        disabled: {
            type: Boolean,
            default() {
                return false;
            },
        },
    },
    methods: {
        containsHtml(message) {
            return typeof message === 'string' && message.includes('<');
        },
        onUpdateValue(value) {
            this.$emit('input', value);
        },
        onFileChange(event) {
            const file = event.target.files[0];
            this.$emit('input', file);
        },
        onPressEnter() {
            this.$emit('pressEnter');
        },
        getErrorMessage(m) {
            const key = `validation.${m}`;
            const translated = this.$t(key);

            // 邏輯：如果翻譯出來的結果跟 Key 一模一樣 (例如都還是 "validation.檔案太大...")
            // 代表翻譯檔找不到這個 Key，那就回傳原本的 m (中文錯誤訊息)
            if (translated === key) {
                return m;
            }

            // 否則回傳翻譯後的結果
            return translated;
        },
    },
};
</script>