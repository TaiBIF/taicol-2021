<template>
    <draggable
        v-bind="dragOptions"
        :list="list"
        :move="onMove"
        :value="value"
        class="item-container"
        data-type="main"
        ghost-class="ghost"
        tag="div"
        @input="emitter"
    >
        <div v-for="(el,index) in realValue" :key="`${level}_${index}`"
             class="group"
        >
            <div class="usage-row">
                <div class="handle has-text-grey-lighter">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="usage-content" v-on:click="goUsage(el.id)">
                    <span :class="{'has-text-weight-bold': level === 1}"
                          class="utitle">{{ el.taxonName.name }}</span>
                    <span class="usubtitle">{{ el.taxonName.authors }}</span>
                </div>
                <button class="button is-pulled-right is-small is-text"
                        v-on:click="onRemove(el)"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nested-draggable v-if="level + 1 < 3"
                              :allow-children="false"
                              :empty-insert-threshold="100"
                              :level="level + 1"
                              :list="el.usages"
                              class="item-sub"
                              data-type="sub"
                              v-on:remove="onRemove"
            />
        </div>
    </draggable>
</template>

<script>
    import draggable from 'vuedraggable';

    export default {
        name: 'nested-draggable',
        methods: {
            emitter(value) {
                this.$emit('input', value);
            },
            onMove(e) {
                // 若是帶有「子項目」的項目不能移到已經是「子項目」的項目中
                if (e.draggedContext.element.usages.length && e.to.dataset.type === 'sub') {
                    return false;
                }
            },
            onRemove(item) {
                this.$emit('remove', item);
            },
            goUsage(usageId) {
                const { id } = this.$route.params;
                this.$router.push(`/namespaces/${id}/usages/${usageId}`);
            },
        },
        computed: {
            dragOptions() {
                return {
                    animation: 0,
                    group: 'description',
                    disabled: false,
                    ghostClass: 'ghost',
                    handle: '.handle',
                    threshold: 0,
                };
            },
            realValue() {
                return this.value ? this.value : this.list;
            },
        },
        props: {
            value: {
                required: false,
                type: Array,
                default: null,
            },
            list: {
                required: false,
                type: Array,
                default: null,
            },
            level: {
                type: Number,
                default: 1,
            },
        },
        components: {
            draggable,
        },
    };
</script>
<style lang="scss" scoped>

    .ghost {
        opacity: 80%;
        background-color: whitesmoke;

        .title {
            display: none;
        }
    }

    .group {
        .usage-row {
            border: 1px solid $light-grey;
            padding: .25rem 1rem;
            margin-bottom: .8rem;
            cursor: pointer;
            min-height: 1rem;
            display: flex;
            box-shadow: 0 0.25em .5em -0.125em rgba(85, 85, 85, 0.1), 0 0px 0 1px rgba(85, 85, 85, 0.02);

            .utitle {
                color: $orange;
            }

            .handle {
                margin-right: .5rem;
            }

            .usage-content {
                flex-grow: 1;
            }
        }

        .item-sub {
            margin: 0 0 0 1rem;
            color: lightgray;

            .usage-row {
                .utitle {
                    color: lightgray;
                }
            }
        }

        &.sortable-chosen {
            .usage-row {
                background: gray;
                color: white;

                .utitle {
                    color: white;
                }
            }
        }
    }

    .title {
        margin-left: 1rem;
    }


</style>
