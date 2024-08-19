<template>
    <div class="container">
        <div class="columns rows">
            <div class="column is-5">
                <div class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.lastName')"/>
                    </div>
                    <div class="column is-3" v-text="person.lastName"/>
                </div>
                <div class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.firstName')"/>
                    </div>
                    <div class="column is-9" v-text="person.firstName"/>
                </div>
                <div v-if="person.middleName" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.middleName')"/>
                    </div>
                    <div class="column is-9" v-text="person.middleName"/>
                </div>
                <div v-if="person.abbreviationName" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.abbreviationName')"/>
                    </div>
                    <div class="column is-9" v-text="person.abbreviationName"/>
                </div>
                <div v-if="person.originalFullName" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.originalFullName')"/>
                    </div>
                    <div class="column is-9" v-text="person.originalFullName"/>
                </div>
                <div v-if="person.otherNames" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.otherNames')"/>
                    </div>
                    <div class="column is-9" v-text="person.otherNames"/>
                </div>
                <div v-if="person.yearOfBirth || person.yearOfDeath" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.yearOfBirthAndDeath')"/>
                    </div>
                    <div class="column is-9">
                        {{ `${person.yearOfBirth || ''}-${person.yearOfDeath || ''}` }}
                    </div>
                </div>
                <div v-if="person.yearOfPublication" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.yearOfPublication')"/>
                    </div>
                    <div class="column is-9" v-text="person.yearOfPublication"/>
                </div>
                <div v-if="person.nationality" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.countryNumericCode')"/>
                    </div>
                    <div class="column is-9">
                        {{ person.nationality.display['zh-tw'] }}
                    </div>
                </div>
                <div v-if="person.biologyDepartments.length" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.biologyDepartment')"/>
                    </div>
                    <div class="column is-9">
                        <span v-for="department in person.biologyDepartments">
                            {{ $t(`person.biologyDepartmentOptions.${department}`) }}
                        </span>
                    </div>
                </div>
                <div v-if="person.biologicalGroup" class="columns">
                    <div class="column is-3">
                        <label class="label" v-text="$t('person.biologicalGroup')"/>
                    </div>
                    <div class="column is-9">
                        {{ person.biologicalGroup }}
                    </div>
                </div>
                <br/>
                <br/>
                <div class="columns">
                    <div class="column is-3">
                        <router-link v-if="authenticated" :to="{name: 'person-edit', params: { id: person.id}}"
                                     class="button">
                            {{ $t('person.edit') }}
                        </router-link>
                    </div>
                </div>
                <div class="columns">
                    <!-- 人名編輯紀錄  -->
                    <div class="column">
                        <p class="text-[15px] mb-2 is-5 is-inline-block has-text-grey"
                            v-on:click="toggleEditLog()">
                            {{ $t('common.editHistory') }} <a><i class="fas" :class="{'fa-chevron-down': editLogHidden, 'fa-chevron-up': !editLogHidden}"></i></a>
                        </p>
                        <div :class="{ hidden: editLogHidden }">
                            <table class="table text-[14px] is-fullwidth max-w-full  has-text-grey">
                                <thead class="font-bold">
                                <tr>
                                    <th class="w-[80px]  has-text-grey" v-text="$t('common.editDate')"/>
                                    <th class="w-[80px]  has-text-grey" v-text="$t('common.editAction')"/>
                                    <th class="w-[270px]  has-text-grey" v-text="$t('common.editItem')"/>
                                    <th class="w-[90px]  has-text-grey" v-text="$t('common.editBy')"/>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="editLog in editLogs">
                                    <td>{{ editLog.createdAt }}</td>
                                    <td>{{ editLog.action }}</td>
                                    <td>{{ editLog.item }}</td>
                                    <td>{{ editLog.by }}</td>
                                </tr>
                                </tbody>
                            </table>
                            <button v-if=" logMore === true " v-on:click="fetchEditLog('person', logOffset)"  class="button is-small"> more +</button>
                        </div>
                    </div>                    
                </div>
            </div>

        </div>
    </div>
</template>
<script>
import { mapGetters } from 'vuex';

export default {
    data() {
        return {
            editLogs: [],
            logMore: false,
            logOffset: 0,
            editLogHidden: true,
        }
    },
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
    mounted() {
        this.fetchEditLog('person', 0);
    },
    methods: {
        toggleEditLog(){
            this[`editLogHidden`] = !this[`editLogHidden`];
        },
        fetchEditLog(log_type, offset) {

            this.axios.get(`/edit-logs?log_type=${log_type}&log_id=${this.$route.params.id}&offset=${offset}`)
                .then(({ data: { editLogs, logMore, logOffset }  }) => {
                    console.log(editLogs, logMore, logOffset)
                    this[`editLogs`].push(...editLogs);
                    this[`logMore`] = logMore;
                    this[`logOffset`] = logOffset;
                });
        },
    }
};
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
