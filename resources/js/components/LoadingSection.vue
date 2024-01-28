<template>
    <div class="flex items-center justify-center h-full">
        <div class="flex flex-col gap-8">
            <div class="w-[60px] h-[60px]">
                <div class="lds-dual-ring"></div>
            </div>
            <p class="text-center text-gray-300 text-3xl">{{ $t('common.loading') }}</p>
        </div>
    </div>
</template>
<style lang="scss" scoped>

.lds-dual-ring {
    display: inline-block;
    width: 60px;
    height: 60px;
    transform: scale(0.8);
    transform-origin: center center;
}

.lds-dual-ring:after {
    content: ' ';
    display: block;
    width: 64px;
    height: 64px;
    margin: 8px;
    border-radius: 50%;
    border: 6px solid #e5e7eb;
    border-color: #e5e7eb transparent #e5e7eb transparent;
    animation: lds-dual-ring 1.2s linear infinite;
}

@keyframes lds-dual-ring {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>
