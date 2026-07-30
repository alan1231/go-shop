<?php /** @var array|null $user */ /** @var string $message */ /** @var string $message_type */
$isEdit = isset($user);
$isMember = $isEdit && ($user['role'] ?? '') === 'user'; // 一般會員只顯示唯讀帳號/Email
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
        <?php if ($isMember): ?>
            <!-- 一般會員：帳號/Email 唯讀，僅能改密碼 -->
            <div class="form-group">
                <label>帳號</label>
                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>新密碼</label>
                <input type="password" name="password" required>
            </div>
        <?php else: ?>
            <!-- 新增會員 / 管理員編輯自己 -->
            <div class="form-group">
                <label>帳號</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($user['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= $isEdit ? '新密碼（留則不變）' : '密碼' ?></label>
                <input type="password" name="password" <?= $isEdit ? '' : 'required' ?>>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? '儲存變更' : '新增會員' ?></button>
        <a href="<?= BASE_URL ?>/admin/users" class="btn btn-default" style="margin-left:10px;">取消</a>
    </form>
</div>