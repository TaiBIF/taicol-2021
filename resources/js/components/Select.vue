<template>
    <div class="control">
        <v-select
            ref="mySelect"
            v-model="localValue"
            :class="{'is-danger': errors}"
            :clear-search-on-blur="clearSearchOnBlur"
            :clearable="clearable"
            :close-on-select="!multiple"
            :components="{OpenIndicator: Indicator}"
            :create-option="createOption"
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
                    {{ $t('common.noResult') }}
                </slot>
            </template>
        </v-select>
        <p v-for="m in errors" class="is-danger">{{ $t(`validation.${m}`) }}</p>
    </div>
</template>
<script>
import Sortable from 'sortablejs';

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
                    newOption = { [this.label]: newOption };
                }

                this.$emit('option:created', newOption);
                return newOption;
            },
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
        multiple() {
            this.initSortable();
        },
    },
    data() {
        return {
            Indicator: {
                render: (createElement) => createElement('div', ' '),
            },
            typingTimer: null,
            localValue: this.value,
            keyword: '',
        };
    },
    mounted() {
        this.initSortable();
    },
    beforeDestroy() {
        if (this._sortable) {
            this._sortable.destroy();
            this._sortable = null;
        }
    },
    methods: {
        initSortable() {
            if (!this.multiple) return;
            this.$nextTick(() => {
                const el = this.$refs.mySelect?.$el?.querySelector('.vs__selected-options');
                if (!el || this._sortable) return;
                this._sortable = Sortable.create(el, {
                    draggable: '.vs__selected',
                    filter: 'input',
                    preventOnFilter: false,
                    animation: 150,
                    onEnd: (evt) => {
                        if (evt.oldIndex === evt.newIndex) return;
                        const arr = [...this.localValue];
                        const [moved] = arr.splice(evt.oldIndex, 1);
                        arr.splice(evt.newIndex, 0, moved);
                        this.localValue = arr;
                        this.updateValue(arr);
                    },
                });

                // 阻止點擊已選項目時觸發下拉選單
                el.addEventListener('mousedown', (e) => {
                    if (e.target.closest('.vs__selected')) {
                        e.stopPropagation();
                    }
                }, true);
            });
        },
        onKeydown(text) {
            const app = this;
            clearTimeout(this.typingTimer);
            this.typingTimer = setTimeout(() => app.typing(text), 500);
        },
        updateValue(value) {
            if (this.keyId) {
                if (this.multiple) {
                    this.$emit('input', value.map((item) => item[this.keyId]));
                } else {
                    this.$emit('input', value ? value[this.keyId] : null);
                }
            } else {
                this.$emit('input', value);
            }
        },
        typing(text) {
            this.keyword = text;
            this.$emit('typing', { value: this.value, keyword: text });
        },
    },
};
</script>
<style lang="scss">
.vs--disabled .vs__dropdown-toggle, .vs--disabled .vs__clear, .vs--disabled .vs__search, .vs--disabled .vs__selected, .vs--disabled .vs__open-indicator {
    background-color: #f8f8f887;
}

.vs__selected-options .vs__selected {
    cursor: grab;

    &.sortable-ghost {
        opacity: 0.4;
    }
}
</style>