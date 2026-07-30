<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - 管理後台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a1d29 0%, #2d3348 100%); }
        .login-box { background: #fff; border-radius: 12px; padding: 40px; width: 380px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .login-box .logo { text-align: center; margin-bottom: 32px; }
        .login-box .logo i { font-size: 36px; color: #4CAF50; }
        .login-box .logo h1 { font-size: 20px; font-weight: 700; color: #1a1d29; margin-top: 8px; }
        .login-box .logo p { font-size: 13px; color: #888; margin-top: 4px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 13px; color: #444; margin-bottom: 6px; }
        .form-group .input-wrap { display: flex; align-items: center; border: 1px solid #d0d5dd; border-radius: 8px; transition: border-color 0.2s; background: #f9fafb; }
        .form-group .input-wrap:focus-within { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.12); background: #fff; }
        .form-group .input-wrap i { padding: 0 12px; color: #999; font-size: 15px; }
        .form-group input { width: 100%; padding: 12px 12px 12px 0; border: none; background: transparent; font-size: 14px; outline: none; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; background: #4CAF50; color: #fff; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #43a047; }
        .error { background: #ffebee; color: #c62828; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 18px; display: flex; align-items: center; gap: 6px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <i class="fas fa-store"></i>
            <h1>管理後台</h1>
            <p>請登入您的帳號</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= BASE_URL ?>/login">
            <div class="form-group">
                <label>帳號</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" required placeholder="使用者名稱或 Email">
                </div>
            </div>
            <div class="form-group">
                <label>密碼</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> 登入</button>
        </form>
    </div>
</body>
</html>
