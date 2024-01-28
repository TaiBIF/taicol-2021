<template>
    <div class="px-4 py-2 form">
        <div class="body flex">
            <!-- taxon view -->
            <div class="flex-1 p-4">
                <p class="mb-3 font-bold">{{ $t('collect.nameView') }}</p>
                <div v-for="referenceUsages in usagesGroupByReference">
                    <div v-if="referenceUsages.length !== 0 && referenceUsages[0].reference" class="mb-3 font-bold">
                        {{ referenceUsages[0].reference.subtitle }}
                    </div>
                    <div class="item-container draggable-container mb-5">
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
                                    class="flex-1"
                                />

                                <div class="buttons bg-white bg-opacity-25 is-right" style="min-width: 100px">
                                    <router-link
                                        :to="{name: 'taxon-name-page', params: {id: usage.taxonName.id}}"
                                        class="g-button is-small mr-2">
                                        <i class="fas fa-external-link-alt"></i>
                                    </router-link>

                                    <a class="close-button is-small"
                                       v-on:click="e => onRemove(1, usage.id)">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="item-container draggable-container">
                    <div v-for="item in taxonNames"
                         class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                        <div v-if="!item.content" class="flex-1">
                            <p class="italic text-gray-400">{{ $t('collect.notExist') }}</p>
                        </div>
                        <taxon-name-full-label v-else :key="item.id" :taxon-name="item.content" :with-color="true"
                                               class="flex-1"></taxon-name-full-label>
                        <div class="buttons bg-white bg-opacity-25 is-right" style="min-width: 100px">
                            <router-link
                                :to="{name: 'taxon-name-page', params: {id: item.content.id}}"
                                class="g-button is-small mr-2"
                            >
                                <i class="fas fa-external-link-alt"></i>
                            </router-link>

                            <a class="close-button is-small"
                               v-on:click="e => onRemove(2, item.content.id, item.id)">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- reference view -->
            <div class="flex-1 p-4">
                <p class="mb-3 font-bold">{{ $t('collect.referenceView') }}</p>
                <div v-for="g in usageGroupByTaxonName" class="mb-5">
                    <div v-if="g.length !== 0 && g[0].content[0].taxonNameId" class="mb-3">
                        <taxon-name-full-label :taxon-name="g[0].content[0].taxonName"
                                               :with-color="true"></taxon-name-full-label>
                    </div>
                    <div class="item-container draggable-container">
                        <div v-for="r in g">
                            <div class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                                <div class="flex-1">
                                    {{ r.reference.subtitle }}
                                </div>
                                <div class="buttons bg-white bg-opacity-25 is-right" style="min-width: 100px">
                                    <router-link
                                        :to="{name: 'reference-page', params: {id: r.reference.id}}"
                                        class="g-button is-small mr-2"
                                    >
                                        <i class="fas fa-external-link-alt"></i>
                                    </router-link>

                                    <a class="close-button is-small"
                                       v-on:click="e => onRemove(1, r.content[0].id, r.id)">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="item-container draggable-container">
                    <div v-for="r in references"
                         class="border border-gray-100 px-4 py-1 mb-3 shadow-md flex cursor-pointer">
                        <div v-if="!r" class="flex-1">
                            <p class="italic text-gray-400">{{ $t('collect.notExist') }}</p>
                        </div>
                        <span v-else class="flex-1">
                            {{ r.subtitle }}
                        </span>

                        <div class="buttons bg-white bg-opacity-25 is-right" style="min-width: 100px">
                            <router-link
                                :to="{name: 'reference-page', params: {id: r.id}}"
                                class="g-button is-small mr-2"
                            >
                                <i class="fas fa-external-link-alt"></i>
                            </router-link>
                            <a class="close-button is-small"
                               v-on:click="e => onRemove(3, r.id)">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style lang="scss" scoped>
.form {
    height: calc(100% - 1rem);
}
</style>
<script>
import { groupBy } from 'lodash';
import TaxonNameFullLabel from '../TaxonNameFullLabel.vue';
import UsagePreview from '../../UsagePreview.vue';
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
        };
    },
    computed: {
        taxonNames() {
            return (this.itemGroup[2] || []).sort(
                (a, b) => (a.content?.name > b.content?.name) - (a.content?.name < b.content?.name),
            );
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
        onRemove(type, id, itemId) {
            if (confirm('確定要刪除此收藏 ?')) {
                this.axios.delete(`/favorite-folders/${this.folder.id}/items/${itemId}`, {
                    params: {
                        type,
                        id,
                    },
                }).then(() => {
                    this.fetchFolderData();
                });
            }
        },
        getIndications(indicationArray) {
            return indicationArray ? indicationArray.map(
                (abbreviation) => indications.find((i) => i.abbreviation === abbreviation),
            ).filter(Boolean) : [];
        },
        fetchFolderData() {
            this.axios.get(`/favorite-folders/${this.folder.id}/items`)
                .then(({ data: { data } }) => {
                    this.itemGroup = data;
                    this.$forceUpdate();
                });
        },
    },
    mounted() {
        this.fetchFolderData();
    },
    components: { UsagePreview, TaxonNameFullLabel },
};
</script>
