<template>
    <div class="control">
        <v-select
            :class="{'is-danger': errors}"
            :clear-search-on-blur="clearSearchOnBlur"
            :clearable="clearable"
            :close-on-select="!multiple"
            :create-option="createOption"
            :components="{OpenIndicator: Indicator}"
            :disabled="disabled"
            :filter-by="filterBy"
            :filterable="filterable"
            :input-id="keyId"
            :label="label"
            :loading="loading"
            :multiple="multiple"
            :options="options"
            :push-tags="pushTag"
            :searchable="searchable"
            :selectable="selectable"
            :taggable="taggable"
            class="has-background-white"
            ref="mySelect"
            v-model="localValue"
            v-on:input="(val) => updateValue(val)"
            v-on:search="onKeydown"
        >
            <template v-slot:option="option">
                <slot :option="option" name="option">
                    {{ label ? option[label] : option }}
                </slot>
            </template>
            <template v-slot:selected-option="option">
                <slot :option="option" name="selected-option">
                    {{ label ? option[label] : option }}
                </slot>
            </template>
            <template v-slot:no-options="{search, searching, loading}">
                <slot name="no-options">
                    {{ $t('forms.noResult') }}
                </slot>
            </template>
        </v-select>
        <p class="is-danger" v-for="m in errors">{{ m }}</p>
    </div>
</template>
<style lang="scss">
    .vs__dropdown-option {
        &:hover {
            background-color: lightgray;
            cursor: pointer;
        }

    }

    .vs__dropdown-option--highlight {
        background: lightgray !important;
    }
</style>
<script>
    export default {
        props: {
            keyId: {
                type: String,
            },
            label: {
                type: String,
                required: false,
            },
            value: {
                type: Object | Number | Array,
            },
            options: {
                type: Array,
                default: () => [],
            },
            errors: {
                type: Array,
            },
            searchable: {
                type: Boolean,
                default: true,
            },
            clearable: {
                type: Boolean,
                default: false,
            },
            multiple: {
                type: Boolean,
                default: false,
            },
            filterBy: {
                type: Function,
                default(option, label, search) {
                    return (label || '').toLowerCase().indexOf(search.toLowerCase()) > -1;
                },
            },
            createOption: {
                type: Function,
                default(newOption) {
                    if (typeof this.optionList[0] === 'object') {
                        newOption = {[this.label]: newOption}
                    }

                    this.$emit('option:created', newOption)
                    return newOption
                }
            },
            taggable: {
                type: Boolean,
                default: false,
            },
            pushTag: {
                type: Boolean,
                default: false,
            },
            filterable: {
                type: Boolean,
                default: false,
            },
            selectable: {
                type: Function,
                default: () => true,
            },
            clearSearchOnBlur: {
                type: Function,
                default: ({ clearSearchOnSelect, multiple }) => clearSearchOnSelect && !multiple,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
            loading: {
                type: Boolean,
                default: false,
            },
        },
        watch: {
            value(value) {
                this.localValue = value;
            },
        },
        data() {
            return {
                Indicator: {
                    render: createElement => createElement('div', ' '),
                },
                typingTimer: null,
                localValue: this.value,
                keyword: '',
            }
        },
        methods: {
            onKeydown(text) {
                const app = this;
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => app.typing(text), 500);
            },
            updateValue(value) {
                if (this.keyId) {
                    console.log(value);

                    if (this.multiple) {
                        this.$emit('input', value.map((item) => item[this.keyId]))
                    } else {
                        this.$emit('input', value[this.keyId])
                    }
                } else {
                    this.$emit('input', value)
                }
            },
            typing(text) {
                this.keyword = text;
                this.$emit('typing', { value: this.value, keyword: text });
            },
        },
    }
</script>

