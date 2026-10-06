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
import AuthorMatchTable from '../AuthorMatchTable.vue';
import { resolveAuthors } from '../../utils/resolveAuthors';

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
            authorsCandidates?: object,
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
        const selectedAuthors = ref<{[key: number]: any}>({});
        const authorError = ref<string>('');


        const onFetchDoi = () => {

            
            isLoading.value = true;
            errors.value = {};
            axios
                .get('/doi', { params: { doi: doi.value } })
                .then(({ data }) => {
                    // console.log(data);
                    result.value = data;
                    selectedAuthors.value = {};
                    authorError.value = '';
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

            // 整合最終的作者清單（多筆相似人名需先確認）
            const { final: finalAuthorsPossible, unresolved } = resolveAuthors(
                result.value.authors,
                result.value.authorsPossible,
                result.value.authorsCandidates,
                selectedAuthors.value,
            );
            if (unresolved.length) {
                authorError.value = app.$t('aiImport.author.unresolved', { names: unresolved.join(app.$i18n.locale() === 'zh-tw' ? '、' : ', ') });
                return;
            }
            authorError.value = '';

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
            authorError,
        };
    },
    components: { Loading, GeneralInput, AuthorMatchTable },
});
</script>