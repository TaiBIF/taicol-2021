<template>
    <page :preload="onPreload" class="container">
        <div class="flex flex-col h-full py-6 mb-4">
            <div class="box overflow-y-auto px-10 py-4 grow">
                <simple-person-form ref="form" :on-after-submit="onAfterSubmit" :presetData="person"/>
            </div>
            <div class="form-footer">
                <div class="buttons is-right">
                    <button class="button"
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
        };
    },
    methods: {
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
            this.$refs.form.onSubmit(true);
        },
    },
    components: {
        Page,
        SimplePersonForm,
    },
};
</script>
