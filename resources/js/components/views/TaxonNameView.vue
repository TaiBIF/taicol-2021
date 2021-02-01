<template>
    <div class="container">
        <p v-if="hasTitle" class="title is-2 has-text-centered">
            <span class="taxon-name"
                  v-html="formattedName.replace(/_([a-zA-Z]*)_/g, '<i>$1</i>')"/>

            <span v-text="formattedAuthors"/>
        </p>
        <div>
            <div class="columns row">
                <div class="column is-7">
                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.nomenclature')"/>
                        <div class="column is-9">
                            <p v-text="nomenclatureFormatted"></p>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.rank')"/>
                        <div class="column is-9">
                            <p v-text="rankFormatted"></p>
                        </div>
                    </div>
                    <hr>

                    <!--    學名作者   -->
                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t(`forms.taxonName.author.${nomenclature ? nomenclature.settings.keyOfAuthors : 'authors'}`)"/>
                        <div class="column is-9">
                            <p v-for="author in authors"
                               v-text="`${renderPersonName(author)}`"/>
                        </div>
                    </div>

                    <!--    前述者/提出此名者   -->
                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.exAuthor')"/>
                        <div class="column is-9">
                            <p v-for="author in exAuthors"
                               v-text="`${renderPersonName(author)}`"
                            />
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.reference')"/>
                        <div class="column is-9">
                            <p v-if="usage.reference" v-text="usage.reference.subtitle"></p>
                            <p v-else-if="!hasReference" v-text="referenceName"></p>
                        </div>
                    </div>
                    <div v-if="note" class="columns">
                        <div class="column is-3"
                             v-text="$t('forms.taxonName.note')"/>
                        <div class="column is-9" v-text="note">
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="columns">
                    </div>
                    <div class="columns">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { fullName } from '../../utils/preview/person';

    export default {
        props: {
            hasTitle: {
                type: Boolean,
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
            usage: {
                type: Object | Array,
            },
            hasReference: {
                type: Boolean,
                required: true,
            },
            referenceName: {
                type: String,
                required: true,
            },
            originalTaxonName: {
                type: Object,
            },
            note: {
                type: String,
            },
        },
        computed: {
            nomenclatureFormatted() {
                return this.nomenclature ? `${this.nomenclature.display['zh-tw']} (${this.nomenclature.name})` : '';
            },
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
            renderPersonName(person) {
                return fullName(person);
            },
        },
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
