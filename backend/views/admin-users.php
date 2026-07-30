<?php /** @var array $users */ ?>
<div class="page-header">
    <h1><i class="fas fa-users"></i> 會員管理</h1>
    <a href="<?= BASE_URL ?>/admin/users/add" class="btn btn-primary"><i class="fas fa-plus"></i> 新增會員</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($users)): ?>
        <p style="text-align:center;padding:48px;color:#888;">尚無會員</p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:14px;vertical-align:middle;">
            <thead>
                <tr style="background:#f8f9fa;text-align:left;">
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">ID</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">帳號</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">Email</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;">註冊日期</th>
                    <th style="padding:14px 18px;border-bottom:2px solid #eee;text-align:center;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:12px 18px;"><?= $u['id'] ?></td>
                        <td style="padding:12px 18px;font-weight:600;"><?= htmlspecialchars($u['username']) ?></td>
                        <td style="padding:12px 18px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:12px 18px;color:#888;"><?= $u['created_at'] ?></td>
                        <td style="padding:12px 18px;text-align:center;">
                            <a href="<?= BASE_URL ?>/admin/users/edit/<?= $u['id'] ?>" style="color:#4CAF50;text-decoration:none;font-size:16px;margin-right:8px;" title="編輯"><i class="fas fa-edit"></i></a>
                            <a href="<?= BASE_URL ?>/admin/users/delete/<?= $u['id'] ?>" style="color:#f44336;text-decoration:none;font-size:16px;" title="刪除" onclick="return confirm('確定刪除此會員？')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
