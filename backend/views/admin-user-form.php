<?php /** @var array|null $user */ /** @var string $message */ /** @var string $message_type */
$isEdit = isset($user);
?>
<div class="page-header">
    <h1><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? '編輯會員' : '新增會員' ?></h1>
</div>

<div class="card" style="max-width:480px;">
    <?php if (!empty($message)): ?>
        <?php $msg_type = $message_type ?? ''; ?>
        <div class="msg msg-<?= $msg_type ?: 'error' ?>"><i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <?php if ($isEdit): ?>
            <!-- 編輯會員：帳號/Email 唯讀，僅能改密碼 -->
            <div class="form-group">
                <label>帳號</label>
                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <?php if (!empty($user['provider'])): ?>
                <div class="msg msg-success" style="margin-bottom:16px;">
                    <i class="fab fa-<?= $user['provider'] === 'line' ? 'line' : 'google' ?>" style="color:<?= $user['provider'] === 'line' ? '#06C755' : '#4285F4' ?>;"></i>
                    此會員透過 <?= $user['provider'] === 'line' ? 'LINE' : 'Google' ?> 登入，無密碼，無法修改。
                </div>
            <?php else: ?>
            <div class="form-group">
                <label>新密碼</label>
                <input type="password" name="password" required>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- 新增會員 -->
            <div class="form-group">
                <label>帳號</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($user['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" required>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? '儲存變更' : '新增會員' ?></button>
        <a href="<?= BASE_URL ?>/admin/users" class="btn btn-default" style="margin-left:10px;">取消</a>
    </form>
</div>
