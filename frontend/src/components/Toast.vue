<template>
  <div class="toast-container">
    <transition-group name="toast">
      <div v-for="t in toastStore.items" :key="t.id" class="toast flex items-center gap-sm px-md py-2.5 rounded-lg shadow-lg bg-inverse-surface text-inverse-on-surface font-label-lg text-label-lg" :class="t.type">
        <span class="material-symbols-outlined" style="font-size: 18px;">{{ t.type === 'success' ? 'check_circle' : 'error' }}</span>
        <span class="text-inverse-on-surface">{{ t.message }}</span>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { useToastStore } from '../store/toast.js'

const toastStore = useToastStore()
</script>

<style scoped>
.toast-container { position: fixed; bottom: calc(env(safe-area-inset-bottom, 20px) + 96px); left: 50%; transform: translateX(-50%); z-index: 9999; display: flex; flex-direction: column; align-items: center; gap: 8px; pointer-events: none; width: max-content; max-width: calc(100% - 32px); }
@media (min-width: 768px) {
  .toast-container { bottom: calc(env(safe-area-inset-bottom, 20px) + 128px); }
}
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }
</style>
