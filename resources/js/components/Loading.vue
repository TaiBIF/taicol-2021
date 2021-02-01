<template>
    <div>
        <div class="loading"></div>
    </div>
</template>
<style lang="scss" scoped>
    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    .loading {
        width: 3rem;
        height: 3rem;
        margin: 1rem auto 0;
        border: solid .5rem $light-grey;
        border-radius: 50%;
        border-right-color: transparent;
        border-bottom-color: transparent;

        transition: all 0.5s ease-in;
        animation-name: rotate;
        animation-duration: 1.0s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
    }
</style>
