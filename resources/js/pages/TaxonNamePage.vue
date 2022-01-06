<template>
    <page :preload="onPreload">
        <tab-content :tabs="tabs" v-on:change-tab="onChangeTab" :current="currentTab">
            <template v-slot:title>
                <taxon-name-full-label :taxon-name="taxonName" :with-color="true"/>
                {{ commonName ? commonName.name : '' }}
            </template>
            <template v-slot:content>
                <detail-view  v-if=" currentTab === `info` || currentTab === ''" v-bind="taxonName"/>
                <accepted-view v-if="currentTab === `accepted`" :type="taxonName.nomenclature.group"/>
                <synonym-view v-if="currentTab === `synonym`" :taxon-name="taxonName"/>
                <homonym-view v-if="currentTab === `homonym`" :taxon-name="taxonName"/>
                <trivial-name v-if="currentTab === `names`" :taxon-name="taxonName"/>
                <reference-view v-if="currentTab === `in-reference`"/>
                <sub-taxon-names-view v-if="currentTab === `sub-taxon-name`" :taxon-name="taxonName"/>
                <common-names-view v-if="currentTab === `common-name`" :taxon-name-id="taxonName.id"></common-names-view>
            </template>
        </tab-content>
    </page>
</template>
<script>
    import DetailView from '../components/views/taxonName/DetailView';
    import SynonymView from '../components/views/taxonName/SynonymView';
    import HomonymView from '../components/views/taxonName/HomonymView';
    import TrivialName from '../components/views/taxonName/TrivialName';
    import ReferenceView from '../components/views/taxonName/ReferenceView';
    import AcceptedView from '../components/views/taxonName/AcceptedView';
    import SubTaxonNamesView from '../components/views/taxonName/SubTaxonNamesView';
    import TaxonNameFullLabel from '../components/views/TaxonNameFullLabel';
    import TabContent from '../components/layout/TabContent';
    import Page from './Page';
    import CommonNamesView from "../components/views/taxonName/CommonNamesView";

    export default {
        data() {
            return {
                currentTab: this.$route.hash,
                tabs: [
                    {
                        key: 'info',
                        title: '學名資訊',
                        default: true,
                        display: true,
                    },
                    {
                        key: 'accepted',
                        title: '有效學名',
                        display: false,
                    },
                    {
                        key: 'in-reference',
                        title: '引用文獻',
                        display: false,
                    },
                    {
                        key: 'homonym',
                        title: '同名',
                        display: false,
                    },
                    {
                        key: 'synonym',
                        title: '異名',
                        display: false,
                    },
                    {
                        key: 'sub-taxon-name',
                        title: '子階層學名',
                        display: false,
                    },
                    {
                        key: 'common-name',
                        title: '俗名',
                        display: false,
                    },
                ],
                commonName: null,
                taxonName: {
                    usage: {},
                    authors: [],
                    exAuthors: [],
                },
            }
        },
        mounted() {
            this.currentTab = this.$route.hash.replace('#', '');
        },
        methods: {
            async onPreload() {
                try {
                    const {
                        data: {
                            taxonName,
                            commonName,
                            tabs,
                        },
                    } = await this.axios.get(`/taxon-names/${this.$route.params.id}`);

                    this.taxonName = taxonName;
                    this.commonName = commonName;

                    tabs.forEach(({ key, display }) => {
                        this.onToggle(key, display);
                    });

                    return 200;
                } catch (e) {
                    return status;
                }
            },
            onChangeTab(key) {
                this.currentTab = key;
                this.$router.replace({ hash: key });
            },
            onToggle(key, status) {
                const tab = this.tabs.find(t => t.key === key);
                if (tab) {
                    tab.display = status;
                }
            },
        },
        components: {
            CommonNamesView,
            Page,
            TabContent,
            TaxonNameFullLabel,
            SubTaxonNamesView,
            AcceptedView,
            DetailView,
            SynonymView,
            HomonymView,
            TrivialName,
            ReferenceView,
        },
    }
</script>

