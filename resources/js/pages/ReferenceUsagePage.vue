<template>
    <div class="container">
        <div class="header section">
            <label class="title is-5">文獻&nbsp;&nbsp;</label>
            <span class="title is-5">{{ reference.title }}</span>
            <router-link :to="referenceUrl" class="button is-text is-pulled-right">檢視簡易異名表</router-link>
        </div>
        <div class="body">
            <div class="usage-container">
                <template v-for="(usages, index) in usageGroups">
                    <div class="group">
                        <template v-for="(usage, index) in usages">
                            <div :class="{'is-title': usage.isTitle}" class="usage-row"
                            >
                                <div :class="{'is-indent': usage.isIndent}" class="usage-content">
                                    <template v-if="usage.isTitle">
                                        <span class="utitle" v-html="usage.taxonName.name"></span>
                                        <span v-if="!usage.isTitle" class="status is-pulled-right">{{
                                                usage.status
                                            }}</span>
                                    </template>
                                    <template v-else>
                                        <div class="usage-content">
                                            <div v-if="usage.customNameRemark"
                                                  v-html="usage.customNameRemark"/>
                                            <div v-else-if="usage.nameRemark"
                                                  v-html="usage.nameRemark"/>
                                            <div v-else>
                                                <span class="taxon-name">{{ usage.taxonName.name }}</span>
                                                <span class="usubtitle">{{  usage.taxonName.formattedAuthors }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
    import { combo as Scombo } from '../utils/preview/typeSpecimen';
    import { factory as rFactory, subTitle, title } from '../utils/preview/reference';

    export default {
        data() {
            return {
                usageGroups: [],
                reference: {},
                referenceUrl: `/references/${this.$route.params.id}`,
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

                    this.formStatus = this.$c.PAGE_IS_SUCCESS;
                    this.$store.commit('breadcrumb/SET_ITEMS', [{
                        url: this.referenceUrl,
                        name: `文獻: ${this.reference.title}`,
                    }, {
                        url: '#',
                        name: `詳細異名表`,
                    }]);
                });

            this.axios.get(`/references/${this.$route.params.id}/usages`)
                .then(({ data: { data } }) => {
                    this.usageGroups = data;
                });


        },
        methods: {
            specimenCombo: (t) => Scombo(t),
            refPreview(usage) {
                const rCombo = rFactory(usage.taxonName.nomenclature.group);
                const result = [
                    usage.taxonName.properties.referenceName,
                    rCombo(usage.perUsages)
                ].filter(Boolean).join('; ');
                return result ? `${result}. ` : '';
            },

        },
    }
</script>

<style lang="scss" scoped>
    .body {
        margin-top: 1rem;
    }

    .group {
        background: $light-grey;
        margin-bottom: .5rem;

        .usage-row {
            border-bottom: 1px solid lightgrey;
            padding: .5rem 2rem;

            .usage-content {
                &.is-indent {
                    margin-left: 2rem;
                }
                .tag {
                    margin-left: .5rem;
                }
            }
        }
    }
</style>
