<template>
    <div class="container">
        <div class="box">
            <p class="title is-3" v-text="$t('functions.login')"/>
            <div class="form">
                <div class="field">
                    <label class="label" v-text="$t('forms.person.email')"/>
                    <div class="control">
                        <general-input v-model="email" :errors="errors.email"/>
                    </div>
                </div>

                <div class="field">
                    <label class="label" v-text="$t('forms.person.password')"/>
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
                                v-text="$t('functions.login')"
                        />
                        <router-link class="button is-text is-fullwidth is-outlined"
                                     to="/register" v-text="$t('forms.person.createAccount')"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import GeneralInput from '../components/GeneralInput';

    export default {
        data() {
            return {
                isLoading: false,
                errors: {},
                email: '',
                password: '',
            };
        },
        mounted() {
            window.addEventListener('keydown', this.triggerLogin);
        },
        beforeDestroy() {
            window.removeEventListener('keydown', this.triggerLogin);
        },
        methods: {
            triggerLogin(event) {
                if (event.keyCode === 13) {
                    this.onLogin();
                }
            },
            onLogin() {
                this.isLoading = true;
                this.$store.dispatch('auth/login', {
                    email: this.email,
                    password: this.password,
                    device: navigator?.userAgent || 'unknown',
                }).then(() => {
                    const redirect = this.$route.query.redirect || '';

                    if (redirect) {
                        this.$router.replace(redirect);
                    } else {
                        this.$router.replace({ name: 'index' });
                    }
                }).catch(({ errors }) => {
                    this.isLoading = false;
                    this.errors = errors;
                });
            },
        },
        components: { GeneralInput },
    }
</script>
<style lang="scss" scoped>
    .container {
        display: flex;
        align-items: center;
        justify-content: center;


        .box {
            width: $tablet;
            margin-top: 3rem;
        }
    }
</style>
