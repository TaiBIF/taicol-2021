<template>
    <div class="container">
        <p class="title is-2 has-text-centered" v-if="hasTitle">
            <span class="taxon-name"
                  v-html="formattedName.replace(/_([a-zA-Z]*)_/g, '<i>$1</i>')"/>
            <span v-text="formattedAuthors"/>
        </p>
        <div>
            <div class="columns row">
                <div class="column is-7">
                    <div class="columns">
                        <div class="column is-3"
                             v-text="'分類階層'"/>
                        <div class="column is-9">
                            <p v-text="rankFormatted"></p>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-3"
                             v-text="'學名地位'"/>
                        <div class="column is-9">
                            <p v-text=""></p>
                        </div>
                    </div>
                    <hr>

                    <!--    學名作者   -->
                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t(`forms.taxonName.author.${nomenclature ? nomenclature.settings.keyOfAuthors : 'authors'}`)"/>
                        <div class="column is-9">
                            <p v-for="author in authors"
                               v-text="`${renderPersonFullName(author)}`"/>
                        </div>
                    </div>

                    <!--    前述者/提出此名者   -->
                    <div class="columns" v-if="exAuthors.length">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.exAuthor')"/>
                        <div class="column is-9">
                            <p v-for="author in exAuthors"
                               v-text="`${renderPersonFullName(author)}`"
                            />
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.reference')"/>
                        <div class="column is-9">
                            <router-link class="my-link" v-if="reference" target="_blank" :to="`/references/${reference.id}`">
                                {{ r(reference) }}
                            </router-link>
                            <p v-else v-text="properties.referenceName"></p>
                        </div>
                    </div>

                    <!--  文獻中學名寫法   -->
                    <div class="columns" v-if="usage.nameInReference">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.nameInReference')"/>
                        <div class="column is-9">
                            <p v-text="usage.nameInReference"></p>
                        </div>
                    </div>

                    <!--  原始組合名/基礎名   -->
                    <div class="columns" v-if="originalTaxonName">
                        <div class="column is-3"
                             v-text="$t(`forms.taxonName.originalName.${nomenclature.settings.keyOfOriginalName}`)"/>
                        <div class="column is-9">
                            <router-link class="my-link" :to="`/taxon-names/${originalTaxonName.id}`">
                                <span v-text="`${originalTaxonName.name}`"/>
                                <author-name v-bind="{
                                    authors: originalTaxonName.authors,
                                    exAuthors: originalTaxonName.exAuthors,
                                    type: nomenclature.group,
                                    publishYear: originalTaxonName.publishYear,
                                }"></author-name>
                            </router-link>
                        </div>
                    </div>

                    <div class="columns" v-if="typeSpecimens.length">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.specimenType')"/>
                        <div class="column is-9">
                            <p v-for="specimen in typeSpecimens">
                                {{ showTypeSpecimen(specimen) }}
                            </p>
                        </div>
                    </div>
                    <div class="columns" v-if="hybridParents.length">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.hybridFormula')"/>
                        <div class="column is-9">
                            <p>{{ hybridParents.map(p => p.name).join(' x ')}}</p>
                        </div>
                    </div>
                    <div class="columns" v-if="note">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.note')"/>
                        <div class="column is-9" v-text="note">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { combo } from '../../utils/preview/typeSpecimen';
    import { fullName } from '../../utils/preview/person';
    import AuthorName from '../AuthorName';
    import { subTitle } from '../../utils/preview/reference';

    export default {
        components: { AuthorName },
        props: {
            hasTitle: {
                type:Boolean,
                default: true,
            },
            formattedName: {
                type: String,
            },
            formattedAuthors: {
                type: String,
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
            hybridParents: {
                type: Array,
            },
            originalTaxonName: {
                type: Object,
            },
            note: {
                type: String,
            }
        },
        computed: {
            rankFormatted() {
                return this.rank ? `${this.rank.display['zh-tw']} (${this.rank.display['en-us']})` : '';
            },
        },
        data() {
            return {
                showBook: false,
            }
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
    .edit {
        position: absolute;
        right: 0;
    }

    .title {
        margin-bottom: 2rem;
    }

    .columns {
        &.row {
            padding: 2rem 5vw;
        }
    }
</style>
