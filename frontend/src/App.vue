<template>
  <div class="min-h-screen bg-surface-container-highest md:py-8 flex justify-center">
    <div class="w-full max-w-md bg-surface min-h-screen relative shadow-2xl overflow-hidden md:rounded-[32px] md:border-[8px] md:border-inverse-surface">
      <router-view :key="$route.fullPath" />
      <Toast />
    </div>
  </div>
</template>

<script setup>
import Toast from './components/Toast.vue'
</script>