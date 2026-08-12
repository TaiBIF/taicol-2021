<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <p class="title text-center" v-if="!submitted"><b>{{ $t('aiImport.guide.title') }}</b></p>
            <ul class="guide-list" v-if="!submitted">

                <li>{{ $t('aiImport.guide.fileUpload') }}</li>
                <li>{{ $t('aiImport.guide.redundancyCheck') }}</li>
                
                <!-- 子層情境列表 -->
                <li>
                    <span>{{ $t('aiImport.guide.scenariosTitle') }}</span>
                    <ul class="sub-list">
                        <li>{{ $t('aiImport.guide.scenarios.case1') }}</li>
                        <li>{{ $t('aiImport.guide.scenarios.case2') }}</li>
                        <li>{{ $t('aiImport.guide.scenarios.case3') }}</li>
                        <li>{{ $t('aiImport.guide.scenarios.case4') }}</li>
                        <li>{{ $t('aiImport.guide.scenarios.case5') }}</li>
                    </ul>
                </li>
                <li>{{ $t('aiImport.guide.newRef') }}</li>
                <li>{{ $t('aiImport.guide.parsingProcess') }}</li>
                <li>{{ $t('aiImport.guide.newTaxa') }}</li>
                <li>{{ $t('aiImport.guide.verification') }}</li>
                <li>{{ $t('aiImport.guide.finalImport') }}</li>
                </ul>

            <div class="flex gap-2 my-4">
                <p class="leading-10 w-[80px]">
                    <span class="font-bold">{{ $t('aiImport.referencePDF') }}</span>
                </p>
                <general-input
                    class="grow"
                    accept=".txt,.doc,.docx,.pdf,.rtf,.odt,text/plain,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
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
        const uploadedFile = ref<File | null>(null);
        const result = ref<any>(null);
        const errors = ref<object>({});
        const isLoading = ref<boolean>(false);
        const submitted = ref<boolean>(false);
        const selectedAuthors = ref<{[key: number]: any[]}>({});

        // 處理檔案上傳
        // const onSetFile = (event) => {
        //     const file = event.target.files[0];
        //     uploadedFile.value = file;
        // };

        const onFetchReferenceAI = () => {

            submitted.value = true; // 按下匯入後就隱藏note

            // 1. 前端基本驗證
            if (!uploadedFile.value) {
                errors.value = { file: ['請上傳PDF檔案'] };
                return;
            }

            // 檢查檔名是否含有非 ASCII 字符
            // eslint-disable-next-line no-control-regex
            if (/[^\x00-\x7F]/.test(uploadedFile.value.name)) {
                errors.value = { file: ['檔案名稱含有中文或特殊字符，可能導致處理失敗，請重新命名檔案後再上傳'] };
                return;
            }

            isLoading.value = true;
            errors.value = {};
            result.value = null;

            const formData = new FormData();
            formData.append('file', uploadedFile.value);

            axios.post('/fetch/reference/ai', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json'
                },
            })
            .then(({ data }) => {
                // 成功處理
                result.value = data.data; 
                errors.value = {};
            })
            .catch((error) => {
            // --- 重構重點：正確解析 Axios 錯誤物件 ---
                console.error('API Error:', error);
                result.value = null;

                const res = error.data || error;
                const status = error.status || 500;

                // 2. 抓取我們定義的 code 與 message
                const message = res.message || error.message || '處理失敗';
                const code = res.code || 'UNKNOWN'; // 從 data 裡面拿 code
                const conflictData = res.payload;   // 從 data 裡面拿 payload

                // 3. 根據 Status 分流
                switch (status) {
                    case 409: 
                        handleConflict(code, message, conflictData);
                        break;
                    
                    case 413:
                        errors.value = { file: ['檔案大小超過伺服器限制'] };
                        break;

                    case 503:
                        errors.value = { file: ['AI 服務忙碌中，請稍後再試'] };
                        break;

                    default:
                        // 400, 422, 500 等直接顯示訊息
                        errors.value = { file: [message] };
                }
            })
            .finally(() => {
                isLoading.value = false;
            });
        };


        const getRefLink = (ref: any) => {
            if (!ref) return '';
            const link = app.$router.resolve({ 
                name: 'reference-page', 
                params: { id: ref.id } 
            }).href;
            return `<a class="my-link" href="${link}" target="_blank">${ref.title}</a>`;
        };
        // [簡潔化] 專門處理 409 邏輯，依賴明確的 Code
        const handleConflict = (code: string, message: string, data: any) => {

            let errorMessage = '';

            switch (code) {
                case 'REF_HAS_USAGE': {
                    const link = getRefLink(data);
                    errorMessage = `${app.$t('validation.referenceUsagePrefix')} ${link}`;
                    break;
                }
                case 'REF_WITH_FILE': {
                    const link = getRefLink(data);
                    errorMessage = `${app.$t('validation.referenceHasFile')} ${link}`;
                    break;
                }
                case 'REF_EXISTS': {
                    // 情境：文獻已存在，系統自動補件成功 -> 關閉當前 Modal -> 開啟綁定 Modal
                    if (data) {
                        app.$store.commit('setBindReferenceData', data);
                        // 這裡是用 closeModal，因為我們還在 AI Import Modal 裡面
                        app.$store.commit('closeModal'); 
                        app.$store.commit('openModal', {
                            component: () => import('../modals/BindReferenceModal.vue'),
                        });
                        if (app.$toast) app.$toast.success('文獻已存在，已自動上傳 PDF 並轉至綁定流程');
                        return; 
                    }
                    errorMessage = '文獻已存在但資料讀取錯誤';
                    break;
                }
                default: // DRAFT_EXISTS or UNKNOWN_TYPE
                    errorMessage = message;
                    break;
            }

            errors.value = { file: [errorMessage] };
        };


        const onSetToForm = () => {

            if (!result.value) return;

            const referenceData = {
                 ...result.value, // 展開所有後端回傳屬性 (已是 camelCase)
                 authors: getFinalAuthorsPossible(), // 覆蓋處理過的 authors
                 fromAiImport: true
             };
            
            // 將資料存到 store 中
            app.$store.commit('setReferencePresetData', referenceData);

            // 關閉當前 modal
            app.$store.commit('closeModal');

            // 打開 ReferenceLayer
            app.$store.commit('layer/ADD', {
                template: () => import('../layers/ReferenceLayer.vue'),
                props: {
                    usePresetFromStore: true,
                },
                events: {
                    onAfterSubmit: (data) => {
                        console.log('Reference created:', data);
                        // 將資料存到 store 中
                        app.$store.commit('setBindReferenceData', data);

                        // // AI 導入特殊的後續處理
                        app.$toast && app.$toast.success('文獻已成功發布！');

                        // 這邊要直接打開另外一個綁定modal
                        app.$store.commit('layer/CLOSE');

                        app.$store.commit('openModal', {
                            component: () => import('../modals/BindReferenceModal.vue'),
                        });

                        if (props.onOverwrite && typeof props.onOverwrite === 'function') {
                            props.onOverwrite(data);
                        }
                    },
                }
            });
        };

        const onAuthorSelect = (authorIndex: number, selectedPersons: any[]) => {

            selectedAuthors.value[authorIndex] = selectedPersons;
        };

        const getFinalAuthorsPossible = () => {
            const finalAuthors: any[] = [];
            
            result.value?.authors?.forEach((originalAuthor, index) => {
                if (selectedAuthors.value[index]) {
                    // 如果是array，取第一個；如果不是array，直接使用
                    const selectedAuthor = Array.isArray(selectedAuthors.value[index]) 
                        ? selectedAuthors.value[index][0] 
                        : selectedAuthors.value[index];
                    finalAuthors.push(selectedAuthor);
                } else if (result.value?.authorsPossible && result.value.authorsPossible[index]) {
                    finalAuthors.push(result.value.authorsPossible[index]);
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
            uploadedFile,
            result,
            errors,
            isLoading,
            submitted,
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

<style scoped>
/* 主層清單顯示實心圓點 */
.guide-list {
  list-style-type: disc ;
  padding-left: 1.25rem; /* 必須加上內縮，否則圓點會跑到容器外被吃掉 */
}

/* 子層情境清單顯示空心圓點（視覺更有層次） */
.sub-list {
  list-style-type: circle;
  padding-left: 1.25rem;
  margin-top: 0.25rem;
  margin-bottom: 0.25rem;
}

/* 讓列表間距稍微拉開，閱讀更舒適 */
.guide-list > li {
  margin-bottom: 0.5rem;
}
</style>