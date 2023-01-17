<template>
    <div class="layer-wrapper">
        <div class="layer-header">
            <button class="button is-text is-inline" v-on:click="onClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="layer-content box">
            <div class="flex gap-2">
                <p class="leading-10">
                    <span class="font-bold">DOI:</span>
                    http://dx.doi.org/
                </p>
                <general-input v-model="doi" :errors="errors.doi" class="grow"/>
                <button class="button" v-on:click="onFetchDoi">搜尋</button>
            </div>

            <div class="w-full">
                <div v-if="isLoading" class="flex w-full items-center justify-center">
                    <loading></loading>
                </div>
                <div v-else-if="!!result" class="py-4">
                    <table class="table w-full">
                        <tr>
                            <td class="no-wrap">文獻類型</td>
                            <td>{{ typeDisplay(result.type) }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">作者</td>
                            <td>
                                <div class="flex-col">
                                    <div v-for="(author, key) in result.authors">
                                        <p :class="{'text-red-500': !result.authorsPossible[key]}"
                                           class="font-bold mb-2">{{ author.family }}, {{ author.given }}</p>
                                        <div v-if="!!result.authorsPossible[key]" class="w-full mb-2 gap-3">
                                            <span class="font-bold">&nbsp;&nbsp;&nbsp;&nbsp;{{
                                                    result.authorsPossible[key].id
                                                }}:&nbsp;</span>
                                            <span class="space-x-44">
                                                {{ result.authorsPossible[key]['fullName'] }}
                                                ({{ result.authorsPossible[key].abbreviationName }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="no-wrap">發表年份</td>
                            <td>{{ result.publishYear }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">文章標題</td>
                            <td>{{ result.articleTitle }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">期刊/書名</td>
                            <td>{{ result.bookTitle }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">期刊/書名縮寫</td>
                            <td>{{ result.bookTitleAbbreviation }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">卷號/部冊號</td>
                            <td>{{ result.volume }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">期號</td>
                            <td>{{ result.issue }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">頁碼範圍</td>
                            <td>{{ result.page }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">DOI</td>
                            <td>{{ result.doi }}</td>
                        </tr>
                        <tr>
                            <td class="no-wrap">連結URL</td>
                            <td>{{ result.url }}</td>
                        </tr>

                        <tr>
                            <td class="no-wrap">語言</td>
                            <td>{{ result.language }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="layer-footer">
            <button :disabled="!result" class="button" v-on:click="onSetToForm">填入</button>
            <button class="button"
                    v-on:click="onClose"
                    v-text="$t('forms.actions.close')">
            </button>
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
            type: number
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

        const onFetchDoi = () => {
            isLoading.value = true;
            errors.value = {};
            axios
                .get('/doi', { params: { doi: doi.value } })
                .then(({ data }) => {
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

        const onClose = () => {
            app.$store.commit('layer/CLOSE');
        };

        const onSetToForm = () => {
            if (!result.value) return;

            props.onOverwrite({
                type: result.value.type,
                authors: Object.values(result.value.authorsPossible).filter(Boolean),
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

            onClose();
        };
        const typeDisplay = (type) => {
            const typeObject = referenceTypes.find((t) => t.value === type);
            return typeObject ? app.$t(`forms.reference.typeOptions.${typeObject.value}`) : '';
        };

        return {
            isLoading,
            doi,
            result,
            errors,
            typeDisplay,
            onFetchDoi,
            onSetToForm,
            onClose,
        };
    },
    components: { Loading, GeneralInput },
});
</script>
<style lang="scss">
.layer-wrapper {
    height: 100%;

    .layer-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: white;
        padding: 1.5rem;
        height: 5.5rem;
    }

    .layer-content {
        overflow-x: hidden;
        height: calc(100% - 11rem);
        position: relative;
    }

    .layer-footer {
        position: fixed;
        bottom: 0;
        height: 5.5rem;
        width: 100%;
        padding: 1.5rem;
    }
}
</style>
