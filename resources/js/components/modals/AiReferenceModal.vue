<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <p class="title text-center"><b>{{ $t('aiImport.guide.title') }}</b></p>

            <!-- 1. 檔案上傳區域 -->
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

            <!-- 2. Guide 說明區域（移至上傳區域下方） -->
            <div v-if="!submitted" class="mt-6">
                <ul class="guide-list">
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
            </div>

            <!-- 3. 上傳後動態產生的內容（載入狀態與解析結果表格） -->
            <div class="min-h-3/5 flex w-full">
                <div v-if="isLoading" class="flex w-full items-center justify-center">
                    <loading></loading>
                </div>
                <div v-else-if="!!result" class="py-4 w-full">
                    <!-- 有找到可能的既有文獻：先請使用者確認 -->
                    <template v-if="hasSimilar">
                        <p class="font-bold mb-2">{{ $t('aiImport.choice.title') }}</p>
                        <div class="flex flex-col gap-1 mb-4">
                            <label class="cursor-pointer">
                                <input type="radio" value="similar" v-model="choice" /> {{ $t('aiImport.choice.similar') }}
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="manual" v-model="choice" /> {{ $t('aiImport.choice.manual') }}
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="new" v-model="choice" /> {{ $t('aiImport.choice.new') }}
                            </label>
                        </div>
                    </template>

                    <!-- 沒找到可能的既有文獻：直接新增，保留手動搜尋入口 -->
                    <p v-else class="mb-4">
                        <a v-if="choice === 'new'" class="my-link" v-on:click="choice = 'manual'">{{ $t('aiImport.choice.manualLink') }}</a>
                        <a v-else class="my-link" v-on:click="choice = 'new'">{{ $t('aiImport.choice.backToNew') }}</a>
                    </p>

                    <!-- A. 相似文獻 -->
                    <div v-if="choice === 'similar'" class="mb-6 p-4 border rounded">
                        <p class="font-bold mb-1">{{ $t('aiImport.similar.title', { count: result.similarReferences.length }) }}</p>
                        <p class="help mb-3">{{ $t('aiImport.similar.hint') }}</p>
                        <label v-for="item in result.similarReferences" :key="item.id"
                               class="flex items-start gap-2 mb-2"
                               :class="isSelectable(item) ? 'cursor-pointer' : 'opacity-60'">
                            <input type="radio" class="mt-1" :value="item" v-model="selectedSimilar" :disabled="!isSelectable(item)" />
                            <span>
                                <router-link target="_blank" :to="{ name: 'reference-page', params: { id: item.id } }" class="my-link">
                                    {{ item.title }}
                                </router-link>
                                <span v-if="item.exact" class="tag is-info is-light ml-1">{{ $t('aiImport.similar.exact') }}</span>
                                <span class="tag ml-1" :class="statusTag(item.status).cls">{{ statusTag(item.status).text }}</span>
                                <span v-if="item.subtitle" class="help has-text-grey-light">{{ item.subtitle }}</span>
                            </span>
                        </label>
                        <div class="flex gap-2 mt-2">
                            <button class="button" :disabled="!selectedSimilar" v-on:click="selectedSimilar = null">{{ $t('aiImport.action.clearSelection') }}</button>
                            <button class="button is-primary" :class="{ 'is-loading': isBinding }" :disabled="!selectedSimilar || isBinding"
                                    v-on:click="onBindReference(selectedSimilar)">{{ $t('aiImport.action.bind') }}</button>
                        </div>
                    </div>

                    <!-- B. 手動搜尋 -->
                    <div v-if="choice === 'manual'" class="mb-6">
                        <div class="flex gap-2">
                            <reference-select
                                class="grow"
                                hide-create
                                :value="manualReference"
                                @input="(v) => { manualReference = v; conflict = null; bindError = ''; }"
                            />
                            <button class="button is-primary" :class="{ 'is-loading': isBinding }" :disabled="!manualReference || isBinding"
                                    v-on:click="onBindReference(manualReference)">{{ $t('aiImport.action.bind') }}</button>
                        </div>
                        <p class="help mt-1">{{ $t('aiImport.manual.hint') }}</p>
                    </div>

                    <!-- A / B 綁定時遇到的狀態：已有 PDF 確認覆蓋、或無法綁定 -->
                    <div v-if="choice !== 'new' && (conflict || bindError)" class="box mb-6">
                        <div v-if="conflict" v-html="conflictMessage"></div>
                        <div v-if="conflict && conflict.code === 'REF_WITH_FILE'" class="mt-2">
                            <p>{{ $t('aiImport.overwrite.existingPdf') }}<a v-if="conflict.payload.fileUrl" :href="conflict.payload.fileUrl" target="_blank" class="my-link">{{ $t('aiImport.overwrite.openExisting') }}</a></p>
                            <p>{{ $t('aiImport.overwrite.uploadedPdf') }}<a v-if="result.fileUrl" :href="result.fileUrl" target="_blank" class="my-link">{{ $t('aiImport.overwrite.openUploaded') }}</a></p>
                            <div class="flex gap-2 mt-2">
                                <button class="button is-primary" :class="{ 'is-loading': isBinding }" :disabled="isBinding"
                                        v-on:click="onBindReference(conflict.payload, true)">{{ $t('aiImport.overwrite.confirm') }}</button>
                                <button class="button" v-on:click="conflict = null">{{ $t('common.cancel') }}</button>
                            </div>
                        </div>
                        <p v-else-if="conflict" class="help mt-2">{{ $t('aiImport.conflict.reselect') }}</p>
                        <p v-if="bindError" class="help is-danger mt-1" v-html="bindError"></p>
                    </div>

                    <!-- C. 新增文獻 -->
                    <template v-if="choice === 'new'">
                    <p class="font-bold mb-2">{{ $t('aiImport.preview.title') }}</p>
                    <table class="table">
                        <tr>
                            <td class="no-wrap">{{ $t('reference.type') }}</td>
                            <td>{{ typeDisplay(result.type) }}</td>
                        </tr>

                        <tr>
                            <td class="no-wrap">{{ $t('reference.author') }}</td>
                            <td>
                                <!-- 作者比對：多筆相似人名時讓使用者確認 -->
                                <author-match-table
                                    :authors="result.authors"
                                    :authors-possible="result.authorsPossible"
                                    :authors-candidates="result.authorsCandidates"
                                    :errors="errors.authors"
                                    v-model="selectedAuthors"
                                />
                                <p v-if="authorError" class="help is-danger mt-1">{{ authorError }}</p>
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
                    </template>
                </div>
            </div>
        </div>
        <div class="flex justify-end sticky bottom-0 p-4 bg-white border-t gap-2">
            <button v-if="!!result && choice === 'new'" class="button is-primary" v-on:click="onSetToForm">{{ $t('reference.fillInByDoi') }}</button>
            <button class="button" v-on:click="onClose">{{ $t('common.close') }}</button>
        </div>
    </div>
