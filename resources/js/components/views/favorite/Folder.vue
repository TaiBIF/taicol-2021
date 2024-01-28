<template>
    <div class="px-4 py-2 form">
        <div class="tabs m-0">
            <ul>
                <li :class="{'is-active': contentType === 'taxon-name'}" v-on:click="() => contentType = 'taxon-name'">
                    <a>{{ $t('collect.v.name') }}</a></li>
                <li :class="{'is-active': contentType === 'reference'}" v-on:click="() => contentType = 'reference'">
                    <a>{{ $t('collect.v.reference') }}</a>
                </li>
            </ul>
        </div>
        <div class="overflow-y-auto body">
            <!-- taxon view -->
            <div v-if="contentType === 'taxon-name'">
                <div v-for="referenceUsages in usagesGroupByReference">
                    <div v-if="referenceUsages.length !== 0 && referenceUsages[0].reference" class="mb-3 font-bold">
                        {{ referenceUsages[0].reference.subtitle }}
                    </div>
                    <draggable id="favorite-usage" :clone="cloneUsages"
                               :list="referenceUsages"
                               class="item-container draggable-container mb-5"
                               data-type="usage"
                               tag="div"
                               v-bind="dragOptions">
                        <div v-for="(item, index) in referenceUsages">
                            <div v-if="!item.content"
                                 class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                                <p class="italic text-gray-400">{{ $t('collect.notExist') }}</p>
                            </div>
                            <div v-for="usage in item.content" v-else
                                 :class="{'ml-8': usage.isIndent}"
                                 class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer"
                            >
                                <usage-preview
                                    ref="nameRemark"
                                    :indications="getIndications(usage.properties.indications)"
                                    :is-simple="true"
                                    :per-usages="usage.perUsages"
                                    :status="usage.status"
                                    :taxon-name="usage.taxonName"
                                    :type-name="usage.typeName"
                                    :type-specimens="usage.typeSpecimens"
                                />
                            </div>
                        </div>
                    </draggable>
                </div>

                <draggable :clone="cloneTaxonNameToUsage" :list="itemGroup[2]"
                           class="item-container draggable-container"
                           data-type="taxon-name"
                           tag="div"
                           v-bind="dragTOptions">
                    <div v-for="item in taxonNames"
                         class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                        <div v-if="!item.content">
                            <p class="italic text-gray-400">{{ $t('collect.notExist') }}</p>
                        </div>
                        <taxon-name-full-label v-else :key="item.id" :taxon-name="item.content"
                                               :with-color="true"></taxon-name-full-label>
                    </div>
                </draggable>
            </div>

            <!-- reference view -->
            <div v-else-if="contentType === 'reference'">
                <div v-for="g in usageGroupByTaxonName" class="mb-5">
                    <div v-if="g.length !== 0 && g[0].content[0].taxonNameId" class="mb-3">
                        <taxon-name-full-label :taxon-name="g[0].content[0].taxonName"
                                               :with-color="true"></taxon-name-full-label>
                    </div>
                    <draggable :clone="cloneUsageReference" :list="g"
                               class="item-container draggable-container"
                               data-type="reference"
                               tag="div"
                               v-bind="dragReferenceOptions">
                        <div v-for="r in g">
                            <div class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                                {{ r.reference.subtitle }}
                            </div>
                        </div>
                    </draggable>
                </div>

                <draggable id="favorite-reference" :clone="cloneReference"
                           :list="references"
                           class="item-container draggable-container"
                           data-type="reference"
                           tag="div"
                           v-bind="dragReferenceOptions">
                    <div v-for="r in references"
                         class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                        <div v-if="!r">
                            <p class="italic text-gray-400">{{ $t('collect.notExist') }}</p>
                        </div>
                        <span v-else>{{ r.subtitle }}</span>
                    </div>
                </draggable>
            </div>
        </div>
    </div>
