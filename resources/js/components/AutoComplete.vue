<template>
    <div class="control has-icons-left has-icons-right">
        <input :class="{'is-large': !isSmall}"
               class="input"
               placeholder="請輸入關鍵字"
               v-model="keyword"
               v-on:blur="close"
               v-on:keydown.esc="close"
               v-on:keydown.enter="goSearch"
        />
        <ul :class="{'is-large': !isSmall}"
            class="auto-list"
            v-show="value && showList && results.length">
            <li :key="i"
                class="list-group-item"
                v-for="(result, i) in results">
                {{ result }}
            </li>
        </ul>
        <a :class="{'is-large': !isSmall}" class="icon is-right"
           v-on:click="goSearch">
            <i class="fas fa-search"></i>
        </a>
    </div>
</template>
<script>
    export default {
        props: {
            results: {
                type: Array,
                default: () => [],
            },
            value: {
                type: String,
                required: true,
            },
            isSmall: {
                type: Boolean,
                default: false,
            },
        },
        watch: {
            value(value) {
                this.showList = true;
                this.keyword = value;
            },
            keyword(value) {
                this.$emit('input', value);
            },
        },
        data() {
            return {
                showList: false,
                keyword: this.value,
            }
        },
        methods: {
            close() {
                this.showList = false;
            },
            goSearch() {
                this.$emit('go-search');
            },
        },
    }
</script>
<style lang="scss" scoped>
    .auto-list {
        border-radius: 2rem;
        background: white;
        margin-top: 1rem;
        position: absolute;
        width: 100%;
        z-index: 10;
        overflow: hidden;
        border: 1px solid $light-grey;

        li {
            padding: 0 2.5rem;
            height: 2.5rem;
            line-height: 2.5rem;
            cursor: pointer;
            color: #363636;

            &:hover {
                background-color: #dfdfdf;
            }
        }

        &.is-large {
            li {
                height: 3rem;
                line-height: 3rem;
            }
        }
    }

    .control {
        .icon {
            border-left: 1.5px solid $light-grey;
            height: 1.5rem;
            margin-top: .5rem;
            cursor: pointer;
            pointer-events: inherit;

            &.is-right {
                right: .5rem;
            }

            i {
                color: #7f7f7f;
                font-size: 1rem;
            }

            &.is-large {
                height: 2.3rem;
                margin-top: 0.8rem;

                i {
                    font-size: 2rem;
                }
            }
        }
    }
</style>
