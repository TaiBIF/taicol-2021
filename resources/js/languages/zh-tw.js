import header from './zh-tw/header';
import indexPage from './zh-tw/index';
import loginPage from './zh-tw/login';
import common from './zh-tw/common';
import taxonName from './zh-tw/taxonName';
import reference from './zh-tw/reference';
import person from './zh-tw/person';
import namespace from './zh-tw/namespace';
import typeSpecimen from './zh-tw/typeSpecimen';
import usage from './zh-tw/usage';
import user from './zh-tw/user';
import collect from './zh-tw/collect';
import validation from './zh-tw/validation';

export default {
    title: '物種學名管理工具',
    functions: {
        index: '首頁',
    },
    search: {
        options: '搜尋選項',
    },
    forms: {
        draftCreateNote: '草稿，請按 Enter 新增',
        copySuccess: '複製至剪貼簿',
    },
    common,
    header,
    validation,
    taxonName,
    reference,
    person,
    namespace,
    typeSpecimen,
    usage,
    collect,
    user,
    indexPage,
    loginPage,
};
