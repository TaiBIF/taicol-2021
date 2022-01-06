<template>
    <div class="container">
        <div class="body">
            <div class="columns">
                <div class="column is-7">
                    <reference-view v-bind="reference" :show-import="true"></reference-view>
                </div>
                <div class="column is-5">
                    <div>
                        <p class="text-xl font-bold pt-2 is-5 is-inline-block">本文獻分類群及其異名表</p>
                        <router-link :to="`/references/${this.$route.params.id}/usages`"
                                     class="button is-text float-right">檢視異名表
                        </router-link>
                    </div>
                    <div class="box has-background-light usage-container mt-5">
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
                                            <span v-if="(usage.status === '' || usage.status === 'accepted') && usage.taxonName.rank.order < speciesRank.order && !usage.isIndent" class="font-bold">
                                                {{ usage.taxonName.rank.display['en-us'] }}
                                            </span>
                                            <usage-preview
                                                ref="nameRemark"
                                                :indications="getIndications(usage.properties.indications)"
                                                :is-simple="true"
                                                :per-usages="usage.perUsages"
                                                :status="usage.status"
                                                :taxon-name="usage.taxonName"
                                                :type-specimens="usage.typeSpecimens"
                                                :type-name="usage.typeName"
                                                class="inline-block"
                                            />
                                        </div>
                                    </router-link>
                                </div>
                            </template>
                            <hr class="my-4" v-if="parseInt(index) !== usageGroupsCount"/>
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
    import UsagePreview from '../components/UsagePreview';
    import indications from '../components/selects/indications';
    import { mapGetters } from "vuex";

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
            getIndications(indicationArray) {
                return indicationArray?.map((abbreviation) => {
                    return indications.find(i => i.abbreviation === abbreviation);
                }).filter(Boolean);
            },
        },
        computed: {
            ...mapGetters({
                speciesRank: 'rank/getSpeciesRank',
            }),
            usageGroupsCount() {
                return Object.values(this.usageGroups);
            }
        },
        components: {
            UsagePreview,
            AuthorName,
            ReferenceView,
            Breadcrumb,
        },
    }
</script>
<style lang="scss" scoped>
    a {
        color: $black;
    }

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

