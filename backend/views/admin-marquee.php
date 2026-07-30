<?php /** @var string $content */ ?>
<div class="page-header">
    <h1><i class="fas fa-scroll"></i> 跑馬燈管理</h1>
</div>

<div class="card" style="max-width:640px;">
    <?php if (!empty($message)): ?>
        <?php $msg_type = $message_type ?? ''; ?>
        <div class="msg msg-<?= $msg_type ?: 'error' ?>"><i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
    <?php endif; ?>

    <p style="color:#888;margin-bottom:16px;font-size:14px;">此內容會顯示在前台網站上方的跑馬燈區域。</p>

    <form action="" method="POST">
        <div class="form-group">
            <label>跑馬燈內容</label>
            <textarea name="content" rows="3" placeholder="請輸入跑馬燈文字" style="width:100%;max-width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;outline:none;"><?= htmlspecialchars($content) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 儲存</button>
        <a href="<?= BASE_URL ?>/admin" class="btn btn-default" style="margin-left:10px;">取消</a>
    </form>
</div>