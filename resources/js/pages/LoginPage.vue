<template>
    <div class="container flex justify-center items-center">
        <div class="box mt-[3rem] w-[768px]">
            <p class="title is-3" v-text="$t('loginPage.login')"/>
            <div class="form">
                <div class="field">
                    <label class="label" v-text="$t('loginPage.email')"/>
                    <div class="control">
                        <general-input v-model="email" :errors="errors.email"/>
                    </div>
                </div>

                <div class="field">
                    <label class="label" v-text="$t('loginPage.password')"/>
                    <div class="control">
                        <general-input v-model="password"
                                       :errors="errors.password"
                                       type="password"/>
                    </div>
                </div>
                <div class="field">
                    <div class="buttons">
                        <button :class="{'is-loading': isLoading}"
                                class="button has-text-white has-background-dark is-fullwidth"
                                v-on:click="onLogin"
                                v-text="$t('loginPage.login')"
                        />
                        <router-link :to="{name: 'register'}"
                                     class="button is-text is-fullwidth is-outlined"
                                     v-text="$t('loginPage.createAccount')"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import {
    defineComponent, onBeforeUnmount, onMounted, ref,
} from '@vue/composition-api';

import GeneralInput from '../components/GeneralInput.vue';

export default defineComponent({
    setup(props, context) {
        const app: any = context.root;

        const route = app.$route;
        const router = app.$router;
        const store = app.$store;

        const isLoading = ref<boolean>(false);
        const errors = ref({});

        const email = ref<string>('');
        const password = ref<string>('');

        const onLogin = () => {
            isLoading.value = true;

            const params = {
                email: email.value,
                password: password.value,
                device: navigator?.userAgent || 'unknown',
            };

            store.dispatch('auth/login', params).then(() => {
                const redirect = route.query.redirect || '';

                if (redirect) {
                    router.replace(redirect);
                } else {
                    router.replace({ name: 'index' });
                }
            }).catch(({ errors: es }) => {
                isLoading.value = false;
                errors.value = es;
            });
        };

        const triggerLogin = (event) => {
            if (event.keyCode === 13) {
                onLogin();
            }
        };

        onMounted(() => {
            window.addEventListener('keydown', triggerLogin);
        });

        onBeforeUnmount(() => {
            window.removeEventListener('keydown', triggerLogin);
        });
        return {
            isLoading,
            errors,
            email,
            password,
            triggerLogin,
            onLogin,
        };
    },
    components: { GeneralInput },
});
</script>
