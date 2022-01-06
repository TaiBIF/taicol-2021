<template>
    <div class="tab-page">
        <div class="header">
            <div class="title is-3">
                <slot name="title"/>
                <div class="fake" v-for="tab in tabs.filter(t => t.display)"></div>
            </div>
            <div class="tabs is-right">
                <slot name="tabs" ref="tabs">
                    <ul>
                        <li v-for="tab in tabs"
                            v-if="tab.display"
                            :class="{'is-active': (current === tab.key || (current === '' && tab.default))}"
                            v-on:click="onChangeTab(tab.key)">
                            <a>{{ tab.title }}</a>
                        </li>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="box">
            <slot name="content"/>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            tabs: {
                default() {
                    return [];
                }
            },
            current: {
                type: String,
                required: true,
            }
        },
        methods: {
            onChangeTab(key) {
                this.$emit('change-tab', key);
            }
        }
    }
</script>
<style lang="scss" scoped>
    .tab-page {
        display: flex;
        flex-flow: column;
        height: 100%;
        flex-flow: column;
        flex: 1 1 auto;
    }

    .box {
        height: 100%;
        padding: 0;
        overflow-y: auto;
        overflow-x: hidden;
        position: relative;
    }

    .title {
        margin: 0;
        padding-bottom: 1.5rem;
    }

    .fake {
        display: inline-block;
        width: 8rem; // tab 的寬度
        height: 1rem;
        content: ' ';
    }

    .tabs {
        position: absolute;
        right: 0;
        bottom: 0;
        display: inline-block;

        ul {
            border: none;

            li {
                background: rgba(225, 225, 225, 1);
                margin-left: 1px;

                a {
                    min-width: 3rem;
                    padding-left: 2rem;
                    padding-right: 2rem;
                }

                &.is-active {
                    background: white;
                }
            }
        }
    }
</style>
