import Vue from 'vue';
import vSelect from 'vue-select';
import vuexI18n from 'vuex-i18n';
import { Pagination, Upload } from 'buefy';
import Toast from 'vue-toastification';
import VueCompositionAPI from '@vue/composition-api';
import toastOptions from './toastOptions';
import 'vue-toastification/dist/index.css';
import VueAxios from './vue-axios';

import initial from './initial';
import axios from './utils/maxios';

import store from './store';
import router from './router';

import Header from './components/Header.vue';
import breadcrumb from './components/Breadcrumb.vue';

import zhTw from './languages/zh-tw';
import enUs from './languages/en-us';
import c from './constants';
import { renderMarkdown } from './utils';
import layers from './components/layers.vue';
import modal from './components/Modal.vue';

Vue.use(VueCompositionAPI);
Vue.use(vuexI18n.plugin, store);
Vue.i18n.add('zh-tw', zhTw);
Vue.i18n.add('en-us', enUs);
Vue.i18n.set('zh-tw');
Vue.i18n.fallback('zh-tw');

Vue.use(Upload);
Vue.use(Toast, toastOptions);
Vue.use(Pagination);

Vue.use(VueAxios, axios);
Vue.prototype.$c = c;

export const utils = {
    methods: {
        renderMarkdown,
    },
};

Vue.mixin(utils);

Vue.component('v-select', vSelect);

initial()
    .then(() => {
        new Vue({
            el: '#app',
            components: {
                tHeader: Header, breadcrumb, layers, modal,
            },
            store,
            router,
            mounted() {
                console.info('Application loaded.');
            },
            provide() {
                return {
                    axios,
                };
            },
        });
    });
