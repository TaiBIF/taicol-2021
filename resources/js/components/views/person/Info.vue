<template>
    <div class="container">
        <div class="columns rows">
            <div class="column is-6">
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
