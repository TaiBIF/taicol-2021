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
            <span v-else>{{ $t(`validation.${m}`) }}</span>
        </p>
    </div>
</template>

<script>
export default {
    props: {
        value: {
            type: [String, Number, File], // 加上 File 類型
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
            this.$emit('input', file); // 發送 File 物件而不是字串
        },
        onPressEnter() {
            this.$emit('pressEnter');
        },
    },
};
</script>