module.exports = {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        'on-surface-variant': 'var(--shop-text-muted)',
        'on-background': 'var(--shop-text)',
        primary: 'var(--shop-primary)',
        'on-primary': 'var(--shop-on-primary)',
        background: 'var(--shop-background)',
        'on-surface': 'var(--shop-text)',
      },
      spacing: {
        'margin-mobile': '16px',
        'container-max': '1280px',
      },
      fontFamily: {
        'body-md': ['Hanken Grotesk'],
        'label-sm': ['JetBrains Mono'],
      },
      fontSize: {
        'body-md': ['16px', { lineHeight: '1.6', fontWeight: '400' }],
        'label-sm': ['12px', { lineHeight: '1', letterSpacing: '.05em', fontWeight: '500' }],
      },
    },
  },
}
