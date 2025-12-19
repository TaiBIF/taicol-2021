<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <!-- <div class="flex gap-2">
                <p class="leading-10 w-[80px]">
                    <span class="font-bold">DOI</span>
                </p>
                <general-input v-model="doi" :errors="errors.doi" class="grow"/>
            </div>
            或
            <div class="flex gap-2">
                <p class="leading-10 w-[80px]">
                    <span class="font-bold">文獻URL</span>
                </p>
                <general-input v-model="url" :errors="errors.url" class="grow"/>
            </div>
            或 -->
            <div class="flex gap-2 mb-4">
                <p class="leading-10 w-[80px]">
                    <span class="font-bold">文獻PDF</span>
                </p>
                <general-input
                    class="grow"
                    accept=".pdf,application/pdf"
                    type="file"
                    :errors="errors.file"
                    v-model="uploadedFile"
                />
            </div>

            <button class="button" v-on:click="onFetchReferenceAI">{{ $t('common.import') }}</button>

            <div class="min-h-3/5 flex w-full">
                <div v-if="isLoading" class="flex w-full items-center justify-center">
                    <loading></loading>
                </div>
                <div v-else-if="!!result" class="py-4">
                    <table class="table">
                        <tr>
                            <td class="no-wrap">{{ $t('reference.type') }}</td>
                            <td>{{ typeDisplay(result.type) }}</td>
                        </tr>

                        <tr>
                            <td class="no-wrap">{{ $t('reference.author') }}</td>
                            <td>
                                <!-- 新增的作者表格 -->
                                <table class="table w-full border border-gray-300">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-300 px-4 py-2 text-left">文獻中作者</th>
                                            <th class="border border-gray-300 px-4 py-2 text-left">對應現有資料庫人名</th>
                                            <th class="border border-gray-300 px-4 py-2 text-center">選擇其他人名</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(author, key) in result.authors" :key="key">
                                            <!-- 文獻中作者 -->
                                            <td class="border border-gray-300 px-4 py-2">
                                                <span :class="{'text-red-500': !result.authorsPossible[key]}" 
                                                    class="font-bold">
                                                    {{ author.family }}, {{ author.given }}
                                                </span>
                                            </td>
                                            
                                            <!-- 對應TaiCOL資料庫內作者 -->
                                            <td class="border border-gray-300 px-4 py-2">
                                                <div v-if="!!result.authorsPossible[key]">
                                                    <router-link  target="_blank" :to="{name: 'person-page', params: {id: result.authorsPossible[key].id }}" class="my-link">
                                                        {{ result.authorsPossible[key]['fullName'] }} <span v-if=" result.authorsPossible[key].abbreviationName ">({{ result.authorsPossible[key].abbreviationName }})</span>
                                                    </router-link>
                                                </div>
                                                <span v-else class="text-gray-500 italic">未找到對應作者</span>
                                            </td>

                                            <!-- 動作 -->
                                            <td class="border border-gray-300 px-4 py-2 text-center">
                                                <person-select  
                                                    class="w-[180px]"
                                                    :multiple="false"
                                                    :errors="errors.authors"
                                                    :value="selectedAuthors[key] || []"
                                                    :authorData="{ given: author.given, family: author.family, index: key }"
                                                    @input="(value) => onAuthorSelect(key, value)"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.publishYear') }}</td>
                            <td>{{ result.publishYear }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.articleTitle') }}</td>
                            <td>{{ result.articleTitle }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.journal') }}/{{ $t('reference.bookTitle') }}</td>
                            <td>{{ result.bookTitle }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">
                                {{ $t('reference.journalAbbreviation') }}/{{ $t('reference.bookTitleAbbreviation') }}
                            </td>
                            <td>{{ result.bookTitleAbbreviation }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.volume') }}/{{ $t('reference.volumeBook') }}</td>
                            <td>{{ result.volume }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.issue') }}</td>
                            <td>{{ result.issue }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.pagesRange') }}</td>
                            <td>{{ result.page }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.doi') }}</td>
                            <td>{{ result.doi }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">{{ $t('reference.url') }}</td>
                            <td>{{ result.url }}</td>
                        </tr>

                        <tr>
                            <td class="no-wrap">{{ $t('reference.language') }}</td>
                            <td>{{ result.language }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button :disabled="!result" class="button" v-on:click="onSetToForm">{{ $t('reference.fillInByDoi') }}</button>
            <button class="button" v-on:click="onClose">{{ $t('common.close') }}</button>
        </div>
    </div>
</template>

<script lang="ts">
import {
    defineComponent, inject, PropType, ref,
} from '@vue/composition-api';
import GeneralInput from '../GeneralInput.vue';
import Loading from '../Loading.vue';
import referenceTypes from '../../utils/options/referenceTypes';
import PersonSelect from '../selects/PersonSelect.vue';

export default defineComponent({
    name: 'ai-reference-modal',
    props: {
        onOverwrite: {
            type: Function as PropType<(data) => void>,
            required: true,
        },
    },
    setup(props, context) {
        
        const app: any = context.root;
        const axios: any = inject('axios');

        // 測試用
        // app.$store.commit('openModal', {
        //     component: () => import('../modals/BindReferenceModal.vue'),
        // });

        // 響應式變數
        const doi = ref<string>('');
        const url = ref<string>('');
        const uploadedFile = ref<File | null>(null);
        const result = ref<{
            type: number,
            authors: Array<{ given: string, family: string }>,
            authorsPossible: object,
            publishYear: string,
            articleTitle: string,
            bookTitle: string,
            bookTitleAbbreviation: string,
            volume: string,
            issue: string,
            page: string,
            doi: string,
            url: string,
            language: string,
            file: string,
        } | null>(null);
        const errors = ref<object>({});
        const isLoading = ref<boolean>(false);
        const selectedAuthors = ref<{[key: number]: any[]}>({});

        // 處理檔案上傳
        // const onSetFile = (event) => {
        //     const file = event.target.files[0];
        //     uploadedFile.value = file;
        // };

        // 發送 AI 請求
        const onFetchReferenceAI = () => {

            // 檢查是否有填寫任一項目
            // if (!doi.value && !url.value && !uploadedFile.value) {
            if (!uploadedFile.value) {
                errors.value = { 
                    // doi: ['請填入DOI、URL或上傳PDF檔案'], 
                    // url: ['請填入DOI、URL或上傳PDF檔案'], 
                    file: ['請上傳PDF檔案'] 
                };
                return;
            }

            // 檢查檔名是否含有中文或特殊字符
            const fileName = uploadedFile.value.name;
            const hasNonAscii = /[^\x00-\x7F]/.test(fileName);
            
            if (hasNonAscii) {

                errors.value = { 
                    // doi: ['請填入DOI、URL或上傳PDF檔案'], 
                    // url: ['請填入DOI、URL或上傳PDF檔案'], 
                    file: ['檔案名稱含有中文或特殊字符，可能導致處理失敗，請重新命名檔案後再上傳'] 
                };

                return;
            }

            isLoading.value = true;
            errors.value = {};

            // 如果有上傳檔案，使用 POST 與 FormData
                const formData = new FormData();
                formData.append('file', uploadedFile.value);

                axios.post('/fetch/reference/ai', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'Accept': 'application/json'
                    },
                    transformRequest: [function (data) {
                        return data; // 不要讓 axios 轉換 FormData
                    }]
                })
                .then(({ data }) => {
                    result.value = data;
                    errors.value = {};
                    isLoading.value = false;
                })
                .catch(({ errors: e, status, message, data }) => {

                    console.log( e, status, message, data)
                    result.value = null;

                    if (status === 400) {
                        errors.value = { file: [message || '檔案處理失敗'] };
                    } else if (status === 422) {
                        errors.value = { file: [e || '檔案處理失敗'] } ;
                    } else if (status === 409) {
                        let errorMessage = '';
                        
                        if (e?.file.type === 'reference_usage' && e?.file.reference) {
                            const referenceUrl = app.$router.resolve({
                                name: 'reference-page',
                                params: { id: e.file.reference.id }
                            }).href;
                            
                            errorMessage = app.$t('validation.referenceUsagePrefix') + 
                                        `<a class="my-link" href="${referenceUrl}" target="_blank">${e.file.reference.title}</a>`;
                        } else if (e?.file.type === 'reference_with_file' && e?.file.reference) {
                            const referenceUrl = app.$router.resolve({
                                name: 'reference-page',
                                params: { id: e.file.reference.id }
                            }).href;
                            
                            errorMessage = app.$t('validation.referenceHasFile') + 
                                        `<a class="my-link" href="${referenceUrl}" target="_blank">${e.file.reference.title}</a>`;
                        } else if (message == 'Reference exists') {
                            console.log(data)
                            app.$store.commit('closeModal');
                            app.$toast && app.$toast.success('文獻已存在，將直接綁定');

                            // 將資料存到 store 中
                            app.$store.commit('setBindReferenceData', data[0]);

                            app.$store.commit('openModal', {
                                component: () => import('../modals/BindReferenceModal.vue'),
                            });

                        } else {
                            errorMessage = message || e?.message || '檔案處理失敗';
                        }

                        errors.value = { file: [errorMessage] };
                    } else {
                        errors.value = { file: [message || '檔案處理失敗'] };
                    }
                    isLoading.value = false;
                });
        };

        const onSetToForm = () => {

            if (!result.value) return;

            const finalAuthorsPossible = getFinalAuthorsPossible();

            // 準備要傳遞給 ReferenceLayer 的資料
            const referenceData = {
                type: result.value.type,
                authors: finalAuthorsPossible,
                publishYear: result.value.publishYear,
                articleTitle: result.value.articleTitle,
                bookTitle: result.value.bookTitle,
                bookTitleAbbreviation: result.value.bookTitleAbbreviation,
                volume: result.value.volume,
                issue: result.value.issue,
                page: result.value.page,
                doi: result.value.doi,
                url: result.value.url,
                language: result.value.language,
                file: result.value.file,
                fromAiImport: true
            };

            console.log('Preparing to send reference data:', referenceData);

            

            // // 將資料存到 store 中
            // app.$store.commit('setReferencePresetData', referenceData);

            // // 關閉當前 modal
            // app.$store.commit('closeModal');

            // // 打開 ReferenceLayer
            // app.$store.commit('layer/ADD', {
            //     template: () => import('../layers/ReferenceLayer.vue'),
            //     props: {
            //         usePresetFromStore: true,
            //     },
            //     events: {
            //         onAfterSubmit: (data) => {
            //             console.log('Reference created:', data);
            //             // 將資料存到 store 中
            //             app.$store.commit('setBindReferenceData', data);

            //             // // AI 導入特殊的後續處理
            //             app.$toast && app.$toast.success('文獻已成功發布！');

            //             // 這邊要直接打開另外一個綁定modal
            //             app.$store.commit('layer/CLOSE');

            //             app.$store.commit('openModal', {
            //                 component: () => import('../modals/BindReferenceModal.vue'),
            //             });

            //             if (props.onOverwrite && typeof props.onOverwrite === 'function') {
            //                 props.onOverwrite(data);
            //             }
            //         },
            //     }
            // });
        };

        const onAuthorSelect = (authorIndex: number, selectedPersons: any[]) => {
            selectedAuthors.value[authorIndex] = selectedPersons;
        };

        const getFinalAuthorsPossible = () => {
            const finalAuthors: any[] = [];
            
            result.value?.authors?.forEach((originalAuthor, index) => {

                if (selectedAuthors.value[index]) {
                    // 下拉選單的會是list 取第一個
                    console.log('selectedAuthors', selectedAuthors.value[index]);
                    finalAuthors.push(selectedAuthors.value[index]);
                } else if (result.value?.authorsPossible && result.value.authorsPossible[index]) {
                    finalAuthors.push(result.value.authorsPossible[index]);
                    console.log('finalAuthors', result.value.authorsPossible[index]);

                }
            });
            
            return finalAuthors;
        };

        const onClose = () => {
            app.$store.commit('closeModal');
        };

        const typeDisplay = (type) => {
            const typeObject = referenceTypes.find((t) => t.value === type);
            return typeObject ? app.$t(`reference.typeOptions.${typeObject.value}`) : '';
        };

        return {
            doi,
            url,
            uploadedFile,
            result,
            errors,
            isLoading,
            selectedAuthors,
            typeDisplay,
            onFetchReferenceAI,
            onSetToForm,
            onClose,
            onAuthorSelect,
            getFinalAuthorsPossible,
        };
    },
    components: { Loading, GeneralInput, PersonSelect },
});
</script>