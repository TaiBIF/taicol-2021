import Vue from 'vue';
import vSelect from 'vue-select';
import vuexI18n from 'vuex-i18n';
import VueAxios from './vue-axios';

import initial from './initial';
import axios from './utils/maxios';

import { Tooltip, Upload, Toast, Pagination } from 'buefy';

import store from './store';
import router from './router';

import Header from './components/Header';
import breadcrumb from './components/Breadcrumb';

import zhTw from './languages/zh-tw';
import c from './constants';

Vue.use(vuexI18n.plugin, store);
Vue.i18n.add('zh-tw', zhTw);
Vue.i18n.set('zh-tw');

Vue.use(Tooltip);
Vue.use(Upload);
Vue.use(Toast);
Vue.use(Pagination);

Vue.use(VueAxios, axios);
Vue.prototype.$c = c;

import { renderMarkdown } from './utils/index';
export const utils = {
    methods: {
        renderMarkdown,
    }
}
Vue.mixin(utils);

Vue.component('v-select', vSelect);

import layers from './components/layers';
import modal from './components/Modal';
import { renderFormattedMarkdown } from './utils';

initial()
    .then(() => {
        new Vue({
            el: '#app',
            components: {
                tHeader: Header,
                breadcrumb,
                layers,
                modal,
            },
            store,
            router,
            mounted() {
                console.info('Application loaded.');
            },
            methods: {
                openToast(message, type = 'is-success') {
                    this.$buefy.toast.open({
                        duration: 3000,
                        message,
                        position: 'is-bottom',
                        type,
                        queue: false,
                    });
                },
            },
        });
    });

