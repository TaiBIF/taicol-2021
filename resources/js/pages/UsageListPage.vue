<template>
    <div class="flex flex-col">
        <div class="w-full mb-2 sticky top-0 z-10">
            <div class="text-xl font-bold pl-5 inline-block">
                <template v-if="$route.meta.type === 'reference'">
                    {{ $t('reference.editNameArea') }}
                </template>
                <template v-else>
                    {{ $t('namespace.checklistEditArea') }} 
                </template>
                <tooltip>
                    <i class="fas fa-info-circle"></i>
                    <template v-slot:body>
                        <div class="w-[700px] z-10">
                            <img :src="usageInfoImagePath" height="auto" width="800px">
                        </div>
                    </template>
                </tooltip>

            </div>
            <div v-if="!isLoading" class="buttons float-right inline-block">
                <button
                    :class="{
                            'has-background-grey': isShowTaxonName,
                            'has-text-white': isShowTaxonName,
                            'has-background-grey-lighter': !isShowTaxonName
                        }"
                    class="button namespace-button"
                    v-on:click="onToggleTaxonNameField"
                >
                    {{ $t('namespace.insertNameCard') }}
                </button>
                <button class="button namespace-button"
                        v-on:click="onToggleSimpleForm">
                    {{ isListSimple ? $t('namespace.listDetail') : $t('namespace.listSimple') }}
                </button>                
                <button v-if="configs.type === 'namespace'" class="button namespace-button"
                        v-on:click="onOpenPropertiesModal">
                    {{ $t('namespace.setAllProperties') }}
                </button>
                <button v-if="configs.type === 'namespace'"
                        class="button namespace-button"
                        v-on:click="onImportUsages">
                    {{ $t('namespace.importUsages') }}
                </button>
                <button v-if="configs.type === 'namespace'"
                        class="button namespace-button"
                        v-on:click="onExportUsages">
                    {{ $t('namespace.exportUsages') }}
                </button>

                <button v-if="configs.type === 'namespace'"
                        class="button namespace-button"
                        v-on:click="onDownloadDoc">
                    {{ $t('namespace.downloadDoc') }}
                </button>
                <button v-if="configs.type === 'namespace'"
                        class="button namespace-button"
                        v-on:click="onClearNamespace">
                    {{ $t('namespace.clearNamespace') }}
                </button>
                <button v-if="configs.type === 'namespace'" class="button namespace-button"
                        v-on:click="onOpenPublishingModal">
                    {{ $t('namespace.publishingTool') }}
                </button>

            </div>
        </div>
        <div id="usage-container" class="grow flex flex-col z-0">
            <div v-if="isShowTaxonName" class="border-b bg-white sticky top-0 z-50">
                <div class="field is-horizontal px-4 py-4">
                    <taxon-name-select v-model="newTaxonName"
                                       class="taxon-name-column"/>
                    &nbsp;
                    <button class="button" v-on:click="onAddTaxonName">{{ $t('namespace.add') }}</button>
                </div>
                <div v-if="newTaxonName" class="w-full bg-white px-4 pb-4 flex shadow-md">
                    <status-select v-model="newTaxonNameStatus" class="w-[100px] mr-4"></status-select>
                    <div v-if="newTaxonNameStatus === 'accepted'" class="flex">
                        <div class="flex items-center">
                            <span class="font-bold mr-2">
                                {{ $t('usage.isInTaiwan') }}
                            </span>
                        </div>
                        <div class="buttons has-addons">
                            <radio-button v-model="newTaxonNameIsInTaiwan"
                                          :label="$t('usage.yes')" :v="1"
                            />
                            <radio-button v-model="newTaxonNameIsInTaiwan"
                                          :label="$t('usage.unknown')" :v="2"
                            />
                            <radio-button v-model="newTaxonNameIsInTaiwan"
                                          :label="$t('usage.no')" :v="0"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div ref="usageContainer"
                 class="form-body bg-white shadow-md p-0 w-full overflow-y-auto grow">
                <div id="usage-content-container" class="p-4 min-h-full">
                    <draggable :list="usages" class="item-container draggable-container"
                               ghost-class="dragging"
                               data-type="main"
                               handle=".handle"
                               tag="div"
                               v-bind="dragOptions"
                               v-on:change="onChange"
                               @start="onDragStart"
                               @end="onDragEnd"
                               >
                        <div v-for="(usage,index) in usages">
                            <div 
                                v-if="!usage.isDeleted"
                                :key="`usage_${index}`"
                                :class="{
                                        'is-title': usage.isTitle,
                                        'is-indent': usage.isIndent,
                                        'bg-red-50': isInvalidUsage(usage, index),
                                    }"
                                :data-index="index"
                                class="usage-row bg-white"
                                tabindex="1"
                                v-on:keypress.tab.prevent="(e) => e.preventDefault()"
                                v-on:keydown.tab.prevent="(e) => onTab(e, index)"
                                v-on:keyup.tab.prevent="(e) => e.preventDefault()">
                                <span class="handle"></span>
                                <div class="usage-content" v-on:dblclick="() => goUsage(usage)">
                                    <status-dot :status="usage.status"/>
                                    <!-- <template v-if="usage.nameRemark && !isListSimple && !usage.isTitle"> -->
                                    <template v-if="!isListSimple && !usage.isTitle">
                                        <p v-if="usage.customNameRemark"
                                        v-html="usage.customNameRemark"/>
                                        <usage-preview
                                            v-else
                                            ref="nameRemark"
                                            :indications="getIndications(usage.properties.indications)"
                                            :per-usages="usage.perUsages"
                                            :status="usage.status"
                                            :taxon-name="usage.taxonName"
                                            :type-name="usage.typeName"
                                            :type-specimens="usage.typeSpecimens"
                                            :common-names="usage.properties.commonNames"
                                        />
                                    </template>
                                    <template v-else>
                                        <usage-preview
                                            ref="nameRemark"
                                            :indications="getIndications(usage.properties.indications)"
                                            :is-simple="true"
                                            :per-usages="usage.perUsages"
                                            :status="usage.status"
                                            :taxon-name="usage.taxonName"
                                            :type-name="usage.typeName"
                                            :type-specimens="usage.typeSpecimens"
                                            :common-names="usage.properties.commonNames"
                                        />
                                    </template>
                                </div>
                                <div class="is-right">
                                    <usage-property-short-tags :p="usage.properties"></usage-property-short-tags>
                                </div>
                                <div class="buttons bg-white bg-opacity-25 is-right  ">

                                    <a v-if="!isUsageFormSimple" v-show="!usage.isIndent"
                                    class="button is-small is-text"
                                    v-on:click="e => onToggleTitle(e, index)"
                                    >
                                        {{ $t('namespace.usageTitle') }}
                                    </a>
                                    <a class="close-button is-small"
                                    v-on:click="e => onRemove(e, index)">
                                    </a>
                                </div>
                            </div>
                            <!-- 如果是某個group的最後一個usage則顯示 -->
                            <usage-property-export-tags v-if="isLastGroupElement(index,usages,usage.group)" class="d-none" :p="returnAccetpedUsageProp(usage.group, usages)"></usage-property-export-tags>
                            <div class="accepted-prop" :class="`accepted-prop-${usage.group}`" v-show="!isListSimple && isLastGroupElement(index,usages,usage.group)">
                                <!-- 從這邊去抓accepted usage的資料 -->
                                <div v-html="returnUsageProp(returnAccetpedUsageProp(usage.group, usages))"></div>
                            </div>
                        </div>
                    </draggable>
                </div>
            </div>
            <div class="sticky bottom-0 bg-white p-4 w-full border-t">
                <div class="buttons is-right">
                    <button class="button m-0"
                        v-on:click="goBack()"
                        v-text="$t('common.goBack')"/>
                    <a :class="{ 'is-loading': isLoading }"
                       class="button"
                       v-on:click="onSave"
                       v-text="$t('common.complete')"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import draggable from 'vuedraggable';
