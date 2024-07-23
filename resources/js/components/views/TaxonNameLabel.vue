<template>
    <span v-if="taxonName.rank">
        <template v-if="taxonName.rank.key === 'hybrid-formula'">
            <taxon-name-label :taxon-name="taxonName.hybridParents[0]"/>
            <author-name v-bind="{
                authors: taxonName.hybridParents[0].authors,
                exAuthors: taxonName.hybridParents[0].exAuthors,
                type: taxonName.hybridParents[0].nomenclature.group,
                originalTaxonName: taxonName.hybridParents[0].originalTaxonName,
                publishYear: taxonName.hybridParents[0].publishYear,
            }"/>
            ×
            <taxon-name-label :taxon-name="taxonName.hybridParents[1]"/>
            <author-name v-bind="{
                authors: taxonName.hybridParents[1].authors,
                exAuthors: taxonName.hybridParents[1].exAuthors,
                type: taxonName.hybridParents[1].nomenclature.group,
                originalTaxonName: taxonName.hybridParents[1].originalTaxonName,
                publishYear: taxonName.hybridParents[1].publishYear,
            }"/>
        </template>
        <template v-else>
            <span v-html="name"></span>
        </template>
    </span>
</template>
<script>
import { mapGetters } from 'vuex';
import AuthorName from '../AuthorName.vue';

export default {
    name: 'taxon-name-label',
    props: {
        taxonName: {
            type: Object,
            required: true,
        },
    },
    computed: {
        ...mapGetters({
            genusRank: 'rank/getGenusRank',
            speciesRank: 'rank/getSpeciesRank',
        }),
        name() {
            const t = this.taxonName;

            if (t.nomenclature.group === 'virus') {
                return `_${t.properties.latinName}_`.replace(/_(.*?)_/g, '<i>$1</i>');
            }

            // 種以下才可能有 speciesName
            const speciesName = [
                t.species?.properties?.latinGenus,
                t.species?.properties?.latinS1,
            ]
                .filter(Boolean)
                .join(' ');

            // 屬(包含)以上
            const latinName = t.rank.order < this.speciesRank.order ? t.properties.latinName : '';

            let prevName = speciesName || latinName || [
                t.properties.latinGenus,
                t.properties.latinS1,
            ]
                .filter(Boolean)
                .join(' ');

            // 雜交屬
            if (t.rank.order === this.genusRank.order && t.properties.isHybrid) {
                prevName = `× _${t.properties.latinName}_`;
            } else if (t.rank.order >= this.genusRank.order) { // 屬(包含)以下的學名斜體 (屬以上不斜題)
                prevName = prevName ? `_${prevName}_` : '';
            }

            if (t.rank?.key === 'species' && t.properties?.isHybrid) { // [種] 雜交種
                prevName = [
                    `_${t.properties?.latinGenus}_`,
                    `_${t.properties?.latinS1}_`,
                ].join(' × ');
            }

            // render sub layers
            const layers = t.speciesLayers?.map((layer, index) => {
                // 動物不用 s2rank (只要 s2latinName)
                if (index === 0 && t.nomenclature.group === 'animal' && layer.rank.abbreviation === "subsp.") {
                    return layer.latinName ? `_${layer.latinName}_` : '';
                }

                return [
                    `${layer.rank.abbreviation}`,
                    layer.latinName ? `_${layer.latinName}_` : '',
                ].filter(Boolean).join(' ');
            }).join(' ');

            return [
                prevName,
                layers,
            ]
                .filter(Boolean)
                .join(' ')
                .replace(/_(.*?)_/g, '<i>$1</i>');
        },
    },
    components: {
        AuthorName,
    },
};
</script>