</template>

<script lang="ts">
import {
    computed, defineComponent, inject, PropType, ref, watch,
} from '@vue/composition-api';
import GeneralInput from '../GeneralInput.vue';
import Loading from '../Loading.vue';
import referenceTypes from '../../utils/options/referenceTypes';
import AuthorMatchTable from '../AuthorMatchTable.vue';
import { resolveAuthors } from '../../utils/resolveAuthors';
import ReferenceSelect from '../selects/ReferenceSelect.vue';
import { serverMessage } from '../../utils/serverMessage';

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
        const selectedAuthors = ref<{[key: number]: any}>({});
        const authorError = ref<string>('');
        const selectedSimilar = ref<any>(null);
        // similar：相似文獻 / manual：手動搜尋 / new：新增文獻
        const choice = ref<string>('new');
        // 綁定時遇到的狀態 { code, payload }
        const conflict = ref<any>(null);
        const manualReference = ref<any>(null);
        const isBinding = ref<boolean>(false);
        const bindError = ref<string>('');

        // 處理檔案上傳
        // const onSetFile = (event) => {
        //     const file = event.target.files[0];
        //     uploadedFile.value = file;
        // };

        const onFetchReferenceAI = () => {

            submitted.value = true; // 按下匯入後就隱藏note

            // 1. 前端基本驗證
            if (!uploadedFile.value) {
                errors.value = { file: [app.$t('aiImport.error.noFile')] };
                return;
            }

            // 檢查檔名是否含有非 ASCII 字符
            // eslint-disable-next-line no-control-regex
            if (/[^\x00-\x7F]/.test(uploadedFile.value.name)) {
                errors.value = { file: [app.$t('aiImport.error.invalidFileName')] };
                return;
            }

            isLoading.value = true;
            errors.value = {};
            result.value = null;
            selectedSimilar.value = null;
            manualReference.value = null;
            conflict.value = null;
            choice.value = 'new';
            selectedAuthors.value = {};
            authorError.value = '';
            bindError.value = '';

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
                // 有可能的既有文獻時預設先請使用者確認
                const list = data.data?.similarReferences || [];
                choice.value = list.length ? 'similar' : 'new';
                // 完全相符且可綁定者預設選取（等 choice 的 watch 清空選取後再設定）
                const exactItem = list.find((item) => item.exact && isSelectable(item));
                if (exactItem) {
                    app.$nextTick(() => { selectedSimilar.value = exactItem; });
                }
            })
            .catch((error) => {
            // --- 重構重點：正確解析 Axios 錯誤物件 ---
                console.error('API Error:', error);
                result.value = null;

                const res = error.data || error;
                const status = error.status || 500;

                // 2. 抓取我們定義的 code 與 message
                const message = serverMessage({ ...error, ...res }, app.$t('aiImport.error.processFailed'));
                const code = res.code || 'UNKNOWN'; // 從 data 裡面拿 code
                const conflictData = res.payload;   // 從 data 裡面拿 payload

                // 3. 根據 Status 分流
                switch (status) {
                    case 409: 
                        handleConflict(code, message, conflictData);
                        break;
                    
                    case 413:
                        errors.value = { file: [app.$t('aiImport.error.fileTooLarge')] };
                        break;

                    case 503:
                        errors.value = { file: [message || app.$t('aiImport.error.aiBusy')] };
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
                        if (app.$toast) app.$toast.success(app.$t('aiImport.toast.autoBound'));
                        return; 
                    }
                    errorMessage = app.$t('aiImport.error.loadExisting');
                    break;
                }
                default: // DRAFT_EXISTS or UNKNOWN_TYPE
                    errorMessage = message;
                    break;
            }

            errors.value = { file: [errorMessage] };
        };


        // 情形四、五：綁定既有文獻 → 轉至學名使用解析
        const goToBindModal = (reference: any) => {
            app.$store.commit('setBindReferenceData', reference);
            app.$store.commit('closeModal');
            app.$store.commit('openModal', {
                component: () => import('../modals/BindReferenceModal.vue'),
            });
        };

        const hasSimilar = computed(() => (result.value?.similarReferences || []).length > 0);

        // 相似文獻可否選取：草稿、已有學名使用、解析中不可選
        const isSelectable = (item: any) => !item.status || ['bindable', 'has_file'].includes(item.status);

        const statusTag = (status: string) => {
            const cls = {
                bindable:   'is-success is-light',
                has_file:   'is-warning is-light',
                has_usage:  'is-danger is-light',
                processing: 'is-danger is-light',
                draft:      'is-light',
            }[status];

            return cls
                ? { text: app.$t(`aiImport.status.${status}`), cls }
                : { text: '', cls: 'is-hidden' };
        };

        const conflictMessage = computed(() => {
            if (!conflict.value) return '';
            const { code, payload } = conflict.value;
            const link = getRefLink(payload);
            switch (code) {
                case 'REF_WITH_FILE':
                    return `${link}<br>${app.$t('aiImport.conflict.hasFile')}`;
                case 'REF_HAS_USAGE':
                    return `${app.$t('validation.referenceUsagePrefix')} ${link}`;
                case 'REF_PROCESSING':
                    return `${app.$t('aiImport.conflict.processing')}${link}`;
                case 'DRAFT_EXISTS':
                    return `${app.$t('aiImport.conflict.draft')}${link}`;
                default:
                    return link;
            }
        });

        // 切換選項時清除先前的選取與狀態
        watch(choice, () => {
            selectedSimilar.value = null;
            manualReference.value = null;
            conflict.value = null;
            bindError.value = '';
        });
        watch(selectedSimilar, () => {
            conflict.value = null;
            bindError.value = '';
        });

        // 情形四、五：綁定既有文獻 → 轉至學名使用解析
        // overwrite = true：使用者已確認覆蓋原有 PDF
        const onBindReference = (reference: any, overwrite = false) => {
            const aiLogId = result.value?.aiLogId || conflict.value?.payload?.aiLogId;
            if (!reference || !aiLogId) return;

            isBinding.value = true;
            bindError.value = '';

            axios.post('/fetch/reference/ai/bind', {
                referenceId: reference.id,
                aiLogId,
                overwrite,
            })
            .then(({ data }) => {
                conflict.value = null;
                goToBindModal(data.data);
                if (app.$toast) app.$toast.success(app.$t('aiImport.toast.bound'));
            })
            .catch((err) => {
                const { status, data } = err;
                if (status === 409 && data && data.code) {
                    conflict.value = { code: data.code, payload: data.payload };
                } else {
                    bindError.value = serverMessage(err, app.$t('aiImport.error.bindFailed'));
                }
            })
            .finally(() => {
                isBinding.value = false;
            });
        };

        const onSetToForm = () => {

            if (!result.value) return;

            const { final, unresolved } = getFinalAuthors();
            if (unresolved.length) {
                authorError.value = app.$t('aiImport.author.unresolved', { names: unresolved.join(app.$i18n.locale() === 'zh-tw' ? '、' : ', ') });
                return;
            }
            authorError.value = '';

            const referenceData = {
                 ...result.value, // 展開所有後端回傳屬性 (已是 camelCase)
                 authors: final, // 覆蓋處理過的 authors
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
                        app.$toast && app.$toast.success(app.$t('aiImport.toast.published'));

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

        const getFinalAuthors = () => resolveAuthors(
            result.value?.authors,
            result.value?.authorsPossible,
            result.value?.authorsCandidates,
            selectedAuthors.value,
        );

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
            selectedSimilar,
            manualReference,
            choice,
            conflict,
            conflictMessage,
            hasSimilar,
            isSelectable,
            statusTag,
            isBinding,
            bindError,
            onBindReference,
            typeDisplay,
            onFetchReferenceAI,
            onSetToForm,
            onClose,
            authorError,
        };
    },
    components: { Loading, GeneralInput, AuthorMatchTable, ReferenceSelect },
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