import TaxonNameSelect from '../components/selects/TaxonNameSelect.vue';
import { openNotify } from '../utils';
import AuthorName from '../components/AuthorName.vue';
import UsagePreview from '../components/UsagePreview.vue';
import Tooltip from '../components/Tooltip.vue';
import indications from '../components/selects/map/indications';
import downloadUsageHtmlToDoc from '../utils/downloadUsageHtmlToDoc';
import StatusDot from '../components/StatusDot.vue';
import UsagePropertyShortTags from '../components/views/UsagePropertyShortTags.vue';
import UsagePropertyExportTags from '../components/views/UsagePropertyExportTags.vue';
import { NamespaceType } from '../constants/namespace';
import StatusSelect from '../components/selects/StatusSelect.vue';
import RadioButton from '../components/RadioButton.vue';

export default {
    data() {
        return {
            isLoading: true,
            isListSimple: false,
            isShowTaxonName: true,
            newTaxonName: null,
            model: null,
            usages: [],
            configs: null,

            newTaxonNameStatus: 'accepted',
            newTaxonNameIsInTaiwan: 1,
        };
    },
    beforeMount() {
        this.loadConfigs();
    },
    mounted() {
        this.refresh();

        const { items } = this.$store.state.breadcrumb;

        if (this.$route.meta.type === 'reference' && items[items.length - 1]?.type === 'reference-usages-list') {
            items.splice(-1);
            this.$store.commit('breadcrumb/SET_ITEMS', items);
        }

    },
    computed: {
        usageInfoImagePath() {
            return this.$i18n.locale() === 'zh-tw' ?
                '/images/usage-info.png' : '/images/usage-info-eng.png';
        },
        dragOptions() {
            return {
                animation: 0,
                group: {
                    name: 'usages',
                    pull: false,
                    put: ['favorite-usage', 'favorite-taxon-name'],
                },
                disabled: false,
                selectedClass: 'selected',
            };
        },
        isUsageFormSimple() {
            if (this.configs.type === 'namespace') {
                return this.model.type === NamespaceType.SIMPLE;
            }
            return false;
        },
    },
    methods: {
        goBack(){
            window.history.back();
        },
        onDragStart(event){
            let index = event.oldIndex; 
            let now_group = this.usages[index].group;
            var list;
            list = document.querySelectorAll(`.accepted-prop-${now_group}`);
            for (var i = 0; i < list.length; ++i) {
            list[i].classList.add('d-none');
            }
        },
        onDragEnd(event){
            let index = event.oldIndex; 
            let now_group = this.usages[index].group;
            var list;
            list = document.querySelectorAll(`.accepted-prop-${now_group}`);
            for (var i = 0; i < list.length; ++i) {
            list[i].classList.remove('d-none');
            }
        },
        isLastGroupElement(index,usages,group){
           return index === usages.map(e => e.group).lastIndexOf(group)
        },
        returnUsageProp(accptedUsageProp){

            let propStr = '';

            if (accptedUsageProp != null){

                // 前面加上其他屬性 for 匯出word檔使用
                // usage-property-short-tags 裡面的

                if (accptedUsageProp?.distributionInTw){
                    propStr += `<p><i>Distribution in Taiwan.</i> ${accptedUsageProp.distributionInTw}</p>`;
                }

                if (accptedUsageProp?.alienStatusNote){
                    propStr += `<p><i>Distribution Note.</i> ${accptedUsageProp.alienStatusNote}</p>`;
                }

                if (accptedUsageProp?.additionalFields?.length){
                    let additionalFields = accptedUsageProp.additionalFields;
                    for (var i = 0; i < additionalFields.length; ++i) {
                        let str = additionalFields[i].fieldName;
                        let title = str[0].toUpperCase() + str.slice(1);
                        title = title.replace(/([A-Z])/g, ' $1').trim()

                        propStr += `<p><i>${title}.</i> ${additionalFields[i].fieldValue}</p>`;
                    }
                }

                if (accptedUsageProp?.customFields?.length){
                    let customFields = accptedUsageProp.customFields;
                    for (var i = 0; i < customFields.length; ++i) {
                        let str = customFields[i].fieldNameEn;
                        propStr += `<p><i>${str[0].toUpperCase() + str.slice(1)}.</i> ${customFields[i].fieldValue}</p>`;
                    }
                }

                if (accptedUsageProp?.note){
                    propStr += `<p><i>Note.</i> ${accptedUsageProp.note}</p>`;
                }
            }

            return propStr

        },
        returnAccetpedUsageProp(group, usages){

            let accptedUsage = usages.filter(item => item.group === group && item.status=='accepted' )[0];
            if (accptedUsage?.properties != null){
                return accptedUsage.properties
            } else {
                return {}
            }

        },
        isInvalidUsage(usage, index) {

            let now_usages = this.usages.filter(item => item.isDeleted !== true);

            // # 1. 同一個分類群有一個以上的接受名 -> 感覺在介面上不會出現
            if (now_usages.filter(item => item.group === usage.group && item.status === 'accepted' && item.isTitle === false).length > 1){
                console.log(usage.taxonName.name, ': 同一個分類群有一個以上的接受名')
                return true;
            }

            // # 2. 同一個分類群裡面沒有任何接受名 -> 介面上應該會直接跳錯誤

            let accepted_usages = now_usages.filter(item => item.group === usage.group && item.status === 'accepted' && item.isTitle === false).length
            // 除非有相同的name
            let accepted_usages_2 = now_usages.filter(item => item.taxonName.name === usage.taxonName.name && item.status === 'accepted' && item.isTitle === false).length

            if (accepted_usages == 0 && accepted_usages_2 == 0){
                console.log(usage.taxonName.name, ': 同一個分類群裡面沒有任何接受名')
                return true;
            }

            // # 3. 同一個學名出現在同一篇文獻中的兩個分類群(不同accepted_taxon_name_id) 且不是誤用
            if (now_usages.filter(item => item.taxonNameId === usage.taxonNameId && item.group !== usage.group && item.status !== 'misapplied' && item.isTitle === false).length > 1){
                console.log(usage.taxonName.name, ': 同一個學名出現在同一篇文獻中的兩個分類群(不同accepted_taxon_name_id) 且不是誤用')
                return true;
            }

            // # 一組 reference_id, accepted_taxon_name_id, taxon_name_id, 只對到一個ru_id -> 在my namespace usage還不會有accepted_taxon_name_id, reference_id
            if (now_usages.filter(item => item.taxonNameId === usage.taxonNameId && item.status === 'accepted' && item.isTitle === false).length  > 1){
                console.log(usage.taxonName.name, ': 重複的accepted name')
                return true;
            }


            // 合理：
            // autonym 可以在同一篇文獻：兩個同時有效。

            // 不合理：
            if (usage.taxonName.objectGroup!=null){

                // autonym / 同模：同一篇文獻 在不同分類群 同時出現 accepted和not-acceped

                if (now_usages.filter(item => item.taxonName.objectGroup === usage.taxonName.objectGroup && item.group !== usage.group && item.status != usage.status && item.status !== 'misapplied' && item.isTitle === false).length  > 0){
                    console.log(usage.taxonName.name, ': autonym / 同模同一篇文獻 在不同分類群 同時出現 accepted和not-acceped');
                    return true;
                }

                // autonym / 同模：同一篇文獻中有多個not-accepted在不同分類群。

                if (now_usages.filter(item => item.taxonName.objectGroup === usage.taxonName.objectGroup && item.group !== usage.group && item.status === 'not-accepted').length  > 1){
                    console.log(usage.taxonName.name, ': autonym / 同模同一篇文獻中有多個not-accepted在不同分類群。');
                    return true;
                }


                // 同模（不包含autonym）：同一篇文獻中多個accepted
                if (usage.taxonName.autonymGroup==null){

                    if (now_usages.filter(item => item.taxonName.objectGroup === usage.taxonName.objectGroup && item.status === 'accepted' && item.isTitle === false).length  > 1){

                        console.log(usage.taxonName.name, ': 同模（不包含autonym）同一篇文獻中多個accepted');
                        return true;
                    }

                } else {

                    if (now_usages.filter(item => item.taxonName.autonymGroup !== usage.taxonName.autonymGroup && item.taxonName.objectGroup === usage.taxonName.objectGroup && item.status === 'accepted' && item.isTitle === false ).length  > 1){
                        console.log(usage.taxonName.name, ': 同模（不包含autonym）同一篇文獻中多個accepted');
                        return true;
                    }

                }
            }


            if (usage.status === '') {
                return true;
            }

            if (usage.isTitle === false && usage.status === 'accepted' &&  (!('isInTaiwan' in usage.properties) || usage.properties.isInTaiwan === null)) {
                console.log(usage.taxonName.name, ': 地位為accepted，但沒有勾選存在於台灣');
                return true;
            }

            // if (usage.status !== 'accepted' && index === 0) {
            //     return true;
            // }

            if (usage.isTitle === true && usage.status == 'not-accepted') {
                console.log(usage.taxonName.name, ': 非接受名不得設定為標題');
            }

            return false;
        },
        isNotAllowedUsage(usage, index){

            let now_usages = this.usages;

            // # 1. 同一個分類群有一個以上的接受名 -> 感覺在介面上不會出現
            if (now_usages.filter(item => item.group === usage.group && item.status === 'accepted' && item.isTitle === false).length > 1){                
                openNotify('發生錯誤，同一個分類群有一個以上的接受名', 'is-danger');
            }

            let accepted_usages = now_usages.filter(item => item.group === usage.group && item.status === 'accepted' && item.isTitle === false).length
            // 除非有相同的name
            let accepted_usages_2 = now_usages.filter(item => item.taxonName.name === usage.taxonName.name && item.status === 'accepted' && item.isTitle === false).length

            if (accepted_usages == 0 && accepted_usages_2 == 0){
                openNotify('發生錯誤，同一個分類群裡面沒有任何接受名', 'is-danger');
            }

            // # 3. isTitle=true，status=not-accepted
            if (usage.isTitle === true && usage.status == 'not-accepted') {
                openNotify('發生錯誤，非接受名不得設定為標題', 'is-danger');
            }

        },
        loadConfigs() {
            const { id } = this.$route.params;
            if (this.$route.meta.type === 'reference') {
                this.configs = {
                    type: 'reference',
                    backUrl: {
                        name: 'reference-page',
                        params: { id },
                    },
                    listRoute: 'reference-list-page',
                    usageRoute: 'reference-usages-edit',
                };
            } else {
                this.configs = {
                    type: 'namespace',
                    backUrl: { name: 'namespace-list' },
                    listRoute: 'namespace-list',
                    usageRoute: 'namespace-usages',
                };
            }
        },
        onSave() {
            const app = this;

            let isValid = true;

            this.usages.forEach((usage, index) => {
                if (app.isInvalidUsage(usage, index)) {
                
                    app.$store.commit('openModal', {
                        component: () => import('../components/modals/ConfirmLeaveModal.vue'),
                        props: {
                            onLeave: () => {
                                app.$store.commit('closeModal');
                                this.$router.push(this.configs.backUrl);
                            },
                        },
                    });
                    isValid = false;
                }
            });

            if (isValid) {
                this.$router.push(this.configs.backUrl);
            }
        },
        onChange(event) {
            if (typeof event.added !== 'undefined' && event.added.element.child !== undefined) {
                const { element } = event.added;
                const { newIndex } = event.added;

                this.usages = [
                    ...this.usages.slice(0, newIndex), element, ...element.child, ...this.usages.slice(newIndex + 1),
                ];
            }

            let isValid = true;

            if (typeof event.moved !== 'undefined') {
                if (event.moved.oldIndex == 0 && event.moved.element.status=='accepted'){
                    // 如果還有其他group是not-accepted的話就有問題
                    let not_accepted_usages = this.usages.filter(item => item.group == event.moved.element.group && item.status === 'not-accepted');
                    if (not_accepted_usages.length > 0){
                        openNotify('發生錯誤，同一分類群必須要有一個接受名', 'is-danger');
                        this.refresh();
                        isValid = false;
                        
                    }
                }
            }

            if (isValid) {
                this.onSubmit();
            } 

        },
        onTab(e, index) {
            if (this.isUsageFormSimple) {
                return;
            }

            const newIndent = !this.usages[index].isIndent;
            if (index === 0 && newIndent === true) {
                openNotify(this.$t('validation.usage.firstMustBeAccepted'), 'is-danger');
                return;
            }

            e.preventDefault();
            this.usages[index].isIndent = newIndent;
            this.usages[index].status = newIndent ? 'not-accepted' : 'accepted';

            if (newIndent){
                this.usages[index].isTitle = false;
            }

            this.$nextTick(function () {
                this.onSubmit();
            });
        },
        goUsage(usage) {
            if (usage.isTitle) {
                return;
            }

            const { id } = this.$route.params;
            this.$router.push({
                name: this.configs.usageRoute,
                params: {
                    id,
                    usageId: usage.id,
                },
            });
        },
        onToggleTitle(e, index) {
            e.stopPropagation();
            this.usages[index].isTitle = !this.usages[index].isTitle;

            let accepted_usages = this.usages.filter(item => item.group == this.usages[index].group && item.status === 'accepted' && item.isTitle==false)
            // 除非有相同的name
            let accepted_usages_2 = this.usages.filter(item => item.taxonName.name == this.usages[index].taxonName.name && item.status === 'accepted' && item.isTitle==false)


            if (accepted_usages.length==0 && accepted_usages_2.length==0 ){
                openNotify('發生錯誤，唯一的有效學名不得為標題', 'is-danger');
                // 再改回來
                this.usages[index].isTitle = !this.usages[index].isTitle;
            } else {
                this.$nextTick(function () {
                    this.onSubmit();
                });
            }

        },
        onImportUsages() {
            this.$store.commit('openModal', {
                component: () => import('../components/modals/UsagesImportModal.vue'),
                props: {
                    namespaceId: this.model.id,
                    refresh: this.refresh,
                },
            });
        },
        async onExportUsages(){

            const response = await fetch(`/api/export/namespaces/${this.model.id}/usages`);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = this.model.title + '.xlsx';
            a.click();
            window.URL.revokeObjectURL(url);

        },
        onRemove(e, index) {
            e.stopPropagation();
            this.usages[index].isDeleted = true;
            this.onSubmit();
        },
        onToggleSimpleForm() {
            this.isListSimple = !this.isListSimple;
        },
        onToggleTaxonNameField() {
            this.isShowTaxonName = !this.isShowTaxonName;
        },
        onAddTaxonName() {
            const { id } = this.$route.params;
            this.isLoading = true;

            const newUsage = {
                taxonNameId: this.newTaxonName.id,
                status: this.newTaxonNameStatus,
                taxonName: {
                    ...this.newTaxonName,
                    publishUsage: this.newTaxonName.usage.length ? this.newTaxonName.usage[0] : null,
                    publishReferenceName: this.newTaxonName.referenceName,
                },
                properties: {
                    indications: [],
                    isInTaiwan: this.newTaxonNameStatus === 'accepted' ? this.newTaxonNameIsInTaiwan : null,
                },
                isTitle: false,
                isIndent: this.newTaxonNameStatus === 'not-accepted' || this.newTaxonNameStatus === 'misapplied',
            };

            const usages = [
                ...this.usages,
                newUsage,
            ];

            const saveUrl
                = this.$route.meta.type === 'namespace' ? `namespaces/${id}/usages` : `reference/${id}/usages-edit`;

            this.axios
                .post(saveUrl, usages)
                .then(() => {
                    this.isLoading = false;
                    this.refresh();

                    this.$refs.usageContainer.scrollTo({
                        top: this.$refs.usageContainer.scrollHeight + 10,
                        behavior: 'smooth',
                    });
                })
                .catch(({ data, status }) => {
                    if (status === 422) {
                        openNotify(this.$t('validation.usage.firstMustBeAccepted'), 'is-danger');
                    } else {
                        openNotify(this.$t('common.error'), 'is-danger');
                    }
                });
        },
        onOpenPropertiesModal() {
            this.$store.commit('openModal', {
                component: () => import('../components/modals/UsagePropertyModal.vue'),
                props: {
                    onUpdate: this.onUpdateAllProperties,
                },
            });
        },
        onOpenPublishingModal() {
            this.$store.commit('openModal', {
                component: () => import('../components/modals/PublishingToolModal.vue'),
                props: {
                    namespaceId: this.model.id,
                },
            });
        },
        onUpdateAllProperties(data) {
            const { id } = this.$route.params;

            this.axios.put(
                `${this.configs.type === 'reference' ? 'reference' : 'namespaces'}/${id}/usages-properties`,
                data,
            )
                .then(() => {
                    this.isLoading = false;
                    this.refresh();
                })
                .catch(() => {
                    openNotify('發生錯誤，資料儲存失敗', 'is-danger');
                });
        },
        async onDownloadDoc() {
            const container = document.getElementById('usage-content-container');
            downloadUsageHtmlToDoc(container, this.model.title);
        },
        onClearNamespace(){
            this.$store.commit('openModal', {
                component: () => import('../components/modals/ClearUsageModal.vue'),
                props: {
                    id: parseInt(this.$route.params.id)
                }
            });
        },
        onSubmit: _.debounce(function () {
            const { id } = this.$route.params;
            this.isLoading = true;

            const data = this.usages.map((usage) => {
                let result = {
                    isTitle: usage.isTitle,
                    isIndent: usage.isIndent,
                    taxonNameId: usage.taxonName.id,
                    isDeleted: usage.isDeleted === true,
                    status: usage.status,
                };

                if (usage.id) {
                    result.id = usage.id;
                } else {
                    result = {
                        ...result,
                        customNameRemark: usage.customNameRemark,
                        nameRemark: usage.nameRemark,
                        status: usage.status,
                        typeSpecimens: usage.typeSpecimens,
                        properties: usage.properties,
                        perUsages: usage.perUsages,
                        parentTaxonNameId: usage.parentTaxonNameId,
                    };
                }

                return result;
            });

            const saveUrl
                = this.$route.meta.type === 'namespace' ? `namespaces/${id}/usages` : `reference/${id}/usages-edit`;

            this.axios
                .post(saveUrl, data)
                .then(() => {
                    this.isLoading = false;
                    this.refresh();
                })
                .catch(() => {
                    openNotify('發生錯誤，資料儲存失敗', 'is-danger');
                });
        }),

        async loadUsages(url) {
            try {
                let offset = 0;
                const resp = await this.axios.get(`${url}?offset=${offset}`);
                let data = resp.data.usages;
                let groupCount = resp.data.groupCount;

                this.model = resp.data;
                this.usages = data.map((u) => ({
                    ...u,
                    taxonNameId: u.taxonName?.id,
                    parentTaxonNameId: u.parentTaxonName?.id,
                }));

                if (groupCount > 100) {
                    for (let i = 100; i <= groupCount; i += 100) {
                        await this.loadUsageWithDelay(i,url);
                    }                
                } 
                this.isLoading = false;
                this.usages.forEach((usage, index) => {
                    this.isNotAllowedUsage(usage, index)
                })



            } catch (error) {
                console.error("load usage:", error);
            }
        },
        async loadUsageWithDelay(offset,url) {
            try {
                const resp = await this.axios.get(`${url}?offset=${offset}`);
                let data = resp.data.usages;

                this.usages = [...this.usages, ...data.map((u) => ({
                    ...u,
                    taxonNameId: u.taxonName?.id,
                    parentTaxonNameId: u.parentTaxonName?.id,
                }))];

                // wait 1 sec avoid Too Many Attempts
                await new Promise(resolve => setTimeout(resolve, 1000));
            } catch (error) {
                console.error(`loadUsageWithDelay ${offset}:`, error);
            }
        },


        refresh() {
            this.isLoading = true;
            const { id } = this.$route.params;

            const url
                = this.configs.type === 'namespace' ? `/namespaces/${id}/usages` : `/references/${id}/usages-edit`;

            this.loadUsages(url);
        },
        getIndications(indicationArray) {
            return indicationArray ?
                indicationArray.map(
                    (abbreviation) => indications.find((i) => i.abbreviation === abbreviation),
                )
                    .filter(Boolean) : [];
        },
    },
    components: {
        RadioButton,
        StatusSelect,
        UsagePropertyShortTags,
        UsagePropertyExportTags,
        UsagePreview,
        AuthorName,
        TaxonNameSelect,
        draggable,
        Tooltip,
        StatusDot,
    },
};
</script>
<style lang="scss" scoped>

