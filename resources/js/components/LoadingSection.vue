<template>
    <div>
        <div class="loading"></div>
        <br/>
        <p class="has-text-centered has-text-grey-lighter">載入中</p>
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
        width: 100px;
        height: 100px;
        margin: 110px auto 0;
        border: solid 10px $light-grey;
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
