<template>
    <span :class="{
        'is-success': status === 'accepted',
        'is-danger': status === 'not-accepted'
    }" class="tag">
        {{ finalStatus }}
    </span>
</template>
<script>
    export default {
        props: {
            status: {
                type: String,
                required: true,
            },
            indications: {
                type: Array,
                required: true,
            },
        },
        computed: {
            finalStatus() {
                if (this.status === 'accepted') {
                    if (this.indications.includes('nom. prot.')) {
                        return '受保護名';
                    } else if (this.indications.includes('nom. cons.')) {
                        return '保留名';
                    } else {
                        return '有效學名';
                    }
                } else if (this.status === 'not-accepted') {
                    if (this.indications.includes('nom. obl.')) {
                        return '被遺忘名';
                    } else if (this.indications.includes('unavailable')) {
                        return '不適用';
                    } else if (this.indications.includes('nom. rej.')) {
                        return '廢棄名';
                    } else if (this.indications.includes('nom. illeg.')) {
                        return '不合法名';
                    } else if (this.indications.includes('nom. inval.')) {
                        return '不正當名';
                    } else {
                        return '異名';
                    }
                } else {
                    return '-';
                }
            },
        },
    }
</script>
