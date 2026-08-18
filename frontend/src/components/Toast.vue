<template>
  <div class="toast-container">
    <transition-group name="toast">
      <div v-for="t in toastStore.items" :key="t.id" class="toast" :class="t.type">
        <i :class="t.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
        {{ t.message }}
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { useToastStore } from '../store/toast.js'

const toastStore = useToastStore()
</script>

<style scoped>
.toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.toast { display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
.toast.success { background: #4CAF50; }
.toast.error { background: #f44336; }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-12px); }
</style>
