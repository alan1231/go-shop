const oauthConfig = {
  google: {
    clientId: '828907985954-gfmpa7rua6isuucerl22tjcmgq74jsej.apps.googleusercontent.com',
    authUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
    scope: 'openid email profile',
  },
  line: {
    channelId: '2010960328',
    authUrl: 'https://access.line.me/oauth2/v2.1/authorize',
    scope: 'openid profile',
  },
}

export function oauthCallbackPath() {
  return window.location.origin + '/admin/auth/callback'
}

function genState(provider, intent) {
  const rand = crypto.getRandomValues(new Uint32Array(4)).join('')
  const s = provider + '-' + rand
  sessionStorage.setItem('admin_oauth_state', s)
  sessionStorage.setItem('admin_oauth_intent', intent || 'login')
  return s
}

export function buildOAuthUrl(provider, intent) {
  const cfg = oauthConfig[provider]
  const params = new URLSearchParams({
    response_type: 'code',
    client_id: cfg.clientId || cfg.channelId,
    redirect_uri: oauthCallbackPath(),
    scope: cfg.scope,
    state: genState(provider, intent),
  })
  return `${cfg.authUrl}?${params.toString()}`
}

class InvalidStateError extends Error {}

function consumeIntent() {
  const state = sessionStorage.getItem('admin_oauth_state')
  const intent = sessionStorage.getItem('admin_oauth_intent')
  sessionStorage.removeItem('admin_oauth_state')
  sessionStorage.removeItem('admin_oauth_intent')
  if (!state) throw new InvalidStateError()
  return { intent: intent || 'login' }
}

export function verifyOAuthState(state) {
  const expected = sessionStorage.getItem('admin_oauth_state')
  const intent = sessionStorage.getItem('admin_oauth_intent')
  sessionStorage.removeItem('admin_oauth_state')
  sessionStorage.removeItem('admin_oauth_intent')
  if (!expected || state !== expected) throw new InvalidStateError()
  return intent || 'login'
}