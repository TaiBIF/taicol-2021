<template>
    <div>
        <div class="control">
            <input :class="{'is-danger': errors}"
                   :disabled="disabled"
                   :placeholder="placeholder"
                   :type="type"
                   class="input is-fullwidth" v-bind:value="value"
                   v-on:input="onUpdateValue($event.target.value)"
                   v-on:keydown.enter="onPressEnter"
            />
        </div>
        <p class="is-danger" v-for="m in errors">{{ m }}</p>
    </div>
</template>

<script>
    export default {
        props: {
            value: {
                type: String | Number,
                default: '',
            },
            type: {
                type: String,
                default: 'text',
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
            onUpdateValue: function (value) {
                this.$emit('input', value)
            },
            onPressEnter() {
                this.$emit('pressEnter');
            }
        },
    }
</script>
<style lang="scss" scoped>
    .is-right {
        text-align: right;
    }
</style>
