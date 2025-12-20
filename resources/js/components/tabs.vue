<template>
    <div class="position-relative">
        <div role="tablist" class="tabs tabs-border position-relative">
            <template v-for="(tab, idx) in tabs" :key="idx">
                <a
                    role="tab"
                    class="tab"
                    :class="{ 'tab-active': currentTab == tab.id }"
                    @click="emit('update-tab', tab.id)"
                    >{{ tab.name }}</a
                >
            </template>

            <div class="liner"></div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    tabs: Array,
    currentTab: String,
});
const emit = defineEmits(["update-tab"]);
</script>

<style scoped>
.liner {
    position: sticky;
    width: 100%;
    height: 1.5px;
    background: #e5dede;
    margin-top: -2.5px;
    z-index: -1;
    margin-right: 6px;
    margin-left: 6px;
}
</style>
