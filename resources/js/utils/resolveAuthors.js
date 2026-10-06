/**
 * 依使用者選擇整理最終作者清單（給父元件用）
 * 多筆候選但未選擇者回傳於 unresolved，不自動取第一筆
 */
export const resolveAuthors = (authors, authorsPossible, authorsCandidates, selected) => {
    const final = [];
    const unresolved = [];

    (authors || []).forEach((author, index) => {
        if (selected[index]) {
            final.push(selected[index]);
            return;
        }
        const list = Array.isArray(authorsCandidates?.[index])
            ? authorsCandidates[index]
            : (authorsPossible?.[index] ? [authorsPossible[index]] : []);

        if (list.length === 1) {
            final.push(list[0]);
        } else if (list.length > 1) {
            unresolved.push(`${author.family}, ${author.given}`);
        }
    });

    return { final, unresolved };
};

export default resolveAuthors;
