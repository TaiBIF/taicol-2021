<template>
    <table class="table is-fullwidth">
        <thead>
        <tr>
            <th :colspan="columns.length + 1" class="tfooter">
                <label> 共 {{ total }} 筆</label>
            </th>
        </tr>
        <tr>
            <th v-for="column in columns" class="clickable" v-on:click="onChangeSort(column)">
                {{ column.label }}
                <div class="sort-button" v-if="column.sortable">
                    <i class="fas fa-sort-up" v-if="sortDirection === 'asc' && sortColumn === column.field"></i>
                    <i class="fas fa-sort-down" v-else-if="sortDirection === 'desc' && sortColumn === column.field"></i>
                    <i class="fas fa-sort" v-else="sortDirection === ''"></i>
                </div>
            </th>
            <th></th>
        </tr>
        <tr>
            <th v-for="column in columns">
                <slot :name="`column-th-${column.field}`"
                      v-bind:column="column"
                      v-bind:params="params"
                >
                    <input
                        class="input is-small"
                        type="text"
                        v-if="column.searchable"
                        v-model="params[column.field]"
                    />
                </slot>
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(datumn, index) in data">
            <td v-for="column in columns">
                <slot :name="`column-${column.field}`"
                      v-bind:datumn="datumn"
                      v-bind:index="index"
                >
                    {{ getDescendantProp(datumn, column.field)}}
                </slot>
            </td>
            <td>
                <slot name="action"
                      v-bind:datumn="datumn"
                />
            </td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
            <th :colspan="columns.length + 1" class="tfooter">
                <label> 共 {{ total }} 筆</label>
            </th>
        </tr>
        </tfoot>
    </table>
</template>
<script>
    import { debounce } from 'lodash';
    export default {
        props: {
            columns: {
                type: Array,
                required: true,
            },
            data: {
                type: Array,
                default: () => [],
            },
            total: {
                type: Number,
                default: 0,
            },
            currentPage: {
                type: Number,
                default: 1,
            },
            perPage: {
                type: Number,
                default: 100,
            },
        },
        watch: {
            params: {
                handler(value) {
                    this.onChangeSearchParams(value);
                },
                deep: true
            }
        },
        data() {
            return {
                params: {},
                sortColumn: '',
                sortDirection: '',
            }
        },
        methods: {
            getDescendantProp(obj, desc) {
                var arr = desc.split(".");
                while(arr.length && (obj = obj[arr.shift()]));
                return obj;
            },
            onChangeSort(column) {
                const { field, sortable } = column;
                if (!sortable) {
                    return;
                }

                if (field !== this.sortColumn) {
                    this.sortDirection = 'asc';
                } else {
                    this.sortDirection = this.sortDirection === 'desc' ? 'asc' : 'desc';
                }
                this.sortColumn = field;
                this.$emit('sort-change', field, this.sortDirection);
            },
            onChangePage(v) {
                this.$emit('page-change', v);
            },
            onChangeSearchParams: debounce(function () {
                this.$emit('params-change', this.params);
            }),
        }
    }
</script>
<style lang="scss">
    .clickable {
        cursor: pointer;
        .sort-button {
            display: inline-block;
        }
    }

    .tfooter {
        label {
            display: inline-block;
        }

        .pagination-container {
            display: inline-block;
            float: right;
        }
    }
</style>
