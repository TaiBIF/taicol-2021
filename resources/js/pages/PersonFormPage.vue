<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4 grow">
                <div class="py-3 flex items-center">
                    <p v-if="!person" class="ml-3 font-bold text-3xl inline">{{ $t('person.create') }}</p>
                    <p v-else class="ml-3 font-bold text-3xl inline" v-text="$t('person.edit')"/>
                </div>
                <simple-person-form ref="form" 
                                    :on-after-submit="onAfterSubmit" 
                                    :on-loading-change="(v) => isLoading = v"
                                    :presetData="person"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button :class="{'is-loading': isLoading}"
                            class="button m-0"
                            v-on:click="goBack()"
                            v-text="$t('common.goBack')"/>
                    <button :class="{'is-loading': isLoading}"
                            class="button"
                            v-on:click="onSubmit(true)"
                            v-text="$t('common.save')"/>
                </div>
            </div>
        </div>
    </page>
</template>
<script>
import SimplePersonForm from '../components/forms/SimplePersonForm.vue';
import Page from './Page.vue';
import { personDetailResource } from '../utils/models/persons';

export default {
    data() {
        return {
            person: null,
            isLoading: false,
        };
    },
    methods: {
        goBack(){
            window.history.back();
        },
        async onPreload() {
            try {
                const { data } = await this.axios.get(`/persons/${this.$route.params.id}`);
                this.person = personDetailResource(data);
                return 200;
            } catch (e) {
                return e.status;
            }
        },
        onAfterSubmit(person) {
            this.$router.push({ name: 'person-page', params: { id: person.id } });
        },
        onSubmit() {
            this.$refs.form.onSubmit();
        },
    },
    components: {
        Page,
        SimplePersonForm,
    },
};
</script>
