/**
 * Vue Router Settings
 *
 */
import Vue from 'vue';
import VueRouter from 'vue-router';
import store from './store';

Vue.use(VueRouter);

// Lazy load pages
const Index = () => import(/* webpackChunkName: "index" */'./pages/IndexPage');
const Result = () => import(/* webpackChunkName: "results" */'./pages/ResultPage');
const ReferenceForm = () => import(/* webpackChunkName: "reference-form" */'./pages/ReferenceFormPage');
const Reference = () => import(/* webpackChunkName: "reference" */'./pages/ReferencePage');
const TaxonNameForm = () => import(/* webpackChunkName: "taxon-name-form" */'./pages/TaxonNameFormPage');
const TaxonNameList = () => import(/* webpackChunkName: "taxon-name-list" */'./pages/TaxonNameListPage')
const ReferenceList = () => import(/* webpackChunkName: "reference-list" */'./pages/ReferenceListPage');

const routes = [
    {
        name: 'login',
        path: '/login',
        component: () => import(/* webpackChunkName: "login" */'./pages/LoginPage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'register',
        path: '/register',
        component: () => import(/* webpackChunkName: "register" */'./pages/RegisterPage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'index',
        path: '/',
        component: Index,
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'referenceForm',
        path: '/reference',
        component: ReferenceForm,
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'reference-list',
        path: '/references',
        component: ReferenceList,
        meta: {
            allowAnonymous: true,
            searchbar: true,
        },
    },
    {
        name: 'reference',
        path: '/references/:id',
        component: Reference,
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'reference-edit',
        path: '/references/:id/edit',
        component: ReferenceForm,
    },
    {
        name: 'reference-usages',
        path: '/references/:id/usages',
        component: () => import(/* webpackChunkName: "reference-list" */'./pages/ReferenceUsagePage'),
        meta: {
            allowAnonymous: true,
        },
    },
    {
        name: 'taxon-name-list',
        path: '/taxon-names',
        component: TaxonNameList,
        meta: {
            backgroundColor: 'white',
            allowAnonymous: true,
            searchbar: true,
        },
    },
    {
        name: 'taxon-name',
        path: '/taxon-names/:id',
        component: () => import(/* webpackChunkName: "taxon name page" */ './pages/TaxonNamePage'),
        meta: {
            backgroundColor: 'grey',
            allowAnonymous: true,
        },
    },
    {
        name: 'taxon-name-create',
        path: '/taxon-name',
        component: TaxonNameForm,
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'taxon-name-edit',
        path: '/taxon-names/:id/edit',
        component: TaxonNameForm,
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'namespace-list',
        path: '/namespaces',
        component: () => import(/* webpackChunkName: "namespace list page" */ './pages/NamespaceListPage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'namespace',
        path: '/namespaces/:id',
        component: () => import(/* webpackChunkName: "namespace list page" */ './pages/NamespacePage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
    {
        name: 'namespace-usages',
        path: '/namespaces/:namespaceId/usages/:usageId',
        component: () => import(/* webpackChunkName: "namespace list page" */ './pages/UsagePage'),
        meta: {
            backgroundColor: 'grey',
        },
    },
];

const router = new VueRouter({
    mode: 'history',
    routes,
});

const originalPush = VueRouter.prototype.push
VueRouter.prototype.push = function push(location, onResolve, onReject) {
    if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject)
    return originalPush.call(this, location).catch(err => err)
}

router.beforeEach((to, from, next) => {
    store.commit('breadcrumb/CLEAR_ITEMS');
    if (!to.meta?.allowAnonymous && !store.getters['auth/authenticated']) {
        next({
            path: '/login',
            query: { redirect: to.fullPath },
        })
    } else {
        next();
    }
});


export default router;
