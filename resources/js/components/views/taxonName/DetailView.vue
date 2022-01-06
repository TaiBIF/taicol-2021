<template>
    <div class="container">
        <div class="columns row">
            <div class="column is-6">
                <div class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="'分類階層'"/>
                    </div>
                    <div class="column is-9">
                        <p v-text="rankFormatted"></p>
                    </div>
                </div>
                <hr>

                <!--    學名作者   -->
                <div class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t(`forms.taxonName.author.${nomenclature ? nomenclature.settings.keyOfAuthors : 'authors'}`)"/>
                    </div>
                    <div class="column is-9">
                        <p v-for="author in authors">
                            <router-link :key="`author_${author.id}`"
                                         :to="`/persons/${author.id}`"
                                         class="my-link"
                                         v-text="`${renderPersonFullName(author)}`"/>
                        </p>
                    </div>
                </div>

                <!--    前述者/提出此名者   -->
                <div v-if="exAuthors.length" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('forms.taxonName.exAuthor')"/>
                    </div>
                    <div class="column is-9">
                        <p v-for="author in exAuthors">
                            <router-link :key="`author_${author.id}`"
                                         :to="`/persons/${author.id}`"
                                         class="my-link"
                                         v-text="`${renderPersonFullName(author)}`"/>
                        </p>
                    </div>
                </div>

                <div class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('forms.taxonName.reference')"/>
                    </div>
                    <div class="column is-9">
                        <router-link class="my-link" v-if="reference" target="_blank" :to="`/references/${reference.id}`">
                            {{ r(reference) }}
                        </router-link>
                        <p v-else v-text="properties.referenceName"></p>
                    </div>
                </div>

                <!--  文獻中學名寫法   -->
                <div class="columns" v-if="usage.nameInReference">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('forms.taxonName.nameInReference')"/>
                    </div>
                    <div class="column is-9">
                        <p v-text="usage.nameInReference"></p>
                    </div>
                </div>

                <!--  原始組合名/基礎名   -->
                <div class="columns" v-if="originalTaxonName">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t(`forms.taxonName.originalName.${nomenclature.settings.keyOfOriginalName}`)"/>
                    </div>
                    <div class="column is-9">
                        <router-link class="my-link" :to="`/taxon-names/${originalTaxonName.id}`">
                            <taxon-name-full-label :taxon-name="originalTaxonName" :with-color="false"/>
                        </router-link>
                    </div>
                </div>

                <div class="columns" v-if="typeSpecimens.length">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('forms.taxonName.specimenType')"/>
                    </div>
                    <div class="column is-9">
                        <p v-for="specimen in typeSpecimens">
                            {{ showTypeSpecimen(specimen) }}
                        </p>
                    </div>
                </div>

                <div class="columns" v-if="typeName">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('forms.taxonName.typeName')"/>
                    </div>
                    <div class="column is-9">
                        <router-link class="my-link" :to="`/taxon-names/${typeName.id}`">
                            <taxon-name-full-label :taxon-name="typeName"></taxon-name-full-label>
                        </router-link>
                    </div>
                </div>

                <div class="columns" v-if="hybridParents.length">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('forms.taxonName.hybridFormula')"/>
                    </div>
                    <div class="column is-9">
                        <router-link class="my-link" :to="`/taxon-names/${hybridParents[0].id}`">
                            <taxon-name-full-label :taxon-name="hybridParents[0]" />
                        </router-link>
                        ×
                        <router-link class="my-link" :to="`/taxon-names/${hybridParents[1].id}`">
                            <taxon-name-full-label :taxon-name="hybridParents[1]" />
                        </router-link>
                    </div>
                </div>

                <div class="columns" v-if="note">
                    <div class="column is-3"
                         v-text="$t('forms.taxonName.note')"/>
                    <div class="column is-9" v-text="note">
                    </div>
                </div>
                <br/>
                <br/>
                <div class="columns">
                    <div class="column is-3">
                        <router-link :to="`/taxon-names/${id}/edit`" class="button">
                            編輯學名
                        </router-link>
                    </div>
                </div>
            </div>
            <div class="column is-6">
                <div>
                    <div class="columns" v-if="parents.length">
                        <div class="column is-3">
                            <p class="has-text-weight-bold"
                               v-text="'較高分類階層'"/>
                        </div>
                        <div class="column is-9">
                            <p v-for="parent in parents">
                                <router-link :to="`/taxon-names/${parent.id}`" class="my-link">
                                    <taxon-name-full-label :taxon-name="parent" />&nbsp;{{ parent.commonNameTw }}
                                </router-link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { combo } from '../../../utils/preview/typeSpecimen';
    import { fullName } from '../../../utils/preview/person';
    import AuthorName from '../../AuthorName';
    import { subTitle } from '../../../utils/preview/reference';
    import TaxonNameLabel from '../TaxonNameLabel';
    import TaxonNameFullLabel from '../TaxonNameFullLabel';

    export default {
        components: { TaxonNameFullLabel, TaxonNameLabel, AuthorName },
        props: {
            id: {
                type: Number,
                required: true,
            },
            nomenclature: {
                type: Object,
            },
            rank: {
                type: Object,
            },
            authors: {
                type: Array,
                required: true,
            },
            exAuthors: {
                type: Array,
                required: true,
            },
            reference: {
                type: Object,
            },
            usage: {
                type: Object | Array,
            },
            properties: {
                type: Object,
                required: true,
            },
            typeSpecimens: {
                type: Array,
                required: true,
            },
            typeName: {
                type: Object,
            },
            hybridParents: {
                type: Array,
            },
            originalTaxonName: {
                type: Object,
            },
            note: {
                type: String,
            },
        },
        computed: {
            rankFormatted() {
                return this.rank ? `${this.rank.display['zh-tw']} (${this.rank.display['en-us']})` : '';
            },
        },
        data() {
            return {
                showBook: false,
                parents: [],
            }
        },
        mounted() {
            this.axios.get(`/taxon-names/${ this.$route.params.id }/parents`)
                .then(({data: {data}}) => {
                    this.parents = data;
                });
        },
        methods: {
            showTypeSpecimen: (specimen) => combo([specimen]),
            renderPersonFullName(person, type) {
                return fullName(person);
            },
            r(r) {
                r.properties.pagesRange = '';
                return [subTitle(r), this.usage.showPage].filter(Boolean).join(': ');
            },
        }
    }
</script>
<style lang="scss" scoped>
    .container {
        height: 100%;
    }

    .columns {
        margin: 0;
        &.row {
            padding: 2.5rem 0;
            height: 100%;
        }
    }
</style>
