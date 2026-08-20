<template>
  <div v-if="text" class="overflow-hidden h-8 bg-primary-container text-on-primary-container flex items-center">
    <div class="marquee-track inline-block whitespace-nowrap font-label-md text-label-md">
      <span class="px-4 inline-block">{{ text }}</span>
      <span class="px-4 inline-block">{{ text }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useSiteStore } from '../store/site.js'

const site = useSiteStore()
const text = computed(() => site.marqueeText)

onMounted(() => site.init())
onUnmounted(() => site.dispose())
</script>

<style scoped>
.marquee-track {
  animation: marquee-scroll 18s linear infinite;
  will-change: transform;
}
@keyframes marquee-scroll {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}
</style>