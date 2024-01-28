<template>
    <div>
        <div class="px-16 py-12 min-w-400">
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
                    <ul class="list-decimal ml-2">
                        <li>
                            欄位內容請依照範本填寫
                            <a class="text-blue-700 underline" download="usage-import.xlsx"
                               href="/example/usage-import.xlsx">{{ $t('namespace.exampleDownload') }}</a>
                        </li>
                        <li>支援檔案格式 xlsx, xls</li>
                        <li>命名規約、階層、學名為必填</li>
                        <li>
                            <ul>
                                <li>命名規約項目: ICZN、ICN、ICNP、ICVCN</li>
                                <li>階層: kingdom ~ species、subspecies、variety 階層全名</li>
                                <li>地位: accepted、not-accepted、misapplied、undetermined</li>
                                <li>外來屬性: native、naturalized、invasive、cultured</li>
                            </ul>
                        </li>
                        <li>語言項目: 英文、繁體中文、日文、簡體中文、德文、法文、拉丁文、其他</li>
                        <li>是否選項: 是 = 1, 否 = 0, 不選擇 = (留空值)</li>
                    </ul>
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
