<template>
    <div class="container">
        <div class="body">
            <div class="columns">
                <div class="column is-7">
                    <reference-view v-bind="reference" :show-import="true"></reference-view>
                </div>
                <div class="column is-5">
                    <div>
                        <p class="title is-5 is-inline-block">本文獻分類群及其異名表</p>
                        <div class="title buttons is-pulled-right is-inline-block">
                            <router-link class="button is-small is-text"
                                         :to="`/references/${this.$route.params.id}/usages`">檢視異名表</router-link>
                        </div>
                    </div>
                    <div class="box has-background-light usage-container">
                        <template v-for="(usages, index) in usageGroups">
                            <template v-for="(usage, index) in usages">
                                <div :class="{
                                         'is-title': usage.isTitle,
                                         'is-indent': usage.isIndent,
                                     }" class="usage-row"
                                     tabindex="-1"
                                >
                                    <router-link :to="`/taxon-names/${usage.taxonName.id}`">
                                        <div class="usage-content">
                                            <span class="utitle">
                                                <!-- 屬以上的學名不斜體 -->
                                                <template v-if="usage.taxonName.rank.order >= 30">
                                                    <i>{{ usage.taxonName.name }}</i>
                                                </template>
                                                <template v-else>
                                                    {{ usage.taxonName.name }}
                                                </template>
                                            </span>
                                            <author-name v-bind="{
                                                authors: usage.taxonName.authors,
                                                exAuthors: usage.taxonName.exAuthors,
                                                type: usage.taxonName.nomenclature.group,
                                                originalTaxonName: usage.taxonName.originalTaxonName,
                                                publishYear: usage.taxonName.publishYear,
                                            }">
                                            </author-name>
                                        </div>
                                    </router-link>
                                </div>
                            </template>
                            <hr/>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import Breadcrumb from '../components/Breadcrumb';
    import ReferenceView from '../components/views/ReferenceView';
    import { subTitle, title } from '../utils/preview/reference';
    import AuthorName from '../components/AuthorName';

    export default {
        data() {
            return {
                usageGroups: [],
                reference: {
                    id: 0,
                    type: 0,
                    authors: [],
                    publishYear: '',
                    title: '',
                    subtitle: '',
                    language: null,
                    note: '',
                    properties: {},
                },
            }
        },
        mounted() {
            this.axios.get(`/references/${this.$route.params.id}`)
                .then(({ data: { data } }) => {
                    this.reference = data;
                    this.reference.id = parseInt(this.$route.params.id);
                    this.reference.language = data.language ? { id: data.language } : null;
                    this.reference.title = title(data);
                    this.reference.subtitle = subTitle(data);
                }).catch(({ status }) => {

                if (status === 404) {
                }
            });

            this.axios.get(`/references/${this.$route.params.id}/usages`)
                .then(({ data: { data } }) => {
                    this.usageGroups = data;
                });
        },
        methods: {
            goTaxonName(id) {
                this.$router.push(`/taxon-names/${id}`);
            }
        },
        components: {
            AuthorName,
            ReferenceView,
            Breadcrumb,
        },
    }
</script>
<style lang="scss" scoped>
    .container {
        padding-top: 1rem;

        .body {
            padding-top: 1rem;
        }
    }

    hr {
        background-color: lightgray;
        height: 1px;
    }

    .usage-container {
        min-height: 100%;
    }

    .usage-row {
        padding: .25rem 1rem;
        margin-bottom: .2rem;
        cursor: pointer;
        min-height: 1rem;
        display: flex;

        &:focus, &.selected {
            outline: none;
            background: $light-grey;
        }

        &.is-title {
            font-weight: bold;
        }

        &.is-indent {
            margin-left: 2rem;
        }

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
</style>

