<template>
    <div>
        <div class="px-16 py-6 min-w-400">
            <div>
                <p class="title text-center">{{ $t('namespace.importUsages') }}</p>
            </div>
            <div class="py-4 min-h-3/5">
                <div class="w-full">
                    <input
                        class="input is-fullwidth"
                        type="file"
                        v-on:change="onSetFile($event)"
                    />
                    <span v-for="message in errors.file" class="text-red-500" v-text="message"></span>
                    <div v-for="(rowData, row) in errorRows" class="text-center text-red-500">
                        ERROR: [第 {{ row }} 筆] {{ rowData.message }}
                    </div>
                </div>
                <div class="p-2">
                    <ol class="list-decimal ml-2">
                        <li>
                            欄位內容請依照範本填寫
                            <a class="text-blue-700 underline" download="usage-import.xlsx"
                               href="/example/usage-import.xlsx">{{ $t('namespace.exampleDownload') }}</a>
                        </li>
                        <li>支援檔案格式 xlsx, xls</li>
                        <li>命名規約、階層、學名為必填
                            <ul class="description-list">
                                <li>命名規約項目：ICZN、ICN、ICNP、ICVCN</li>
                            </ul>
                        </li>
                        <li>階層：kingdom ~ species、subspecies、variety
                            <ul class="description-list">
                                <li>階層全名，且字首不大寫</li>
                            </ul>
                        </li>
                        <li>地位：accepted、not-accepted、misapplied、undetermined</li>
                        <li>外來屬性：native、naturalized、invasive、cultured</li>
                        <li>標註：多個時以「,」分隔，並僅能匯入目前系統有的</li>
                        <li>文獻格式：reference_id,show_page,figure,pro_parte|reference_id,show_page,figure,pro_parte
                            <ul class="description-list">
                                <li>多筆時以「|」分隔</li>
                                <li>reference_id為必填，其他任何一欄位為空值時仍需要留空並保留逗號</li>
                                <li>figure內容下如有逗號「,」時前後需加上雙引號「"」</li>
                                <li>pro_parte：填入true或false，false可省略留空</li>
                                <li>範例：128,294,"fig. 1, 2",false|141,10,,false</li>
                            </ul>
                        </li>
                        <li>自訂欄位支援custom_fields1至custom_fields5，將欲使用於自訂欄位欄位名的名稱放在欄位內容的最前方，如：
                            <ul class="description-list">
                                <li>IUCN Red List Category:The author considers V. hsuii to be Threatened (NT) following the IUCN Red List criteria (IUCN 2003).</li>
                            </ul>
                        </li>
                        <li>俗名格式：common_name(language,area)|common_name(language,area)
                            <ul class="description-list">
                                <li>多個時以「|」分隔</li>
                                <li>common_name和language為必填，area如為空值需要保留前方逗點，如為臺灣使用建議填入Taiwan</li>
                                <li>language語言項目：英文、繁體中文、日文、簡體中文、德文、法文、拉丁文、其他</li>
                                <li>範例：構樹(繁體中文,Taiwan)|鹿仔樹(繁體中文,)</li>
                            </ul>
                        </li>
                        <li>是否選項：是 = 1, 否 = 0, 不選擇 = (留空值)</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="sticky bottom-0 p-4 bg-white border-t">
            <div class="buttons is-right">
                <button class="button mr-2" v-on:click="onImportExcel">
                    {{ $t('common.import') }}
                </button>
                <button class="button mr-2" v-on:click="close">{{ $t('common.close') }}</button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, inject, ref } from '@vue/composition-api';
import { debounce } from 'lodash';
import { openNotify } from '../../utils';

export default defineComponent({
    props: {
        namespaceId: {
            type: Number,
            required: true,
        },
        refresh: {
            type: Function,
            default() {

            },
        },
    },
    setup(props, context) {
        const axios: any = inject('axios');
        const app: any = context.root;
        const store = app.$store;

        const formData = new FormData();
        const errors = ref<object>({});
        const errorRows = ref<object>({});

        const { namespaceId, refresh } = props;

        const onSetFile = (event) => {
            formData.set('file', event.target.files[0]);
        };

        const close = () => {
            store.commit('closeModal');
        };

        const onImportExcel = debounce(() => {
            axios
                .post(`/import/namespaces/${namespaceId}/usages`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                .then(() => {
                    close();
                    refresh();
                })
                .catch(({
                    errors: e, status, message, data,
                }) => {
                    if (status === 409) {
                        errorRows.value = data.errorRows;
                        openNotify(message, 'is-danger');
                    } else if (status === 422) {
                        errors.value = e;
                    }
                });
        });

        return {
            errors,
            errorRows,
            onSetFile,
            onImportExcel,
            close,
        };
    },
});
</script>
<style lang="scss" scoped>
.description-list {
    margin-left: 1rem;
    list-style: circle;
}
</style>
