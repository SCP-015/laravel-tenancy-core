<template>
    <svg width="22" height="16" viewBox="0 0 22 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M1 3C1 2.46957 1.21071 1.96086 1.58579 1.58579C1.96086 1.21071 2.46957 1 3 1H19C19.5304 1 20.0391 1.21071 20.4142 1.58579C20.7893 1.96086 21 2.46957 21 3V13C21 13.5304 20.7893 14.0391 20.4142 14.4142C20.0391 14.7893 19.5304 15 19 15H3C2.46957 15 1.96086 14.7893 1.58579 14.4142C1.21071 14.0391 1 13.5304 1 13V3Z" stroke="#336AFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M11 11C12.6569 11 14 9.65685 14 8C14 6.34315 12.6569 5 11 5C9.34315 5 8 6.34315 8 8C8 9.65685 9.34315 11 11 11Z" stroke="#336AFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M1 5C2.06087 5 3.07828 4.57857 3.82843 3.82843C4.57857 3.07828 5 2.06087 5 1M17 15C17 13.9391 17.4214 12.9217 18.1716 12.1716C18.9217 11.4214 19.9391 11 21 11" stroke="#336AFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>

</template>

<script setup lang="ts">
defineProps({
    fillColor: {
        type: String,
        default: "#336AFF",
    },
    width: {
        type: Number,
        default: 22,
    },
    height: {
        type: Number,
        default: 16,
    },
});
</script>
