<template>
    <div>
        <div v-if="issn" class="columns">
            <div class="column is-3">
                <label class="label" v-text="$t('reference.issn')"/>
            </div>
            <div class="column is-9" v-text="issn"></div>
        </div>
        <div v-if="issnElectronic" class="columns">
            <div class="column is-3">
                <label class="label" v-text="$t('reference.issnElectronic')"/>
            </div>
            <div class="column is-9" v-text="issnElectronic"></div>
        </div>
        <div v-if="publisher" class="columns">
            <div class="column is-3">
                <label class="label" v-text="$t('reference.publisher')"/>
            </div>
            <div class="column is-9" v-text="publisher"></div>
        </div>
        <div v-if="country" class="columns">
            <div class="column is-3">
                <label class="label" v-text="$t('reference.publisherCountry')"/>
            </div>
            <div class="column is-9" v-text="country ? country.display['zh-tw'] : ''"></div>
        </div>
        <div v-if="city" class="columns">
            <div class="column is-3">
                <label class="label" v-text="$t('reference.publisherCity')"/>
            </div>
            <div class="column is-9" v-text="city"></div>
        </div>
        <div v-if="editors" class="columns">
            <div class="column is-3">
                <label class="label" v-text="$t('reference.editors')"/>
            </div>
            <div class="column is-9">
                <p v-for="person in editors">{{ person.fullName }}</p>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        country: {
            type: Object,
        },
        city: {
            type: String,
        },
        publisher: {
            type: String,
        },
        editors: {
            type: Array,
        },
        issn: {
            type: String,
        },
        issnElectronic: {
            type: String,
        },
    },
};
</script>
