<template>
    <div>
        <navbar v-if="!hideComponent" />

        <div class="my-5 default-container">
            <transition name="fade" mode="out-in">
                <router-view />
            </transition>
        </div>
    </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
