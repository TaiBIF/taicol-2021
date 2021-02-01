<template>
    <div v-if="pageStatus === $c.PAGE_IS_SUCCESS" class="container">
        <div class="header">
            <p class="has-text-orange title is-3">
                <!-- 屬以上的學名不斜體 -->
                <template v-if="taxonName.rank.order >= 30">
                    <i>{{ taxonName.name }}</i>
                </template>
                <template v-else>
                    {{ taxonName.name }}
                </template>
            </p>
            &nbsp;
            <author-name class="title is-3" v-bind="{
                authors: taxonName.authors,
                exAuthors: taxonName.exAuthors,
                type: taxonName.nomenclature.group,
                originalTaxonName: taxonName.originalTaxonName,
                publishYear: taxonName.publishYear,
            }"></author-name>

            <div class="tabs is-right">
                <ul>
                    <li :class="{'is-active': (currentTab === '#info' || currentTab === '')}"
                        v-on:click="onChangeTab('#info')">
                        <a>學名資訊</a>
                    </li>
                    <li v-if="hasAccepted" :class="{'is-active': currentTab === '#accepted'}"
                        v-on:click="onChangeTab('#accepted')">
                        <a>有效學名</a>
                    </li>
                    <li v-if="hasInReference" :class="{'is-active': currentTab === '#in-reference'}"
                        v-on:click="onChangeTab('#in-reference')">
                        <a>引用文獻</a>
                    </li>
                    <li v-if="hasHomonyms" :class="{'is-active': currentTab === '#homonym'}"
                        v-on:click="onChangeTab('#homonym')">
                        <a>同名</a>
                    </li>
                    <li v-if="hasSynonyms" :class="{'is-active': currentTab === '#synonym'}"
                        v-on:click="onChangeTab('#synonym')">
                        <a>異名</a>
                    </li>
                    <li v-if="hasSubTaxonNames" :class="{'is-active': currentTab === '#sub-taxon-names'}"
                        v-on:click="onChangeTab('#sub-taxon-names')">
                        <a>子階層學名</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="box">
            <div v-if="pageStatus === $c.PAGE_IS_SUCCESS && (currentTab === `#info` || currentTab === '')">
                <taxon-name-detail-view v-bind="taxonName" :has-title="false"/>
                <div class="container">
                    <div class="columns row">
                        <div class="column">
                            <router-link :to="`/taxon-names/${taxonName.id}/edit`" class="button is-small">編輯學名
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
            <taxon-name-accepted-view v-else-if="pageStatus === $c.PAGE_IS_SUCCESS && currentTab === `#accepted`" :type="taxonName.nomenclature.group"/>
            <taxon-name-synonym-view v-else-if="pageStatus === $c.PAGE_IS_SUCCESS && currentTab === `#synonym`"
                                     :taxon-name="taxonName"/>
            <taxon-name-homonym-view v-else-if="pageStatus === $c.PAGE_IS_SUCCESS && currentTab === `#homonym`"
                                     :taxon-name="taxonName"
            />
            <taxon-name-trivial-name v-else-if="pageStatus === $c.PAGE_IS_SUCCESS && currentTab === `#names`"
                                     taxon-name="taxonName"
            />
            <taxon-name-reference-view v-else-if="pageStatus === $c.PAGE_IS_SUCCESS && currentTab === `#in-reference`"/>
            <taxon-name-sub-taxon-names-view v-else-if="pageStatus === $c.PAGE_IS_SUCCESS && currentTab === `#sub-taxon-names`" :taxon-name="taxonName"/>
            <loading v-else></loading>

        </div>
    </div>
</template>
<script>
    import Breadcrumb from '../components/Breadcrumb';
    import TaxonNameDetailView from '../components/views/TaxonNameDetailView';
    import Loading from '../components/LoadingSection';
    import TaxonNameSynonymView from '../components/views/TaxonNameSynonymView';
    import TaxonNameHomonymView from '../components/views/TaxonNameHomonymView';
    import TaxonNameTrivialName from '../components/views/TaxonNameTrivialName';
    import TaxonNameReferenceView from '../components/views/TaxonNameReferenceView';
    import { openNotify } from '../utils';
    import AuthorName from '../components/AuthorName';
    import TaxonNameAcceptedView from '../components/views/TaxonNameAcceptedView';
    import TaxonNameSubTaxonNamesView from '../components/views/TaxonNameSubTaxonNamesView';

    export default {
        data() {
            return {
                pageStatus: this.$c.PAGE_IS_LOADING,
                currentTab: this.$route.hash,
                hasHomonyms: false,
                hasSynonyms: false,
                hasAccepted: false,
                hasInReference: false,
                hasSubTaxonNames: false,
                taxonName: {
                    formattedName: '',
                    usage: {},
                    authors: [],
                    exAuthors: [],
                },
            }
        },
        mounted() {
            this.fetchData();
        },
        watch: {
            '$route.params': {
                handler() {
                    this.fetchData();
                },
                immediate: true,
            }
        },
        methods: {
            fetchData() {
                this.axios
                    .get(`/taxon-names/${this.$route.params.id}`)
                    .then(({ data: { taxonName, hasHomonyms, hasSynonyms, hasAccepted, hasInReference, hasSubTaxonNames } }) => {
                        this.taxonName = taxonName;
                        this.taxonName.language = taxonName.language ? { id: taxonName.language } : null;
                        this.hasHomonyms = hasHomonyms;
                        this.hasSynonyms = hasSynonyms;
                        this.hasAccepted = hasAccepted;
                        this.hasInReference = hasInReference;
                        this.hasSubTaxonNames = hasSubTaxonNames;
                        this.pageStatus = this.$c.PAGE_IS_SUCCESS;

                        this.$store.commit('breadcrumb/SET_ITEMS', [{
                            url: '#',
                            name: this.taxonName.name,
                        }]);
                    })
                    .catch(({ status }) => {
                        if (status === 404) {

                        }
                    });
            },
            onCopy(text) {
                const app = this;
                navigator.clipboard.writeText(text).then(function () {
                    openNotify(app.$t('forms.copySuccess'), 'is-dark');
                }, function (err) {
                });
            },
            onChangeTab(value) {
                this.currentTab = value;
            },
        },
        components: {
            TaxonNameSubTaxonNamesView,
            TaxonNameAcceptedView,
            AuthorName,
            TaxonNameDetailView,
            TaxonNameSynonymView,
            TaxonNameHomonymView,
            TaxonNameTrivialName,
            TaxonNameReferenceView,
            Breadcrumb,
            Loading,
        },
    }
</script>
<style lang="scss" scoped>
    .container {
        .tabs {
            margin-bottom: 0;

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

        .title {
            margin-bottom: 1rem;
            display: inline-block;
        }

        .box {
            min-height: 70vh;

            .columns.row {
                padding: 2rem 5vw;
            }
        }
    }
</style>

