<template>
  <section class="auth-shell" :class="`auth-shell--${variant}`">
    <div class="auth-glow auth-glow-one"></div>
    <div class="auth-glow auth-glow-two"></div>
    <div class="auth-stage">
      <div class="auth-card">
        <div class="brand-mark">
          <span class="material-symbols-outlined">storefront</span>
        </div>
        <h1>{{ title }}</h1>
        <p class="subtitle">{{ subtitle }}</p>
        <slot />
      </div>
    </div>
    <footer v-if="footer" class="auth-footer">
      <strong>SHOP</strong>
      <span>© 2026 SHOP 3C RETAILERS</span>
    </footer>
  </section>
</template>

<script>
export default {
  props: {
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
    variant: { type: String, default: 'login' },
    footer: Boolean,
  },
}
</script>

<style>
.auth-shell { position: relative; display: grid; grid-template-rows: 1fr auto; min-height: 100vh; margin: -30px -40px; background: var(--shop-background); overflow: hidden; }
.auth-stage { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; padding: 56px 16px; }
.auth-glow { position: absolute; border-radius: 50%; background: var(--shop-primary); filter: blur(120px); pointer-events: none; }
.auth-glow-one { top: 15%; left: 12%; width: 420px; height: 420px; opacity: .08; }
.auth-glow-two { right: 10%; bottom: 18%; width: 340px; height: 340px; opacity: .05; }
.auth-card { width: min(100%, 440px); padding: 38px; border: 1px solid var(--shop-border); border-radius: 16px; background: color-mix(in srgb, var(--shop-surface-lowest) 68%, transparent); box-shadow: 0 24px 70px rgba(0, 0, 0, .4); text-align: center; backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); }
.auth-card .brand-mark { display: grid; width: 72px; height: 72px; margin: 0 auto 16px; place-items: center; border: 1px solid color-mix(in srgb, var(--shop-primary) 28%, transparent); border-radius: 50%; background: color-mix(in srgb, var(--shop-primary) 12%, var(--shop-surface-lowest)); box-shadow: 0 0 28px color-mix(in srgb, var(--shop-primary) 15%, transparent); color: var(--shop-primary); }
.auth-card .brand-mark .material-symbols-outlined { font-size: 34px; }
.auth-card > h1 { margin: 0; color: var(--shop-text); font-family: 'Sora', sans-serif; font-size: 26px; letter-spacing: -.03em; }
.auth-card > .subtitle { margin: 8px 0 24px; color: var(--shop-text-muted); font-size: 14px; }
.auth-card .form-group { margin-bottom: 16px; text-align: left; }
.auth-card .form-group label { display: block; margin-bottom: 7px; font-weight: 700; }
.auth-card .password-field { position: relative; }
.auth-card .password-field input { padding-right: 48px; }
.auth-card .password-field button { position: absolute; top: 50%; right: 8px; display: grid; width: 36px; height: 36px; place-items: center; border: 0; background: transparent; color: var(--shop-text-muted); cursor: pointer; transform: translateY(-50%); }
.auth-card .password-field button:hover { color: var(--shop-primary); }
.auth-card .password-field button .material-symbols-outlined { font-size: 21px; }
.auth-shell--register .auth-card { width: min(100%, 460px); background: color-mix(in srgb, var(--shop-background) 65%, transparent); box-shadow: 0 24px 70px rgba(0, 0, 0, .42); text-align: left; }
.auth-shell--register .brand-mark { width: 64px; height: 64px; border-radius: 13px; background: var(--shop-surface); }
.auth-shell--register .brand-mark .material-symbols-outlined { font-size: 32px; }
.auth-shell--register .auth-card > h1 { color: var(--shop-primary); font-size: clamp(28px, 5vw, 34px); letter-spacing: -.04em; text-align: center; }
.auth-shell--register .auth-card > .subtitle { margin-bottom: 26px; text-align: center; }
.auth-shell--register .form-group label { color: var(--shop-text-muted); font-size: 12px; }
.auth-footer { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 18px; padding: 22px 16px; border-top: 1px solid var(--shop-border); background: var(--shop-surface-lowest); color: var(--shop-text-muted); font-size: 10px; letter-spacing: .08em; }
.auth-footer strong { color: var(--shop-text); font-family: 'Sora', sans-serif; font-size: 15px; }
@media (max-width: 768px) {
  .auth-shell { margin: -20px -16px; }
  .auth-stage { padding: 34px 16px; }
  .auth-card { padding: 28px 20px; }
  .auth-glow-one { left: -220px; }
  .auth-glow-two { right: -190px; }
}
@media (max-width: 420px) {
  .auth-footer { flex-direction: column; gap: 6px; }
}
</style>
