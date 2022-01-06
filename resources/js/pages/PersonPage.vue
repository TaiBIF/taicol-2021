<template>
    <page :preload="preload">
        <tab-content :tabs="tabs" v-on:change-tab="onChangeTab" :current="currentTab">
            <template v-slot:title>
                {{ person.fullName }}
            </template>
            <template v-slot:content>
                <info :person="person" v-show="currentTab === 'info' || currentTab === ''"></info>
                <references v-show="currentTab === 'reference'" v-on:hide="onHideTab"></references>
                <taxon-names v-show="currentTab === 'taxon-name'" v-on:hide="onHideTab"></taxon-names>
                <type-specimens v-show="currentTab === 'type-specimen'" v-on:hide="onHideTab"></type-specimens>
            </template>
        </tab-content>
    </page>
</template>
<script>
    import AuthorName from '../components/AuthorName';
    import Page from './Page';
    import TabContent from '../components/layout/TabContent';
    import Info from '../components/views/person/Info';
    import References from '../components/views/person/References';
    import TaxonNames from '../components/views/person/TaxonNames';
    import TypeSpecimens from '../components/views/person/TypeSpecimens';

    export default {
        data() {
            return {
                person: null,
                currentTab: null,
                tabs: [
                    {
                        key: 'info',
                        title: '人名資訊',
                        default: true,
                        display: true,
                    },
                    {
                        key: 'taxon-name',
                        title: '命名學名',
                        display: true,
                    },
                    {
                        key: 'reference',
                        title: '發表文獻',
                        display: true,
                    },
                    {
                        key: 'type-specimen',
                        title: '採集標本',
                        display: true,
                    },
                ],
            }
        },
        mounted() {
            this.currentTab = this.$route.hash.replace('#', '');
        },
        methods: {
            async preload() {
                try {
                    const { data } = await this.axios.get(`/persons/${this.$route.params.id}`)
                    this.person = data;
                    return 200;
                } catch (e) {
                    return e.status;
                }
            },
            onChangeTab(key) {
                this.currentTab = key;
                this.$router.replace({ hash: key });
            },
            onHideTab(key) {
                const tab = this.tabs.find(t => t.key === key);
                if (tab) {
                    tab.display = false;
                }
            },
        },
        components: {
            TypeSpecimens,
            TaxonNames,
            References,
            Info,
            TabContent,
            Page,
            AuthorName,
        },
    }
</script>

