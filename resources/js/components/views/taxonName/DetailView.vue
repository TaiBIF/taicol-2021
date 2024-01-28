<template>
    <div class="container">
        <div class="columns row">
            <div class="column is-6">
                <div class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.rank')"/>
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
                           v-text="
                           $t(`taxonName.author.${nomenclature ? nomenclature.settings.keyOfAuthors : 'authors'}`)
                        "/>
                    </div>
                    <div class="column is-9">
                        <p v-for="author in authors">
                            <router-link :key="`author_${author.id}`"
                                         :to="{name: 'person-page', params: {id:author.id}}"
                                         class="my-link"
                                         v-text="`${renderPersonFullName(author)}`"/>
                        </p>
                        <p v-if="properties.authorsName">
                            {{ properties.authorsName }}
                        </p>
                    </div>
                </div>

                <!--    前述者/提出此名者   -->
                <div v-if="exAuthors.length" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.exAuthor')"/>
                    </div>
                    <div class="column is-9">
                        <p v-for="author in exAuthors">
                            <router-link :key="`author_${author.id}`"
                                         :to="{name: 'person-page', params: {id:author.id}}"
                                         class="my-link"
                                         v-text="`${renderPersonFullName(author)}`"/>
                        </p>
                    </div>
                </div>

                <div class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.reference')"/>
                    </div>
                    <div class="column is-9">
                        <router-link v-if="reference" :to="{name: 'reference-page', params: {id:reference.id}}"
                                     class="my-link"
                                     target="_blank">
                            {{ r(reference) }}
                        </router-link>
                        <p v-else v-text="properties.referenceName"></p>
                    </div>
                </div>

                <!--  文獻中學名寫法   -->
                <div v-if="usage.nameInReference" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.nameInReference')"/>
                    </div>
                    <div class="column is-9">
                        <p v-text="usage.nameInReference"></p>
                    </div>
                </div>

                <!--  原始組合名/基礎名   -->
                <div v-if="originalTaxonName" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t(`taxonName.originalName.${nomenclature.settings.keyOfOriginalName}`)"/>
                    </div>
                    <div class="column is-9">
                        <router-link :to="{name: 'taxon-name-page', params: {id: originalTaxonName.id}}"
                                     class="my-link">
                            <taxon-name-full-label :taxon-name="originalTaxonName" :with-color="false"/>
                        </router-link>
                    </div>
                </div>

                <div v-if="typeSpecimens.length" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.specimenType')"/>
                    </div>
                    <div class="column is-9">
                        <p v-for="specimen in typeSpecimens">
                            {{ showTypeSpecimen(specimen) }}
                        </p>
                    </div>
                </div>

                <div v-if="typeName" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.typeName')"/>
                    </div>
                    <div class="column is-9">
                        <router-link :to="{name: 'taxon-name-page', params: {id: typeName.id}}" class="my-link">
                            <taxon-name-full-label :taxon-name="typeName"></taxon-name-full-label>
                        </router-link>
                    </div>
                </div>

                <div v-if="hybridParents.length" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.hybridFormula')"/>
                    </div>
                    <div class="column is-9">
                        <router-link :to="{name: 'taxon-name-page', params: {id: hybridParents[0].id}}" class="my-link">
                            <taxon-name-full-label :taxon-name="hybridParents[0]"/>
                        </router-link>
                        ×
                        <router-link :to="{name: 'taxon-name-page', params: {id: hybridParents[1].id}}" class="my-link">
                            <taxon-name-full-label :taxon-name="hybridParents[1]"/>
                        </router-link>
                    </div>
                </div>
                <div v-if="properties.genomeComposition" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.genomeComposition')"/>
                    </div>
                    <div class="column is-9" v-text="properties.genomeComposition ">
                    </div>
                </div>
                <div v-if="properties.host" class="columns">
                    <div class="column is-3">
                        <p class="has-text-weight-bold"
                           v-text="$t('taxonName.host')"/>
                    </div>
                    <div class="column is-9" v-text="properties.host">
                    </div>
                </div>
                <div v-if="note" class="columns">
                    <div class="column is-3"
                         v-text="$t('taxonName.note')"/>
                    <div class="column is-9" v-text="note">
                    </div>
                </div>
                <br/>
                <br/>
                <div class="columns">
                    <div class="column is-3">
                        <router-link :to="{name: 'taxon-name-edit', params:{id}}" class="button">
                            {{ $t('common.edit') }}
                        </router-link>
                    </div>
                </div>
            </div>
            <div class="column is-6">
                <div>
                    <div v-if="parents.length" class="columns">
                        <div class="column is-3">
                            <p class="has-text-weight-bold"
                               v-text="$t('taxonName.higherTaxa')"/>
                        </div>
                        <div class="column is-9">
                            <p v-for="parent in parents">
                                <router-link :to="{name: 'taxon-name-page', params: {id: parent.id}}" class="my-link">
                                    {{ parent.rank.display['en-us'] }}
                                    <taxon-name-full-label :taxon-name="parent"/>&nbsp;{{ parent.commonNameTw }}
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
import AuthorName from '../../AuthorName.vue';
import { subTitle } from '../../../utils/preview/reference';
import TaxonNameLabel from '../TaxonNameLabel.vue';
import TaxonNameFullLabel from '../TaxonNameFullLabel.vue';

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
        };
    },
    mounted() {
        this.axios.get(`/taxon-names/${this.$route.params.id}/parents`)
            .then(({ data: { data } }) => {
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
    },
};
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
