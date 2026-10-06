<template>
    <table class="table w-full border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-4 py-2 text-left">{{ $t('aiImport.author.inReference') }}</th>
                <th class="border border-gray-300 px-4 py-2 text-left">{{ $t('aiImport.author.matched') }}</th>
                <th class="border border-gray-300 px-4 py-2 text-center">{{ $t('aiImport.author.selectOther') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(author, key) in authors" :key="key" :class="{ 'bg-yellow-50': needsConfirm(key) }">
                <!-- 文獻中作者 -->
                <td class="border border-gray-300 px-4 py-2">
                    <span :class="{ 'text-red-500': !current(key) }" class="font-bold">
                        {{ author.family }}, {{ author.given }}
                    </span>
                </td>

                <!-- 對應現有資料庫人名 -->
                <td class="border border-gray-300 px-4 py-2">
                    <!-- 多筆候選：列出讓使用者確認，不自動帶入 -->
                    <div v-if="candidatesOf(key).length > 1">
                        <p class="text-orange-600 text-sm mb-1">
                            {{ $t('aiImport.author.multiple', { count: candidatesOf(key).length }) }}
                        </p>
                        <label v-for="person in candidatesOf(key)" :key="person.id"
                               class="flex items-start gap-2 mb-1 cursor-pointer">
                            <input type="radio" class="mt-1"
                                   :name="`author-${key}`"
                                   :checked="current(key) && current(key).id === person.id"
                                   @change="select(key, person)"/>
                            <span>
                                <router-link target="_blank" :to="{ name: 'person-page', params: { id: person.id } }" class="my-link">
                                    {{ person.fullName }}
                                    <span v-if="person.abbreviationName">({{ person.abbreviationName }})</span>
                                </router-link>
                                <span class="help has-text-grey-light">{{ detail(person) }}</span>
                            </span>
                        </label>
                    </div>

                    <!-- 單筆 -->
                    <div v-else-if="candidatesOf(key).length === 1">
                        <router-link target="_blank" :to="{ name: 'person-page', params: { id: candidatesOf(key)[0].id } }" class="my-link">
                            {{ candidatesOf(key)[0].fullName }}
                            <span v-if="candidatesOf(key)[0].abbreviationName">({{ candidatesOf(key)[0].abbreviationName }})</span>
                        </router-link>
                        <span class="help has-text-grey-light">{{ detail(candidatesOf(key)[0]) }}</span>
                    </div>

                    <span v-else class="text-gray-500 italic">{{ $t('aiImport.author.notFound') }}</span>

                    <!-- 使用者從右側選了候選以外的人 -->
                    <p v-if="isOutsideCandidates(key)" class="text-sm mt-1">
                        {{ $t('aiImport.author.selected') }}
                        <router-link target="_blank" :to="{ name: 'person-page', params: { id: value[key].id } }" class="my-link">
                            {{ value[key].fullName }}
                        </router-link>
                    </p>
                </td>

                <!-- 選擇其他人名 -->
                <td class="border border-gray-300 px-4 py-2 text-center">
                    <person-select
                        class="w-[180px]"
                        :multiple="false"
                        :errors="errors"
                        :value="isOutsideCandidates(key) ? [value[key]] : []"
                        :authorData="{ given: author.given, family: author.family, index: key }"
                        @input="(v) => select(key, Array.isArray(v) ? v[0] : v)"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import PersonSelect from './selects/PersonSelect.vue';

export default {
    components: { PersonSelect },
    props: {
        authors: { type: Array, default: () => [] },
        authorsPossible: { type: [Array, Object], default: () => [] },
        authorsCandidates: { type: [Array, Object], default: () => [] },
        // 使用者選擇結果 { [index]: person }
        value: { type: Object, default: () => ({}) },
        errors: { type: Array, default: undefined },
    },
    methods: {
        candidatesOf(key) {
            const list = this.authorsCandidates?.[key];
            if (Array.isArray(list)) return list;
            // 舊格式相容：只有 authorsPossible
            const p = this.authorsPossible?.[key];
            return p ? [p] : [];
        },
        needsConfirm(key) {
            return this.candidatesOf(key).length > 1 && !this.value[key];
        },
        // 目前生效的作者：使用者選擇 > 唯一候選；多筆候選時不自動帶入
        current(key) {
            if (this.value[key]) return this.value[key];
            const list = this.candidatesOf(key);
            return list.length === 1 ? list[0] : null;
        },
        isOutsideCandidates(key) {
            const v = this.value[key];
            return !!v && !this.candidatesOf(key).some((p) => p.id === v.id);
        },
        select(key, person) {
            const next = { ...this.value };
            if (person) {
                next[key] = person;
            } else {
                delete next[key];
            }
            this.$emit('input', next);
        },
        detail(person) {
            const locale = this.$i18n.locale();
            return [
                `ID: ${person.id}`,
                person.originalFullName,
                person.yearLife,
                person.nationality?.display?.[locale],
                person.biologicalGroup,
            ].filter(Boolean).join(' | ');
        },
    },
};

</script>
