package storage

import (
	"crypto/rand"
	"encoding/hex"
	"errors"
	"fmt"
	"io"
	"mime/multipart"
	"os"
	"path/filepath"
	"strings"
	"time"
)

var ErrInvalidType = errors.New("不支援的圖片格式")

var allowedExt = map[string]bool{
	"jpg": true, "jpeg": true, "png": true, "gif": true, "webp": true,
}

type Images struct {
	Dir string
}

func (s *Images) Save(src io.Reader, filename string) (string, error) {
	ext := strings.ToLower(strings.TrimPrefix(filepath.Ext(filename), "."))
	if !allowedExt[ext] {
		return "", ErrInvalidType
	}
	if err := os.MkdirAll(s.Dir, 0o755); err != nil {
		return "", err
	}
	name := fmt.Sprintf("img_%d_%s.%s", time.Now().UnixNano(), randHex(6), ext)
	dest := filepath.Join(s.Dir, name)
	dst, err := os.Create(dest)
	if err != nil {
		return "", err
	}
	defer dst.Close()
	if _, err := io.Copy(dst, src); err != nil {
		os.Remove(dest)
		return "", err
	}
	return name, nil
}

func (s *Images) Delete(filename string) {
	if filename == "" {
		return
	}
	_ = os.Remove(filepath.Join(s.Dir, filename))
}

func randHex(n int) string {
	b := make([]byte, n)
	if _, err := rand.Read(b); err != nil {
		panic(err)
	}
	return hex.EncodeToString(b)
}

// ParseMultipartImage 從 multipart form 讀取 image 欄位（可選）
func ParseMultipartImage(r *multipart.Reader, field string) (io.Reader, string, error) {
	for {
		part, err := r.NextPart()
		if err == io.EOF {
			return nil, "", nil
		}
		if err != nil {
			return nil, "", err
		}
		if part.FormName() == field {
			return part, part.FileName(), nil
		}
		io.Copy(io.Discard, part)
	}
}
