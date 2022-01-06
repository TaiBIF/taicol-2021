<template>
    <div class="container">
        <div class="columns rows">
            <div class="column is-6">
                <div class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="`姓`"/>
                    </div>
                    <div class="column is-3" v-text="person.lastName"/>
                </div>
                <div class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="`名`"/>
                    </div>
                    <div class="column is-9" v-text="person.firstName"/>
                </div>
                <div class="columns" v-if="person.middleName">
                    <div class="column is-3">
                        <label class="label" v-text="`中間名`"/>
                    </div>
                    <div class="column is-9" v-text="person.middleName"/>
                </div>
                <div class="columns" v-if="person.abbreviationName">
                    <div class="column is-3">
                        <label class="label" v-text="`縮寫`"/>
                    </div>
                    <div class="column is-9" v-text="person.abbreviationName"/>
                </div>
                <div class="columns" v-if="person.originalFullName">
                    <div class="column is-3">
                        <label class="label" v-text="`原母語完整名`"/>
                    </div>
                    <div class="column is-9" v-text="person.originalFullName"/>
                </div>
                <div class="columns" v-if="person.otherNames">
                    <div class="column is-3">
                        <label class="label" v-text="`其他名`"/>
                    </div>
                    <div class="column is-9" v-text="person.otherNames"/>
                </div>
                <div class="columns" v-if="person.yearOfBirth || person.yearOfDeath">
                    <div class="column is-3">
                        <label class="label" v-text="`生卒年`"/>
                    </div>
                    <div class="column is-9">
                        {{ `${person.yearOfBirth || ''}-${person.yearOfDeath || ''}` }}
                    </div>
                </div>
                <div class="columns" v-if="person.yearOfPublication">
                    <div class="column is-3">
                        <label class="label" v-text="`活躍年代`"/>
                    </div>
                    <div class="column is-9" v-text="person.yearOfPublication"/>
                </div>
                <div class="columns" v-if="person.nationality">
                    <div class="column is-3">
                        <label class="label" v-text="`國籍`"/>
                    </div>
                    <div class="column is-9">
                        {{ person.nationality.display['zh-tw'] }}
                    </div>
                </div>
                <div class="columns" v-if="person.biologyDepartments.length">
                    <div class="column is-3">
                        <label class="label" v-text="`研究領域`"/>
                    </div>
                    <div class="column is-9">
                        <span v-for="department in person.biologyDepartments">
                            {{ $t(`forms.person.biologyDepartmentOptions.${department}`) }}
                        </span>
                    </div>
                </div>
                <div class="columns" v-if="person.biologicalGroup">
                    <div class="column is-3">
                        <label class="label" v-text="`研究類群`"/>
                    </div>
                    <div class="column is-9">
                        {{ person.biologicalGroup }}
                    </div>
                </div>
                <br/>
                <br/>
                <div class="columns">
                    <div class="column is-3">
                        <router-link class="button" v-if="authenticated"
                                     :to="`/persons/${person.id}/edit`">
                            編輯人名
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import { mapGetters } from 'vuex';

    export default {
        props: {
            person: {
                type: Object,
                required: true,
            },
        },
        computed: {
            ...mapGetters({
                authenticated: 'auth/authenticated',
            }),
        },
    }
</script>
<style lang="scss" scoped>
    .container {
        margin: 0 auto;
        height: 100%;

        .columns {
            margin: 0;
            &.rows {
                padding: 2.5rem 0;
                height: 100%;
            }
        }
    }
</style>
