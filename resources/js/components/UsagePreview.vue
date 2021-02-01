<template>
    <p>
        <!--  學名  -->
        <span class="is-orange">
           <!-- 屬以上的學名不斜體 -->
            <template v-if="taxonName.rank.order >= 30">
                <i>{{ taxonName.name }}</i>
            </template>
            <template v-else>
                {{ taxonName.name }}
            </template>
        </span>

        <!-- 有效和無效 -->
        <template v-if="status === 'accepted' || status === 'not-accepted'">
            <!--  命名者  -->
            <author-name v-bind="{
                authors: taxonName.authors,
                exAuthors: taxonName.exAuthors,
                type: taxonName.nomenclature.group,
                originalTaxonName: taxonName.originalTaxonName,
            }"></author-name><!--
                命名者和文獻之間的逗號(解決 span 之間的空格)
             --><span>,&nbsp;</span>

            <!--  引用文獻  -->
            <span v-text="refPreview ? `${refPreview}. ` : ''"></span>

            <!--  標註   -->
            <template v-for="(indication, index) in indications">
                <span :class="{
                    'has-text-success': status === 'accepted',
                    'has-text-danger': status === 'not-accepted'
                    }">{{ indication.abbreviation }}</span><template v-if="index !== indications.length - 1">, </template>
            </template>
        </template>

        <!-- -------------- 未決 ----------- -->
        <template v-else-if="status === 'undetermined'">
            <span class="has-text-danger">?</span>

            <!--  引用文獻  -->
            <span v-text="refPreview ? `${refPreview}: ` : ''"></span>
        </template>

        <!-- -------------- 誤用 ----------- -->
        <template v-else-if="status === 'misapplied'">
            <!--  標註   -->
            <template v-for="(indication, index) in indications">
                <span class="has-text-danger">{{ indication.abbreviation }}</span><template v-if="index !== indications.length - 1">, </template>
            </template>

            <!--  命名者  -->
            <author-name v-bind="{
                authors: taxonName.authors,
                exAuthors: taxonName.exAuthors,
                type: taxonName.nomenclature.group,
                originalTaxonName: taxonName.originalTaxonName,
            }"></author-name>:

            <!--  引用文獻  -->
            <span v-text="refPreviewWithoutPublishReference ? `${refPreviewWithoutPublishReference}. ` : ''"></span>
        </template>

        <template v-else>
            <!--  標註   -->
            <template v-for="(indication, index) in indications">
                <span :class="{
                    'has-text-success': status === 'accepted',
                    'has-text-danger': status === 'not-accepted'
                    }">{{ indication.abbreviation }}</span><template v-if="index !== indications.length - 1">, </template>
            </template>

            <!--
                命名者：動物不需要年份
            -->
            <author-name v-bind="{
                authors: taxonName.authors,
                exAuthors: taxonName.exAuthors,
                type: taxonName.nomenclature.group,
                originalTaxonName: taxonName.originalTaxonName,
            }"></author-name><!--
                命名者和文獻之間的逗號(解決 span 之間的空格)
             --><span>,&nbsp;</span>

            <!--  引用文獻  -->
            <span v-text="refPreview ? `${refPreview}. ` : ''"></span>
        </template>

        <!--   模式    -->
        <span v-text="Scombo(typeSpecimens)"></span>
    </p>
</template>
<script>
    import { combo as Scombo } from '../utils/preview/typeSpecimen';
    import AuthorName from './AuthorName';
    import { comboAnimal, comboPlant, factory as rFactory } from '../utils/preview/reference';
    import { comboLast } from '../utils/preview/person';

    export default {
        components: { AuthorName },
        props: {
            status: {
                type: String,
                required: true,
            },
            taxonName: {
                type: Object,
                required: true,
            },
            indications: {
                type: Array,
                required: true,
            },
            typeSpecimens: {
                type: Array,
                required: true,
            },
            perUsages: {
                type: Array,
                required: true,
            },
        },
        computed: {
            refPreview() {
                if (this.taxonName.nomenclature?.group === 'animal') {
                    return [
                        comboAnimal([{
                            figure: this.taxonName.properties.usage.figure,
                            showPage: this.taxonName.properties.usage.showPage,
                            target: this.taxonName.reference,
                        }], () => {}),
                        comboAnimal(this.perUsages, comboLast),
                    ]
                        .filter(Boolean)
                        .join('; ');
                } else {
                    return [
                        comboPlant([{
                            figure: this.taxonName.properties.usage.figure,
                            showPage: this.taxonName.properties.usage.showPage,
                            target: this.taxonName.reference,
                        }], () => {}),
                        comboPlant(this.perUsages, comboLast),
                    ]
                        .filter(Boolean)
                        .join('; ');
                }
            },
            refPreviewWithoutPublishReference() {
                const rCombo = rFactory(this.taxonName.nomenclature?.group);

                return [
                    rCombo(this.perUsages, comboLast),
                ]
                    .filter(Boolean)
                    .join('; ');
            },
        },
        methods: {
            Scombo: Scombo,
        }
    }
</script>
<style lang="scss" scoped>
    .is-orange {
        color: $orange;
    }
</style>
