export const oauthConfig = {
  redirectUri: 'http://localhost:5173/auth/callback',
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

function genState(provider, redirect) {
  const rand = crypto.getRandomValues(new Uint32Array(4)).join('')
  const payload = redirect ? '|' + encodeURIComponent(redirect) : ''
  const s = provider + '-' + rand + payload
  sessionStorage.setItem('oauth_state', s)
  return s
}

export function buildOAuthUrl(provider, redirect) {
  const cfg = oauthConfig[provider]
  const params = new URLSearchParams({
    response_type: 'code',
    client_id: cfg.clientId || cfg.channelId,
    redirect_uri: oauthConfig.redirectUri,
    scope: cfg.scope,
    state: genState(provider, redirect),
  })
  return `${cfg.authUrl}?${params.toString()}`
}

export function verifyOAuthState(state) {
  const expected = sessionStorage.getItem('oauth_state')
  sessionStorage.removeItem('oauth_state')
  return !!expected && state === expected
}

export function extractOAuthRedirect(state) {
  if (!state) return ''
  const i = state.indexOf('|')
  if (i === -1) return ''
  return decodeURIComponent(state.slice(i + 1))
}
