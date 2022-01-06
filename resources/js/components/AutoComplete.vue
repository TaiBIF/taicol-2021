<template>
    <div class="control has-icons-left has-icons-right">
        <v-select
            ref="select"
            v-model="keywords"
            :class="{'is-large': !isSmall}"
            :close-on-select="false"
            :create-option="onCreatedOption"
            :map-keydown="onChangeKeymap"
            :options="options"
            :select-on-key-codes="[13]"
            class="select-input has-background-white custom-select"
            label="name"
            multiple
            placeholder="請輸入關鍵字"
            taggable
            v-on:search="onKeydown"
            v-on:option:selecting="onSelecting"
        >
            <template v-slot:selected-option="option">
                <div>
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
            <template v-slot:no-options="{ search, searching, loading }">
                請輸入關鍵字 (最多五組)
            </template>
        </v-select>
        <a :class="{'is-large': !isSmall}" class="icon is-right"
           v-on:click="onGoSearch">
            <i class="fas fa-search"></i>
        </a>
    </div>
</template>
<script>
    import { debounce } from 'lodash';
    import TaxonNameSelect from './selects/TaxonNameSelect';

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
                    value.splice(-1, 1)
                }

                this.$emit('input', value);
            },
            searchType() {
                this.keywords = [];
                this.options = [];
            },
        },
        data() {
            return {
                keywords: this.value,
                options: [],
            }
        },
        methods: {
            onCreatedOption(newOption) {
                if (typeof newOption === 'string') {
                    newOption = { 'name': newOption, 'type': 'text' };
                }
                this.$emit('option:created', newOption)
                return newOption;
            },
            onKeydown(text) {
                const app = this;
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => app.onTyping(text), 500);
            },
            onChangeKeymap(map, vm) {
                map = {
                    ...map, 50: function (e) {
                        e.preventDefault();
                        if (e.key !== '@') {
                            vm.search = `${vm.search}${e.key}`;
                        }
                    },
                };

                if (vm.search === '') {
                    map = {
                        ...map, 13: (e) => {
                            this.onGoSearch();
                        },
                    };
                }
                return map;
            },
            onSelecting(selectedOption) {
                if (typeof selectedOption === 'string') {
                    return {
                        name: selectedOption,
                        type: 'name',
                    }
                }
            },
            onTyping: debounce(function (keyword) {
                const app = this;
                if (keyword.length <= 2) {
                    app.options = [];
                    return;
                }

                this.axios.get(`/search`, {
                    params: {
                        keyword,
                        type: this.searchType,
                    },
                })
                    .then(({ data: { data } }) => {
                        app.options = data.map(({ title, type }, index) => {
                            return {
                                index,
                                name: title,
                                type: type.replace(/_/, '-'),
                            }
                        });
                    });
            }),
            markedResult(value, keyword) {
                let c = new RegExp(keyword, 'ig');
                return value.replace(c, '<strong class="has-text-orange">' + keyword + '</strong>');
            },
            onGoSearch() {
                this.$emit('go-search');
            },
        },
        components: { TaxonNameSelect },
    }
</script>
<style lang="scss" scoped>
    /deep/ .custom-select {
        .vs__dropdown-toggle {
            border: 0;
            padding: .3rem 0rem;

            .vs__selected-options {
                ::placeholder {
                    color: $grey;
                }

                .vs__selected {
                    margin: 0px 2px 0px 2px;
                }
            }

            .vs__actions {
                display: none;
            }

            .vs__search {
                margin: 0;
                padding: 0;
            }
        }

        .vs__dropdown-menu {
            border-radius: 1rem;
            margin-top: 1rem;
        }
    }


    .control {
        .select-input {
            border-radius: 2rem;
            padding: .5rem 2rem;
        }

        .icon {
            border-left: 1.5px solid $light-grey;
            height: 1.5rem;
            cursor: pointer;
            pointer-events: inherit;
            margin-top: .5rem;

            &.is-right {
                right: .5rem;
            }

            i {
                color: #7f7f7f;
                font-size: 1rem;
            }

            &.is-large {
                height: 2.3rem;
                padding-left: .5rem;

                i {
                    font-size: 2rem;
                }

                &.is-right {
                    right: 1rem;
                }
            }
        }
    }
</style>
