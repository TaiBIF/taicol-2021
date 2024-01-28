<template>
    <div class="control">
        <v-select
            ref="select"
            v-model="keywords"
            :class="{'is-large': !isSmall}"
            :close-on-select="false"
            :create-option="onCreatedOption"
            :map-keydown="onChangeKeymap"
            :options="options"
            :placeholder="`${$t('common.enterForOptions')} ${$t('common.maxFive')}`"
            :select-on-key-codes="[13]"
            class="select-input bg-white custom-select relative"
            label="name"
            multiple
            taggable
            v-on:search="onKeydown"
            v-on:option:selecting="onSelecting"
            v-on:search:blur="onBlur"
        >
            <template v-slot:selected-option="option">
                <div :title="option.name">
                    <i v-if="option.type === 'person'" class="fas fa-user"></i>
                    {{ option.name }}
                </div>
            </template>
            <template v-slot:option="option">
                <div>
                    <span v-html="option.name"/>
                    <span class="is-pulled-right is-inline-block">
                        <i v-if="option.type === 'person'"
                           :class="{'fa-user': option.type === 'person'}"
                           class="fas"></i>
                        <i v-else-if="option.type === 'reference'"
                           :class="{'fa-file-alt': true}"
                           class="fas"></i>
                    </span>
                </div>
            </template>
            <span slot="no-options"></span>
        </v-select>
        <div
            class="icon-container h-full flex items-center justify-center absolute w-[35px] top-0 right-2 py-2">
            <a :class="{'large': !isSmall}" v-on:click="onGoSearch">
                <i class="fas fa-search"></i>
            </a>
        </div>
    </div>
</template>
<script>
import { debounce } from 'lodash';
import TaxonNameSelect from './selects/TaxonNameSelect.vue';

export default {
    props: {
        value: {
            type: Array,
            required: true,
        },
        searchType: {
            type: String,
            required: true,
        },
        isSmall: {
            type: Boolean,
            default: false,
        },
    },
    watch: {
        keywords(value) {
            // limit keywords
            if (value.length > 6) {
                value.splice(-1, 1);
            }

            this.$emit('input', value);
        },
        searchType() {
            this.options = [];
        },
    },
    data() {
        return {
            keywords: this.value,
            options: [],
        };
    },
    methods: {
        onCreatedOption(newOption) {
            let option = newOption;
            if (typeof newOption === 'string') {
                option = { name: newOption, type: 'text' };
            }
            this.$emit('option:created', option);
            return option;
        },
        onKeydown(text) {
            const app = this;
            clearTimeout(this.typingTimer);
            this.typingTimer = setTimeout(() => app.onTyping(text), 500);
        },
        onChangeKeymap(map, vm) {
            let newMap = {
                ...map,
                50(e) {
                    e.preventDefault();
                    if (e.key !== '@') {
                        vm.search = `${vm.search}${e.key}`;
                    }
                },
            };

            if (vm.search === '') {
                newMap = {
                    ...map,
                    13: (e) => {
                        this.onGoSearch();
                    },
                };
            }
            return newMap;
        },
        onSelecting(selectedOption) {
            if (typeof selectedOption === 'string') {
                return {
                    name: selectedOption,
                    type: 'name',
                };
            }

            return null;
        },
        onBlur() {
            if (this.$refs.select.search.length <= 0) {
                return;
            }

            const newOption = {
                name: this.$refs.select.search,
                type: 'text',
            };

            this.$refs.select.select(newOption);
        },
        onTyping: debounce(function (keyword) {
            const app = this;
            if (keyword.length <= 2) {
                app.options = [];
                return;
            }

            this.axios.get('/search', {
                params: {
                    keyword,
                    type: this.searchType,
                },
            })
                .then(({ data: { data } }) => {
                    app.options = data.map(({ title, type }, index) => ({
                        index,
                        name: title,
                        type: type.replace(/_/, '-'),
                    }));
                });
        }),
        markedResult(value, keyword) {
            const c = new RegExp(keyword, 'ig');
            return value.replace(c, `<strong class="has-text-orange">${keyword}</strong>`);
        },
        onGoSearch() {
            this.$emit('go-search');
        },
    },
    components: { TaxonNameSelect },
};
</script>
<style lang="scss" scoped>
.control {
    .select-input {
        border-radius: 2rem;
        padding: 0rem 2rem;

        &.is-large {
            padding: .5rem 2rem;
        }
    }

    .icon-container {
        cursor: pointer;

        a {
            border-left: 1.5px solid $light-grey;
            @apply mr-0 text-lg text-gray-500;

            i {
                @apply ml-2;
            }

            &.large {
                @apply mr-4 text-[28px];
                i {
                    @apply ml-2;
                }
            }
        }
    }
}
</style>
