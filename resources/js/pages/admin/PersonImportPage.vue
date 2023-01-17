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

                    <button :disabled="isLoading" class="button" v-on:click="onValidate">檢驗</button>
                    <button :disabled="isLoading" class="button" v-on:click="onSubmit">匯入</button>
                </div>

                <ul class="px-4 pb-2">
                    <li>1. <span class="text-red-500">紅色</span>({{ Object.keys(duplicateRows).length }})為重複請刪除,
                        <span class="text-red-800">暗紅色</span>({{ Object.keys(errorRows).length }})為資料錯誤請修正或刪除,
                        <span class="text-orange-300">橘色</span>({{ Object.keys(warningRows).length }})為有同姓名存在,
                        <span>共 {{ Object.keys(validRows).length + Object.keys(warningRows).length }} 筆可匯入</span>
                    </li>
                    <li>2. 上傳檔案請依該欄位順序</li>
                    <li>3. biology_departments 項目: viruses, bacteria, archaea, protozoa, chromista, fungi, plantae,
                        animalia
                    </li>
                    <li>4. first_name, last_name 必填</li>
                    <li>5. country_name 輸入國家中文名，如「臺灣」、「日本」等</li>
                </ul>
            </div>

            <div v-if="isLoading" class="grow">
                <loading-section></loading-section>
            </div>
            <div v-else class="overflow-y-auto grow">
                <div>
                    <table class="narrow-table">
                        <thead>
                        <tr>
                            <td></td>
                            <td>last_name</td>
                            <td>first_name</td>
                            <td>middle_name</td>
                            <td>original_full_name</td>
                            <td>abbreviation_name</td>
                            <td>other_names</td>
                            <td>year_birth</td>
                            <td>year_death</td>
                            <td>year_publication</td>
                            <td>country_name</td>
                            <td>biology_departments</td>
                            <td>biological_group</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="row in duplicateRows" class="text-red-500">
                            <td v-text="row.number"></td>
                            <td v-text="row.lastName"></td>
                            <td v-text="row.firstName"></td>
                            <td v-text="row.middleName"></td>
                            <td v-text="row.originalFullName"></td>
                            <td v-text="row.abbreviationName"></td>
                            <td v-text="row.otherNames"></td>
                            <td v-text="row.yearBirth"></td>
                            <td v-text="row.yearDeath"></td>
                            <td v-text="row.yearPublication"></td>
                            <td v-text="row.countryName"></td>
                            <td v-text="row.biologyDepartments"></td>
                            <td v-text="row.biologicalGroup"></td>
                        </tr>
                        <tr v-for="row in errorRows" class="text-red-800">
                            <td v-text="row.number"></td>
                            <td v-text="row.lastName"></td>
                            <td v-text="row.firstName"></td>
                            <td v-text="row.middleName"></td>
                            <td v-text="row.originalFullName"></td>
                            <td v-text="row.abbreviationName"></td>
                            <td v-text="row.otherNames"></td>
                            <td v-text="row.yearBirth"></td>
                            <td v-text="row.yearDeath"></td>
                            <td v-text="row.yearPublication"></td>
                            <td v-text="row.countryName"></td>
                            <td v-text="row.biologyDepartments"></td>
                            <td v-text="row.biologicalGroup"></td>
                        </tr>
                        <tr v-for="row in repeatRows" class="text-red-500">
                            <td v-text="row.number"></td>
                            <td v-text="row.lastName"></td>
                            <td v-text="row.firstName"></td>
                            <td v-text="row.middleName"></td>
                            <td v-text="row.originalFullName"></td>
                            <td v-text="row.abbreviationName"></td>
                            <td v-text="row.otherNames"></td>
                            <td v-text="row.yearBirth"></td>
                            <td v-text="row.yearDeath"></td>
                            <td v-text="row.yearPublication"></td>
                            <td v-text="row.countryName"></td>
                            <td v-text="row.biologyDepartments"></td>
                            <td v-text="row.biologicalGroup"></td>
                        </tr>
                        <tr v-for="row in warningRows" class="text-orange-300">
                            <td v-text="row.number"></td>
                            <td v-text="row.lastName"></td>
                            <td v-text="row.firstName"></td>
                            <td v-text="row.middleName"></td>
                            <td v-text="row.originalFullName"></td>
                            <td v-text="row.abbreviationName"></td>
                            <td v-text="row.otherNames"></td>
                            <td v-text="row.yearBirth"></td>
                            <td v-text="row.yearDeath"></td>
                            <td v-text="row.yearPublication"></td>
                            <td v-text="row.countryName"></td>
                            <td v-text="row.biologyDepartments"></td>
                            <td v-text="row.biologicalGroup"></td>
                        </tr>
                        <tr v-for="row in validRows">
                            <td v-text="row.number"></td>
                            <td v-text="row.lastName"></td>
                            <td v-text="row.firstName"></td>
                            <td v-text="row.middleName"></td>
                            <td v-text="row.originalFullName"></td>
                            <td v-text="row.abbreviationName"></td>
                            <td v-text="row.otherNames"></td>
                            <td v-text="row.yearBirth"></td>
                            <td v-text="row.yearDeath"></td>
                            <td v-text="row.yearPublication"></td>
                            <td v-text="row.countryName"></td>
                            <td v-text="row.biologyDepartments"></td>
                            <td v-text="row.biologicalGroup"></td>
                        </tr>
                        </tbody>
                    </table>
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
                .post('/import/persons', formData, {
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

        const onValidate = () => {
            isLoading.value = true;
            resetStates();

            axios
                .post('/validate/persons', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                }).then(({ data }) => {
                    duplicateRows.value = data.duplicateRows;
                    repeatRows.value = data.repeatRows;
                    warningRows.value = data.warningRows;
                    validRows.value = data.validRows;
                    errorRows.value = data.errorRows;
                }).catch(({ status, errors: e, data }, err) => {
                    errors.value = e;
                // todo
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
            onValidate,
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