</template>
<style lang="scss" scoped>
.form {
    height: calc(100% - 1rem);
}

.body {
    height: calc(100% - 7rem);
}
</style>
<script>
import draggable from 'vuedraggable';
import { groupBy } from 'lodash';
import TaxonNameFullLabel from '../TaxonNameFullLabel';
import UsagePreview from '../../UsagePreview';
import indications from '../../selects/indications';

export default {
    props: {
        folder: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            contentType: 'taxon-name',
            itemGroup: [],
            dragReferenceOptions: {
                animation: 0,
                sort: false,
                group: {
                    name: 'favorite-reference',
                    pull: 'clone',
                },
                disabled: false,
                selectedClass: 'selected',
            },
            dragTOptions: {
                animation: 0,
                sort: false,
                group: {
                    name: 'favorite-taxon-name',
                    pull: 'clone',
                },
                disabled: false,
                selectedClass: 'selected',
            },
            dragOptions: {
                animation: 0,
                sort: false,
                group: {
                    name: 'favorite-usage',
                    pull: 'clone',
                },
                disabled: false,
                selectedClass: 'selected',
            },
        };
    },
    computed: {
        taxonNames() {
            return (this.itemGroup[2] || []).sort((a, b) => (a.content?.name > b.content?.name) - (a.content?.name < b.content?.name));
        },
        usagesGroupByReference() {
            return Object.values(groupBy(
                (this.itemGroup[1] || []),
                'referenceId',
            )).map((r) => r.sort((a, b) => {
                const aa = a.conten ? a.content[0] : {};
                const bb = b.content ? b.content[0] : {};
                return (aa.taxonName?.name > bb.taxonName?.name) - (aa.taxonName?.name < bb.taxonName?.name);
            }));
        },
        usageGroupByTaxonName() {
            return groupBy(
                (this.itemGroup[1] || [])
                    .filter((item) => item.reference)
                    .sort((a, b) => a.reference.publishYear - b.reference.publishYear),
                (u) => u.content[0].taxonNameId,
            );
        },
        references() {
            const allReferences = (this.itemGroup[3] || [])?.map((i) => i.content)
                .reduce((result, curr) => {
                    const key = curr?.id;
                    if (result[key] === undefined) {
                        result[key] = curr;
                    }
                    return result;
                }, {});

            return Object.values(allReferences).sort((a, b) => a.publishYear - b.publishYear);
        },
    },
    methods: {
        cloneTaxonNameToUsage(item) {
            const taxonName = item.content;
            return {
                customNameRemark: '',
                isIndent: false,
                isTitle: false,
                nameRemark: '',
                perUsages: [],
                properties: { indications: [] },
                status: 'accepted',
                taxonName,
                typeName: taxonName.typeName,
                typeSpecimens: taxonName.typeSpecimens,
            };
        },
        cloneUsages(u) {
            const data = u.content.map((i) => {
                delete i.id;
                return i;
            });
            return {
                ...data[0],
                child: data.slice(1),
            };
        },
        cloneUsageReference(r) {
            console.log(r);
            return {
                target: r.reference,
                showPage: '',
                figure: '',
                nameRemark: '',
                proParte: false,
                isFromPublishedRef: false,
            };
        },
        cloneReference(r) {
            return {
                target: r,
                showPage: '',
                figure: '',
                nameRemark: '',
                proParte: false,
                isFromPublishedRef: false,
            };
        },
        getIndications(indicationArray) {
            return indicationArray ? indicationArray.map((abbreviation) => indications.find((i) => i.abbreviation === abbreviation)).filter(Boolean) : [];
        },
    },
    mounted() {
        this.axios.get(`/favorite-folders/${this.folder.id}/items`)
            .then(({ data: { data } }) => {
                this.itemGroup = data;
                this.$forceUpdate();
            });
    },
    components: { UsagePreview, TaxonNameFullLabel, draggable },
};
</script>
