<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$user = getCurrentUser();

if (isAdmin()) {
    // Admin sees global stats
    $stats = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM projects)                        AS total_projects,
            (SELECT COUNT(*) FROM tasks)                           AS total_tasks,
            (SELECT COUNT(*) FROM tasks WHERE status='completed')  AS completed_tasks,
            (SELECT COUNT(*) FROM tasks WHERE status='pending')    AS pending_tasks
    ")->fetch_assoc();

    // Recent tasks
    $recentTasks = $conn->query("
        SELECT t.*, p.title AS project_title, u.name AS assignee_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.assigned_to = u.id
        ORDER BY t.created_at DESC
        LIMIT 8
    ");

    // Recent projects
    $recentProjects = $conn->query("
        SELECT p.*, u.name AS creator_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS task_count
        FROM projects p
        JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
} else {
    // Member sees own stats
    $uid = $user['id'];
    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = ?)                          AS total_tasks,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status='completed')   AS completed_tasks,
            (SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status='pending')     AS pending_tasks,
            (SELECT COUNT(DISTINCT project_id) FROM project_members WHERE user_id = ?)  AS total_projects
    ");
    $stmt->bind_param('iiii', $uid, $uid, $uid, $uid);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT t.*, p.title AS project_title
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE t.assigned_to = ?
        ORDER BY t.created_at DESC
        LIMIT 8
    ");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $recentTasks = $stmt->get_result();
    $stmt->close();

    $recentProjects = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/php-pmapp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">Welcome back, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
                <?php if (isAdmin()): ?>
                    <a href="/php-pmapp/projects/create.php" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-plus-lg me-1"></i>New Project
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-body">
            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="bi bi-folder2"></i></div>
                        <div>
                            <div class="stat-label">Projects</div>
                            <div class="stat-value"><?= (int)$stats['total_projects'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-list-task"></i></div>
                        <div>
                            <div class="stat-label">Total Tasks</div>
                            <div class="stat-value"><?= (int)$stats['total_tasks'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="stat-label">Completed</div>
                            <div class="stat-value"><?= (int)$stats['completed_tasks'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="stat-label">Pending</div>
                            <div class="stat-value"><?= (int)$stats['pending_tasks'] ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress bar -->
            <?php
            $pct = ($stats['total_tasks'] > 0)
                ? round($stats['completed_tasks'] / $stats['total_tasks'] * 100)
                : 0;
            ?>
            <div class="content-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">Overall Completion</span>
                        <span class="fw-bold text-primary"><?= $pct ?>%</span>
                    </div>
                    <div class="progress" style="height:10px;border-radius:50px;">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%;border-radius:50px;
                            background:linear-gradient(90deg,#6366f1,#8b5cf6)!important;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted"><?= (int)$stats['completed_tasks'] ?> completed</small>
                        <small class="text-muted"><?= (int)$stats['pending_tasks'] ?> remaining</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Tasks -->
                <div class="col-lg-<?= isAdmin() ? '7' : '12' ?>">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-check2-square me-2 text-primary"></i>Recent Tasks</span>
                            <a href="/php-pmapp/tasks/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($recentTasks && $recentTasks->num_rows > 0): ?>
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Task</th>
                                        <th>Project</th>
                                        <?php if (isAdmin()): ?><th>Assigned To</th><?php endif; ?>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($task = $recentTasks->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= htmlspecialchars($task['title']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($task['project_title']) ?></span></td>
                                        <?php if (isAdmin()): ?>
                                        <td class="text-muted small"><?= htmlspecialchars($task['assignee_name'] ?? '—') ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if ($task['status'] === 'completed'): ?>
                                                <span class="status-badge badge-completed"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p>No tasks yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Projects (Admin only) -->
                <?php if (isAdmin() && $recentProjects): ?>
                <div class="col-lg-5">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-folder2-open me-2 text-primary"></i>Projects</span>
                            <a href="/php-pmapp/projects/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if ($recentProjects->num_rows > 0): ?>
                                <?php while ($proj = $recentProjects->fetch_assoc()): ?>
                                <a href="/php-pmapp/projects/view.php?id=<?= $proj['id'] ?>" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2"
                                         style="background:#f9fafb;transition:.15s ease;"
                                         onmouseover="this.style.background='#eef2ff'"
                                         onmouseout="this.style.background='#f9fafb'">
                                        <div style="width:40px;height:40px;border-radius:12px;
                                                    background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                                    display:flex;align-items:center;justify-content:center;
                                                    color:#fff;font-size:1.1rem;flex-shrink:0;">
                                            <i class="bi bi-folder2"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-semibold text-dark small text-truncate"><?= htmlspecialchars($proj['title']) ?></div>
                                            <div class="text-muted" style="font-size:.75rem;"><?= (int)$proj['task_count'] ?> tasks</div>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                    </div>
                                </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-folder2 d-block"></i>
                                    <p>No projects yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
