<template>
    <div class="h-full flex flex-col">
        <div class="header z-10 shrink-0">
            <div class="font-bold m-0 pb-3 text-3xl">
                <slot name="title"/>
                <div v-for="tab in tabs.filter(t => t.display)" class="fake"></div>
            </div>
            <div class="tabs is-right">
                <slot ref="tabs" name="tabs">
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
        <div class="grow box">
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
            },
        },
        current: {
            type: String,
            required: true,
        },
    },
    methods: {
        onChangeTab(key) {
            this.$emit('change-tab', key);
        },
    },
};
</script>
<style lang="scss" scoped>
.box {
    height: 100%;
    padding: 0;
    overflow-y: auto;
    overflow-x: hidden;
    position: relative;
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
