<template>
    <div>
        <div class="px-16 py-12 w-[768px]">
            <div class="flex gap-2">
                <p class="leading-10">
                    <span class="font-bold">DOI:</span>
                    http://dx.doi.org/
                </p>
                <general-input v-model="doi" :errors="errors.doi" class="grow"/>
                <button class="button" v-on:click="onFetchDoi">{{ $t('reference.search') }}</button>
            </div>

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


                        <!-- <tr>
                            <td class="no-wrap">{{ $t('reference.author') }}</td>
                            <td>
                                <div class="flex-col">
                                    <div v-for="(author, key) in result.authors">
                                        <p :class="{'text-red-500': !result.authorsPossible[key]}"
                                           class="font-bold mb-2">{{ author.family }}, {{ author.given }}</p>
                                        <div v-if="!!result.authorsPossible[key]" class="w-full mb-2 flex gap-3">
                                            <span class="font-bold">&nbsp;&nbsp;&nbsp;&nbsp;{{result.authorsPossible[key].id}}:&nbsp;</span>
                                            <span class="space-x-44">
                                                {{ result.authorsPossible[key]['fullName'] }}
                                                ({{ result.authorsPossible[key].abbreviationName }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr> -->
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
            <button :disabled="!result" class="button" v-on:click="onSetToForm">{{ $t('reference.fillInByDoi') }}
            </button>
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
    name: 'doi-modal',
    props: {
        onOverwrite: {
            type: Function as PropType<(data) => void>,
            required: true,
        },
    },
    setup(props, context) {
        const app: any = context.root;

        const axios: any = inject('axios');
        const doi = ref<string>('');
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
        } | null>(null);
        const errors = ref<object>({});
        const isLoading = ref<boolean>(false);
        
        // 追蹤每個作者位置選擇的人員
        const selectedAuthors = ref<{[key: number]: any[]}>({});


        const onFetchDoi = () => {

            
            isLoading.value = true;
            errors.value = {};
            axios
                .get('/doi', { params: { doi: doi.value } })
                .then(({ data }) => {
                    // console.log(data);
                    result.value = data;
                    errors.value = {};
                    isLoading.value = false;
                })
                .catch(({ errors: e, status, message }) => {
                    result.value = null;
                    if (status === 422) {
                        errors.value = e;
                    } else if (status === 404) {
                        errors.value = { doi: [message] };
                    }
                    isLoading.value = false;
                });
        };

        const onSetToForm = () => {
            if (!result.value) return;

            // 整合最終的作者清單
            const finalAuthorsPossible = getFinalAuthorsPossible();

            props.onOverwrite({
                type: result.value.type,
                authors: finalAuthorsPossible, // 傳遞最終整合的作者清單
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
            });
            app.$store.commit('closeModal');
        };

        // 處理作者選擇
        const onAuthorSelect = (authorIndex: number, selectedPersons: any[]) => {
            selectedAuthors.value[authorIndex] = Array.isArray(selectedPersons) 
                ? selectedPersons[0] 
                : selectedPersons;
        };

        // 整合最終的作者清單
        const getFinalAuthorsPossible = () => {
            const finalAuthors: any[] = [];
            
            // 按照原始作者順序處理
            result.value?.authors?.forEach((originalAuthor, index) => {
                // 1. 優先使用下拉選單選擇的人名
                if (selectedAuthors.value[index]) {
                    finalAuthors.push(selectedAuthors.value[index]);
                } 
                // 2. 如果下拉選單沒有選擇，但有對應的現有資料庫人名
                else if (result.value?.authorsPossible && result.value.authorsPossible[index]) {
                    finalAuthors.push(result.value.authorsPossible[index]);
                }
                // 如果都沒有，則跳過該作者
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
            isLoading,
            doi,
            result,
            errors,
            selectedAuthors,
            typeDisplay,
            onFetchDoi,
            onSetToForm,
            onClose,
            onAuthorSelect,
            getFinalAuthorsPossible,
        };
    },
    components: { Loading, GeneralInput, PersonSelect },
});
</script>