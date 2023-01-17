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
                    <li>2. 命名規約, 階層, name 必填</li>
                    <li>3. 作者欄位 (original_name_author, original_name_ex_author, name_authors,
                        name_ex_authors) 使用原母語完整名以「|」分隔
                    </li>
                </ul>
            </div>

            <div v-if="isLoading" class="grow">
                <loading-section></loading-section>
            </div>
            <div v-else class="overflow-y-auto grow">
                <table class="narrow-table w-full">
                    <thead>
                    <tr>
                        <td>nomenclature</td>
                        <td>rank</td>
                        <td>latin_name</td>
                        <td>latin_genus</td>
                        <td>latin_s1</td>
                        <td>s2_rank</td>
                        <td>latin_s2</td>
                        <td>original_name</td>
                        <td>original_name_author</td>
                        <td>original_name_exauthor</td>
                        <td>formatted_authors</td>
                        <td>name_authors</td>
                        <td>name_ex_authors</td>
                        <td>reference_name</td>
                        <td>reference_id</td>
                        <td>page</td>
                        <td>cite_figure</td>
                        <td>year</td>
                        <td>note</td>
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
                .post('/import/taxon-names', formData, {
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
