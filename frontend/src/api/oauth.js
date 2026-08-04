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

export function buildOAuthUrl(provider) {
  const cfg = oauthConfig[provider]
  const params = new URLSearchParams({
    response_type: 'code',
    client_id: cfg.clientId,
    redirect_uri: oauthConfig.redirectUri,
    scope: cfg.scope,
    state: provider,
  })
  return `${cfg.authUrl}?${params.toString()}`
}