.accepted-prop {
    margin-left: 2rem;
    margin-bottom: 1rem;
}

.sortable-chosen .accepted-prop {
    display: none;
}


.d-none {
    display: none;
}


#usage-container {
    height: calc(100vh - #{$navbar-height} - #{$breadcrumb-height} - 2.5rem);
}

.taxon-name-column {
    width: 100%;
}

.usage-row {
    border: 1px solid $light-grey;
    padding: .25rem .5rem;
    margin-bottom: .8rem;
    cursor: pointer;
    min-height: 1rem;
    display: flex;
    box-shadow: 0 0.25em .5em -0.125em rgba(85, 85, 85, 0.1), 0 0px 0 1px rgba(85, 85, 85, 0.02);

    .buttons {
        display: none;
    }

    &:hover {
        .buttons {
            display: block;
        }
    }

    &:focus, &.selected {
        outline: none;
        background: $light-grey;
    }

    &.is-title {
        border: 0;
        box-shadow: none;
        font-weight: bold;
    }

    &.is-indent {
        margin-left: 2rem;
    }

    .utitle {
        color: $orange;
    }

    .handle {
        margin-right: .5rem;
    }

    .usage-content {
        flex-grow: 1;

        p {
            display: inline;
        }
    }
}

.namespace-button {
    font-size: 0.9rem;
}
</style>
