<template>
  <div class="min-h-screen bg-surface-container-highest flex justify-center">
    <div class="w-full max-w-md bg-surface min-h-screen md:h-screen md:min-h-0 relative shadow-2xl overflow-hidden md:overflow-y-auto md:rounded-[16px] md:border-[8px] md:border-white">
      <router-view :key="$route.fullPath" />
      <Toast />
    </div>
  </div>
</template>

<script setup>
import Toast from './components/Toast.vue'
</script>