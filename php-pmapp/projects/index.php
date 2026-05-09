<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$user = getCurrentUser();

if (isAdmin()) {
    $projects = $conn->query("
        SELECT p.*,
               u.name AS creator_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS task_count,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
        FROM projects p
        JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC
    ");
} else {
    $uid  = $user['id'];
    $stmt = $conn->prepare("
        SELECT p.*,
               u.name AS creator_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND assigned_to = ?) AS task_count,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
        FROM projects p
        JOIN users u ON p.created_by = u.id
        JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param('ii', $uid, $uid);
    $stmt->execute();
    $projects = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/php-pmapp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Projects</div>
            <?php if (isAdmin()): ?>
            <a href="/php-pmapp/projects/create.php" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-plus-lg me-1"></i> New Project
            </a>
            <?php endif; ?>
        </div>

        <div class="page-body">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <?php if ($projects && $projects->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($proj = $projects->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="project-card">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div style="width:44px;height:44px;border-radius:12px;flex-shrink:0;
                                        background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                        display:flex;align-items:center;justify-content:center;
                                        color:#fff;font-size:1.2rem;">
                                <i class="bi bi-folder2"></i>
                            </div>
                            <?php if (isAdmin()): ?>
                            <a href="/php-pmapp/projects/view.php?id=<?= $proj['id'] ?>"
                               class="btn btn-sm btn-outline-primary">Open</a>
                            <?php endif; ?>
                        </div>
                        <div class="project-title"><?= htmlspecialchars($proj['title']) ?></div>
                        <div class="project-desc mb-3">
                            <?= htmlspecialchars($proj['description'] ?: 'No description provided.') ?>
                        </div>
                        <div class="d-flex gap-3 text-muted small border-top pt-3">
                            <span><i class="bi bi-list-task me-1"></i><?= (int)$proj['task_count'] ?> tasks</span>
                            <span><i class="bi bi-people me-1"></i><?= (int)$proj['member_count'] ?> members</span>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <a href="/php-pmapp/tasks/index.php?project_id=<?= $proj['id'] ?>"
                               class="btn btn-sm btn-primary flex-grow-1">
                                <i class="bi bi-check2-square me-1"></i> View Tasks
                            </a>
                            <?php if (isAdmin()): ?>
                            <a href="/php-pmapp/projects/members.php?id=<?= $proj['id'] ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-people"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="content-card">
                <div class="empty-state py-5">
                    <i class="bi bi-folder2 d-block mb-3"></i>
                    <h5 class="fw-bold mb-1">No Projects Yet</h5>
                    <p class="text-muted mb-4">
                        <?= isAdmin() ? 'Create your first project to get started.' : 'You haven\'t been assigned to any projects yet.' ?>
                    </p>
                    <?php if (isAdmin()): ?>
                    <a href="/php-pmapp/projects/create.php" class="btn btn-primary px-4">
                        <i class="bi bi-plus-lg me-1"></i> Create First Project
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
