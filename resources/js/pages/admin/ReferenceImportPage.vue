<template>
    <div class="w-full h-full">
        <div class="container flex flex-col h-full">
            <div class="bg-white">
                <div class="flex p-4 gap-1">
                    <div class="w-full">
                        <input
                            class="input is-fullwidth"
                            type="file"
                            v-on:change="onSetFile($event)"
                        />
                        <span v-for="message in errors.file" class="text-red-500" v-text="message"></span>
                    </div>

                    <button :disabled="isLoading" class="button" v-on:click="onSubmit">匯入</button>
                </div>

                <ul class="px-4 pb-2">
                    <li>1. 上傳檔案請依該欄位順序</li>
                    <li>2. 文獻類型, 發表年份必填</li>
                    <li>3. 語言項目: 英文 、繁體中文 、日文 、簡體中文 、德文 、法文 、拉丁文 、其他</li>
                </ul>
            </div>

            <div v-if="isLoading" class="grow">
                <loading-section></loading-section>
            </div>
            <div v-else class="overflow-y-auto grow">
                <table class="narrow-table w-full">
                    <thead>
                    <tr>
                        <td></td>
                        <td>文獻類型</td>
                        <td>作者(|分隔)*比對全名*</td>
                        <td>發表年份</td>
                        <td>文章標題</td>
                        <td>書名/期刊</td>
                        <td>期刊/書名縮寫</td>
                        <td>卷號</td>
                        <td>期號</td>
                        <td>部冊號</td>
                        <td>版本</td>
                        <td>頁碼範圍</td>
                        <td>章節</td>
                        <td>電子文章編號</td>
                        <td>DOI</td>
                        <td>連結</td>
                        <td>語言</td>
                        <td>版權</td>
                        <td>備註</td>
                    </tr>
                    </thead>
                </table>
                <div v-for="(rowData, row) in errorRows" class="text-center text-red-500">
                    ERROR: [第 {{ row }} 筆] {{ rowData.message }}
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent, inject, ref } from '@vue/composition-api';
import Page from '../Page.vue';
import GeneralInput from '../../components/GeneralInput.vue';
import LoadingSection from '../../components/LoadingSection.vue';
import { openNotify } from '../../utils';

export default defineComponent({
    setup(props) {
        const axios: any = inject('axios');
        const formData = new FormData();
        const duplicateRows = ref<object>({});
        const repeatRows = ref<object>({});
        const warningRows = ref<object>({});
        const validRows = ref<object>({});
        const errorRows = ref<object>({});

        const isLoading = ref<boolean>(false);

        const errors = ref<object>({});

        const onSetFile = (event) => {
            formData.set('file', event.target.files[0]);
        };

        const resetStates = () => {
            errors.value = {};
            duplicateRows.value = {};
            repeatRows.value = {};
            warningRows.value = {};
            validRows.value = {};
            errorRows.value = {};
        };

        const onSubmit = () => {
            isLoading.value = true;
            resetStates();

            axios
                .post('/import/references', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                .then(({ data: { total } }) => {
                    openNotify(`成功匯入 ${total} 筆`);
                })
                .catch(({
                    status, errors: e, data, message,
                }, err) => {
                    if (status === 409) {
                        duplicateRows.value = data.duplicateRows;
                        repeatRows.value = data.repeatRows;
                        warningRows.value = data.warningRows;
                        validRows.value = data.validRows;
                        errorRows.value = data.errorRows;
                        openNotify(message, 'is-danger');
                    } else if (status === 422) {
                        errors.value = e;
                    }
                }).finally(() => {
                    isLoading.value = false;
                });
        };

        return {
            duplicateRows,
            errorRows,
            warningRows,
            repeatRows,
            validRows,
            errors,
            isLoading,
            onSetFile,
            onSubmit,
        };
    },
    components: { LoadingSection, GeneralInput, Page },
});
</script>
<style lang="scss">
.narrow-table {
    td {
        padding-left: 2px;
        padding-right: 2px;
        border: 1px solid #dbdbdb;
        background-color: white;
        white-space: nowrap;
    }

    thead {
        td {
            background-color: #dbdbdb;
            border: 0px solid #dbdbdb;
            position: sticky;
            top: 0;
        }
    }
}
</style>
