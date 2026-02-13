export default {
    // common
    'common.selfNotAllowed': '不能為自己',

    // reference
    'reference.min': '必填',
    'reference.not_in': '必填',
    'reference.required': '必填',
    'reference.required_if': '必填',
    'reference.integer': '須為數字',
    'reference.properties.volume.regex': '只允許「數字」、「英文」',
    'reference.properties.chapter.regex': '只允許「數字」、「英文」',
    'reference.properties.pages_range.regex': '只允許「數字」、「英文」、「–」、「,」',
    'reference.regex': '格式不符',
    'reference.image': '格式不符 (jpg, jpeg, png)',
    'reference.authors.exists': '作者不存在',
    'reference.properties.url': '網址格式不符',

    // taxonName
    'taxonName.min': '必填',
    'taxonName.integer': '須為數字',
    'taxonName.show_page.regex': '須為數字或英文字母',
    'taxonName.not_in': '必填',
    'taxonName.required': '必填',
    'taxonName.required_if': '必填',
    'taxonName.required_without': '必填',

    // person
    'person.required': '必填',
    'person.min': '最少 1100 年後',
    'person.digits': '只能為西元年',
    'person.integer': '只能為數字',

    resourceNotFound: '找不到資源',
    inputsInvalid: '欄位填寫錯誤',

    // usage
    // 'usage.wrongRank': '請選擇正確的種下上階層',
    'usage.wrongParent': '請選擇正確上階層',
    'usage.firstMustBeAccepted': '第一張卡片必須為有效學名',
    'usage.hasTaxonUsageExists': '本筆學名使用已被收錄，請以「編輯」代替「刪除再新增」，如本筆學名使用是錯誤匯入，請回報管理員處理。',
    'usage.hasReferenceUsageExists': '已有相同學名使用存在，無法匯入所有學名使用。若需要更新已建立學名使用內容，請使用「編輯異名表」更新內容。',
    'usage.min': '必填',
    'usage.not_in': '必填',
    'usage.required': '必填',
    'usage.required_if': '必填',
    'usage.required_without': '必填',
    'usage.integer': '須為數字',
    'usage.show_page.regex': '須為數字或英文字母',

    // user
    'user.required': '必填',
    'user.email': '格式不符',

    'referenceUsagePrefix': "文獻已建立分類群不得匯入，若需編輯請至文獻頁面的編輯異名表：",
    'referenceHasFile': "此篇文獻已建立並已有文獻PDF檔案，不得匯入，若需編輯請至文獻頁面的編輯異名表："
};
