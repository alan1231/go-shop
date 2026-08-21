package main

import (
	"fmt"
	"io"
	"net"
	"net/http"
	"strings"
	"sync"

	"golang.org/x/net/websocket"
)

// 儲存 WebSocket 客戶端連線
var (
	clients   = make(map[*websocket.Conn]bool)
	clientsMu sync.Mutex
)

func main() {
	// 1. 啟動 TCP Server (監聽 9100 接收 PHP 出餐單)
	go startTCPServer()

	// 2. 設定 HTTP 路由與 WebSocket 路由
	http.HandleFunc("/", handleHome)
	http.Handle("/ws", websocket.Handler(handleWebSocket))

	fmt.Println("==================================================")
	fmt.Println(">>> Mock Server (TCP 9100) 已啟動")
	fmt.Println(">>> Web Dashboard (HTTP 8080) 已啟動")
	fmt.Println(">>> 請開啟瀏覽器造訪: http://127.0.0.1:8080")
	fmt.Println("==================================================")

	// 啟動 Web 服務 (Port 8080)
	err := http.ListenAndServe(":8080", nil)
	if err != nil {
		fmt.Println("Web Server 啟動失敗:", err)
	}
}

// ---- TCP 接收邏輯 ----
func startTCPServer() {
	listener, err := net.Listen("tcp", "127.0.0.1:9100")
	if err != nil {
		fmt.Println("TCP 啟動失敗:", err)
		return
	}
	defer listener.Close()

	for {
		conn, err := listener.Accept()
		if err != nil {
			continue
		}
		go handleTCPConnection(conn)
	}
}

func handleTCPConnection(conn net.Conn) {
	defer conn.Close()

	buffer := make([]byte, 2048)
	n, err := conn.Read(buffer)
	if err != nil && err != io.EOF {
		return
	}

	rawBytes := buffer[:n]
	cleanText := parseEscPos(rawBytes)

	// 將解析好的內容廣播給網頁前端
	broadcastMessage(cleanText)
}

// 簡易 ESC/POS 解析器
func parseEscPos(data []byte) string {
	var builder strings.Builder
	for i := 0; i < len(data); i++ {
		b := data[i]
		if b == 0x1D && i+1 < len(data) && data[i+1] == 0x56 {
			builder.WriteString("\n--- [✂️ 切刀處] ---\n")
			i++
			continue
		}
		if b == 0x1B || b == 0x1D {
			i++
			continue
		}
		if b >= 0x20 || b == '\n' || b == '\r' || b > 0x7F {
			builder.WriteByte(b)
		}
	}
	return builder.String()
}

// ---- WebSocket & Web 廣播邏輯 ----
func handleWebSocket(ws *websocket.Conn) {
	clientsMu.Lock()
	clients[ws] = true
	clientsMu.Unlock()

	defer func() {
		clientsMu.Lock()
		delete(clients, ws)
		clientsMu.Unlock()
		ws.Close()
	}()

	// 保持連線
	buf := make([]byte, 1024)
	for {
		_, err := ws.Read(buf)
		if err != nil {
			break
		}
	}
}

func broadcastMessage(msg string) {
	clientsMu.Lock()
	defer clientsMu.Unlock()

	for ws := range clients {
		websocket.Message.Send(ws, msg)
	}
}

// ---- 前端網頁 HTML/CSS 畫面 ----
func handleHome(w http.ResponseWriter, r *http.Request) {
	html := `<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>出餐單即時預覽看板</title>
    <style>
        body { font-family: 'Courier New', monospace; background-color: #222; color: #333; margin: 20px; display: flex; flex-direction: column; align-items: center; }
        h1 { color: #fff; font-family: sans-serif; }
        #receipt-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; max-width: 1200px; }
        .receipt {
            width: 280px; background: #fff; padding: 15px; border-radius: 2px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-bottom: 5px dashed #ccc;
            white-space: pre-wrap; font-size: 14px; line-height: 1.4; word-break: break-all;
            animation: popIn 0.3s ease-out;
        }
        .cut-line { color: #e74c3c; font-weight: bold; text-align: center; border-top: 1px dashed #e74c3c; margin: 10px 0; padding-top: 5px; }
        @keyframes popIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <h1>🧾 出餐單即時預覽看板 (Mock Thermal Printer)</h1>
    <div id="receipt-container"></div>

    <script>
        const ws = new WebSocket('ws://' + window.location.host + '/ws');
        const container = document.getElementById('receipt-container');

        ws.onmessage = function(event) {
            const receipt = document.createElement('div');
            receipt.className = 'receipt';
            
            // 替換切刀顯示樣式
            let formattedText = event.data.replace(/--- \[✂️ 切刀處\] ---/g, '<div class="cut-line">✂️ --- 已切刀 ---</div>');
            receipt.innerHTML = formattedText;
            
            // 將新單據放在最前面
            container.insertBefore(receipt, container.firstChild);
        };
    </script>
</body>
</html>`
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Write([]byte(html))
}