package service

import (
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"net/url"
	"strings"
)

type OAuthService struct {
	GoogleClientID     string
	GoogleClientSecret string
	LineChannelID      string
	LineChannelSecret  string
	OAuthRedirectURI   string
	HTTP               *http.Client
}

func NewOAuthService(cfg Config) *OAuthService {
	return &OAuthService{
		GoogleClientID:     cfg.GoogleClientID,
		GoogleClientSecret: cfg.GoogleClientSecret,
		LineChannelID:      cfg.LineChannelID,
		LineChannelSecret:  cfg.LineChannelSecret,
		OAuthRedirectURI:   cfg.OAuthRedirectURI,
		HTTP:               &http.Client{Timeout: 15},
	}
}

type Config struct {
	GoogleClientID     string
	GoogleClientSecret string
	LineChannelID      string
	LineChannelSecret  string
	OAuthRedirectURI   string
}

type OAuthUserInfo struct {
	ProviderID string
	Email      string
	Name       string
	Avatar     string
}

func (s *OAuthService) GetUserInfo(provider, code string) (*OAuthUserInfo, error) {
	switch strings.ToLower(provider) {
	case "google":
		return s.google(code)
	case "line":
		return s.line(code)
	}
	return nil, errors.New("invalid provider")
}

func (s *OAuthService) google(code string) (*OAuthUserInfo, error) {
	token := s.httpPostForm("https://oauth2.googleapis.com/token", url.Values{
		"code":          {code},
		"client_id":     {s.GoogleClientID},
		"client_secret": {s.GoogleClientSecret},
		"redirect_uri":  {s.OAuthRedirectURI},
		"grant_type":    {"authorization_code"},
	})
	if token["access_token"] == "" {
		return nil, errors.New("oauth token failed")
	}
	info := s.httpGetJSON("https://www.googleapis.com/oauth2/v2/userinfo", token["access_token"].(string))
	id, _ := info["id"].(string)
	if id == "" {
		return nil, errors.New("oauth userinfo failed")
	}
	email, _ := info["email"].(string)
	name, _ := info["name"].(string)
	picture, _ := info["picture"].(string)
	return &OAuthUserInfo{ProviderID: id, Email: email, Name: name, Avatar: picture}, nil
}

func (s *OAuthService) line(code string) (*OAuthUserInfo, error) {
	token := s.httpPostForm("https://api.line.me/oauth2/v2.1/token", url.Values{
		"code":          {code},
		"client_id":     {s.LineChannelID},
		"client_secret": {s.LineChannelSecret},
		"redirect_uri":  {s.OAuthRedirectURI},
		"grant_type":    {"authorization_code"},
	})
	if token["access_token"] == "" {
		return nil, errors.New("oauth token failed")
	}
	info := s.httpGetJSON("https://api.line.me/v2/profile", token["access_token"].(string))
	uid, _ := info["userId"].(string)
	if uid == "" {
		return nil, errors.New("oauth userinfo failed")
	}
	name, _ := info["displayName"].(string)
	avatar, _ := info["pictureUrl"].(string)
	return &OAuthUserInfo{ProviderID: uid, Name: name, Avatar: avatar}, nil
}

func (s *OAuthService) httpPostForm(url string, values url.Values) map[string]any {
	resp, err := s.HTTP.PostForm(url, values)
	if err != nil {
		return nil
	}
	defer resp.Body.Close()
	var m map[string]any
	_ = json.NewDecoder(resp.Body).Decode(&m)
	return m
}

func (s *OAuthService) httpGetJSON(url, token string) map[string]any {
	req, _ := http.NewRequest("GET", url, nil)
	req.Header.Set("Authorization", "Bearer "+token)
	resp, err := s.HTTP.Do(req)
	if err != nil {
		return nil
	}
	defer resp.Body.Close()
	var m map[string]any
	_ = json.NewDecoder(resp.Body).Decode(&m)
	return m
}

// FetchAvatar 下載三方頭像，回傳圖片資料
func (s *OAuthService) FetchAvatar(avatarURL string) ([]byte, string, error) {
	resp, err := s.HTTP.Get(avatarURL)
	if err != nil {
		return nil, "", err
	}
	defer resp.Body.Close()
	if resp.StatusCode != 200 {
		return nil, "", errors.New("avatar download failed")
	}
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, "", err
	}
	contentType := resp.Header.Get("Content-Type")
	ext := "jpg"
	switch {
	case strings.Contains(contentType, "png"):
		ext = "png"
	case strings.Contains(contentType, "gif"):
		ext = "gif"
	case strings.Contains(contentType, "webp"):
		ext = "webp"
	}
	return body, ext, nil
}
