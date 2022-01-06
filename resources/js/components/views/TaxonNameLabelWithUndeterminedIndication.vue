<template>
    <span>
        <template v-if="taxonName.rank.key === 'hybrid-formula'">
            <taxon-name-label :taxon-name="taxonName.hybridParents[0]" :indication="indication"/>
            <author-name v-bind="{
                authors: taxonName.hybridParents[0].authors,
                exAuthors: taxonName.hybridParents[0].exAuthors,
                type: taxonName.hybridParents[0].nomenclature.group,
                originalTaxonName: taxonName.hybridParents[0].originalTaxonName,
                publishYear: taxonName.hybridParents[0].publishYear,
            }"/>
            ×
            <taxon-name-label :taxon-name="taxonName.hybridParents[1]" :indication="indication"/>
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
    import AuthorName from '../AuthorName';

    export default {
        name: 'taxon-name-label',
        props: {
            taxonName: {
                type: Object,
                required: true,
            },
            indication: {
                type: String,
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

                // 屬(包含)以下的學名斜體 (屬以上不斜題)
                const isItalic = t.rank.order >= this.genusRank.order;

                // 種以下才可能有 speciesName
                const speciesName = t.species ? [
                    `_${t.species?.properties?.latinGenus}_`,
                    (
                        !t.species?.speciesLayers.length &&
                        t.species?.properties?.latinGenus &&
                        t.species?.properties?.latinS1 ? `<span class="has-text-danger">${this.indication}</span>` : ''
                    ),
                    `_${t.species?.properties?.latinS1}_`,
                ]
                    .filter(Boolean)
                    .join(' ') : '';

                // 種以上
                const latinName = t.rank.order < this.speciesRank.order ? [
                    `<span class="has-text-danger">${this.indication}</span>`,
                    isItalic ? `_${t.properties?.latinName}_` : t.properties?.latinName,
                ].filter(Boolean).join(' ') : '';

                let prevName = speciesName || latinName || [
                    `_${t.properties.latinGenus}_`,
                    !t.speciesLayers.length ? `<span class="has-text-danger">${this.indication}</span>` : '',
                    `_${t.properties.latinS1}_`,
                ]
                    .filter(Boolean)
                    .join(' ');

                if (t.rank?.key === 'species' && t.properties?.isHybridFormula) { // [種] 雜交種
                    prevName = [
                        `_${t.properties?.latinGenus}_`,
                        `_${t.properties?.latinS1}_`,
                    ].join(' × ');
                }

                // render sub layers
                const layers = t.speciesLayers?.map((layer, index) => {
                    // 動物不用 s2rank (只要 s2latinName)
                    if (index === 0 && t.nomenclature.group === 'animal') {
                        return layer.latinName ? `_${layer.latinName}_` : '';
                    }

                    if (index === t.speciesLayers.length - 1) {
                        return [
                            `${layer.rank.abbreviation}`,
                            `<span class="has-text-danger">${this.indication}</span>`,
                            layer.latinName ? `_${layer.latinName}_` : '',
                        ].filter(Boolean).join(' ');
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
    }
</script>
