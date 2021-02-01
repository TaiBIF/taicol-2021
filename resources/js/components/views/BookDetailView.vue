<template>
    <div>
        <div class="columns" v-if="issn">
            <div class="column is-3">
                <label class="label" v-text="$t('forms.reference.issn')"/>
            </div>
            <div class="column is-9" v-text="issn"></div>
        </div>
        <div class="columns" v-if="issnElectronic">
            <div class="column is-3">
                <label class="label" v-text="$t('forms.reference.issnElectronic')"/>
            </div>
            <div class="column is-9" v-text="issnElectronic"></div>
        </div>
        <div class="columns" v-if="publisher">
            <div class="column is-3">
                <label class="label" v-text="$t('forms.reference.publisher')"/>
            </div>
            <div class="column is-9" v-text="publisher"></div>
        </div>
        <div class="columns" v-if="country">
            <div class="column is-3">
                <label class="label" v-text="$t('forms.reference.publisherCountry')"/>
            </div>
            <div class="column is-9" v-text="country ? country.display['zh-tw'] : ''"></div>
        </div>
        <div class="columns" v-if="city">
            <div class="column is-3">
                <label class="label" v-text="$t('forms.reference.publisherCity')"/>
            </div>
            <div class="column is-9" v-text="city"></div>
        </div>
        <div class="columns" v-if="editors">
            <div class="column is-3">
                <label class="label" v-text="$t('forms.reference.editors')"/>
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
    }
</script>
