<template>
    <table class="table is-fullwidth is-hoverable has-text-left in-tab-content">
        <thead>
        <tr>
            <th>{{ $t('reference.type') }}</th>
            <th>{{ $t('reference.authority') }}</th>
            <th class="no-wrap">{{ $t('common.publishYear') }}</th>
            <th>{{ $t('reference.articleTitle') }}</th>
            <th>{{ $t('reference.publishTitle') }}</th>
            <th>{{ $t('reference.language') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="r in references">
            <!-- 文獻類型 -->
            <td class="no-wrap"
                v-text="$t(`reference.typeOptions.${r.type}`)"></td>

            <!-- 作者 -->
            <td class="has-text-left" style="white-space: nowrap">
                <p v-for="author in r.authors"
                   class="no-wrap"
                   v-text="fullNameAbbreviation(author)"/>
            </td>

            <!-- 發表年份 -->
            <td v-text="r.publishYear"></td>

            <!-- 文章標題 -->
            <td class="has-text-left">
                <router-link v-if="r.properties.articleTitle"
                             :to="{name: 'reference-page', params: {id: r.id}}"
                             class="my-link">
                    <span class="has-text-weight-bold" v-text="r.properties.articleTitle"/>
                </router-link>
            </td>

            <!-- 發表文獻 -->
            <td class="has-text-left">
                <router-link :to="{name: 'reference-page', params: {id: r.id}}" class="my-link">
                    {{ subTitle(r) }}
                </router-link>
            </td>

            <!-- 語言 -->
            <td class="no-wrap">
                <span v-if="r.language">
                    {{ $t(`reference.languages.${r.language}`) }}
                </span>
            </td>
        </tr>
        </tbody>
    </table>
</template>
<script>
import { fullNameAbbreviation } from '../../../utils/preview/person';
import { subTitle } from '../../../utils/preview/reference';

export default {
    data() {
        return {
            references: [],
        };
    },
    async mounted() {
        const { data } = await this.axios.get(`/persons/${this.$route.params.id}/references`);
        this.references = data;
        if (data.length === 0) {
            this.onHide();
        }
    },
    methods: {
        fullNameAbbreviation: (author) => fullNameAbbreviation(author),
        subTitle: (r) => subTitle(r, false),
        onHide() {
            this.$emit('hide', 'reference');
        },
    },
};
</script>
