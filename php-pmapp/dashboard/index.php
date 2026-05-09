<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$user = getCurrentUser();

if (isAdmin()) {
    $stats = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM projects)                        AS total_projects,
            (SELECT COUNT(*) FROM tasks)                           AS total_tasks,
            (SELECT COUNT(*) FROM tasks WHERE status='completed')  AS completed_tasks,
            (SELECT COUNT(*) FROM tasks WHERE status='pending')    AS pending_tasks
    ")->fetch_assoc();

    $recentTasks = $conn->query("
        SELECT t.*, p.title AS project_title, u.name AS assignee_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.assigned_to = u.id
        ORDER BY t.created_at DESC
        LIMIT 8
    ");

    $recentProjects = $conn->query("
        SELECT p.*, u.name AS creator_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) AS task_count
        FROM projects p
        JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
} else {
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

$taskRows = [];
if ($recentTasks && $recentTasks->num_rows > 0) {
    while ($t = $recentTasks->fetch_assoc()) $taskRows[] = $t;
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
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        /* ══ STAT CARDS — 4 col desktop, 2 col mobile ══ */
        .stat-grid-custom {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 900px) {
            .stat-grid-custom { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .stat-grid-custom { grid-template-columns: repeat(2, 1fr); gap: 0.6rem; }
            .stat-card { padding: 1rem !important; }
            .stat-value { font-size: 1.6rem !important; }
        }

        /* ══ TABLE: no cut on laptop ══ */
        .tasks-table-wrap { width: 100%; overflow-x: auto; }
        .tasks-table-wrap .table { min-width: 480px; table-layout: auto; }
        .tasks-table-wrap .table td,
        .tasks-table-wrap .table th { white-space: normal !important; word-break: break-word; }
        .tasks-table-wrap .table td:first-child,
        .tasks-table-wrap .table th:first-child { min-width: 150px; }

        /* ══ MOBILE/DESKTOP TOGGLE ══ */
        .desktop-only { display: block; }
        .mobile-only  { display: none;  }
        @media (max-width: 768px) {
            .desktop-only { display: none !important; }
            .mobile-only  { display: block !important; }
            .welcome-text { display: none !important; }
            .topbar .btn  { padding: 6px 12px !important; font-size: 0.78rem !important; width: auto !important; margin: 0 !important; }
            .card-header .btn { padding: 5px 12px !important; font-size: 0.78rem !important; width: auto !important; }
        }

        /* ══ MOBILE TASK CARDS ══ */
        .dash-task-card {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            background: rgba(255,255,255,0.95);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 10px;
            border: 1px solid rgba(148,163,184,0.15);
            box-shadow: 0 2px 8px rgba(99,102,241,0.06);
            transition: all 0.25s ease;
        }
        .dash-task-card:hover { box-shadow: 0 6px 18px rgba(99,102,241,0.12); transform: translateY(-2px); }
        .dtc-info { flex: 1; min-width: 0; }
        .dtc-name { font-weight: 700; font-size: 0.9rem; color: #0f172a; margin-bottom: 5px; word-break: break-word; }
        .dtc-project { margin-bottom: 4px; }
        .dtc-project .badge { font-size: 0.72rem; padding: 3px 8px; white-space: normal; }
        .dtc-assignee { font-size: 0.73rem; color: #94a3b8; }
        .dtc-status { flex-shrink: 0; padding-top: 2px; }
        .dtc-status .status-badge { font-size: 0.7rem !important; padding: 4px 10px !important; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small welcome-text">Welcome back, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
                <?php if (isAdmin()): ?>
                    <a href="/projects/create.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>New Project
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-body">

            <!-- STAT CARDS -->
            <div class="stat-grid-custom">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="bi bi-folder2"></i></div>
                    <div><div class="stat-label">Projects</div><div class="stat-value"><?= (int)$stats['total_projects'] ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="bi bi-list-task"></i></div>
                    <div><div class="stat-label">Total Tasks</div><div class="stat-value"><?= (int)$stats['total_tasks'] ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
                    <div><div class="stat-label">Completed</div><div class="stat-value"><?= (int)$stats['completed_tasks'] ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                    <div><div class="stat-label">Pending</div><div class="stat-value"><?= (int)$stats['pending_tasks'] ?></div></div>
                </div>
            </div>

            <!-- PROGRESS BAR -->
            <?php $pct = ($stats['total_tasks'] > 0) ? round($stats['completed_tasks'] / $stats['total_tasks'] * 100) : 0; ?>
            <div class="content-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">Overall Completion</span>
                        <span class="fw-bold text-primary"><?= $pct ?>%</span>
                    </div>
                    <div class="progress" style="height:10px;border-radius:50px;">
                        <div class="progress-bar" style="width:<?= $pct ?>%;border-radius:50px;background:linear-gradient(90deg,#6366f1,#8b5cf6)!important;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted"><?= (int)$stats['completed_tasks'] ?> completed</small>
                        <small class="text-muted"><?= (int)$stats['pending_tasks'] ?> remaining</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <!-- RECENT TASKS -->
                <div class="col-lg-<?= isAdmin() ? '7' : '12' ?>">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-check2-square me-2 text-primary"></i>Recent Tasks</span>
                            <a href="/tasks/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($taskRows) > 0): ?>

                            <!-- DESKTOP TABLE -->
                            <div class="tasks-table-wrap desktop-only">
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
                                    <?php foreach ($taskRows as $task): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($task['title']) ?></td>
                                            <td><span class="badge bg-light text-dark border" style="white-space:normal;"><?= htmlspecialchars($task['project_title']) ?></span></td>
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
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- MOBILE CARDS -->
                            <div class="mobile-only p-3">
                                <?php foreach ($taskRows as $task): ?>
                                <div class="dash-task-card">
                                    <div class="dtc-info">
                                        <div class="dtc-name"><?= htmlspecialchars($task['title']) ?></div>
                                        <div class="dtc-project">
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($task['project_title']) ?></span>
                                        </div>
                                        <?php if (isAdmin() && !empty($task['assignee_name'])): ?>
                                        <div class="dtc-assignee"><i class="bi bi-person me-1"></i><?= htmlspecialchars($task['assignee_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dtc-status">
                                        <?php if ($task['status'] === 'completed'): ?>
                                            <span class="status-badge badge-completed"><i class="bi bi-check-circle-fill"></i> Done</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <?php else: ?>
                                <div class="empty-state"><i class="bi bi-inbox d-block"></i><p>No tasks yet.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- RECENT PROJECTS (Admin) -->
                <?php if (isAdmin() && $recentProjects): ?>
                <div class="col-lg-5">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-folder2-open me-2 text-primary"></i>Projects</span>
                            <a href="/projects/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if ($recentProjects->num_rows > 0): ?>
                                <?php while ($proj = $recentProjects->fetch_assoc()): ?>
                                <a href="/projects/view.php?id=<?= $proj['id'] ?>" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2"
                                         style="background:#f9fafb;transition:.15s ease;"
                                         onmouseover="this.style.background='#eef2ff'"
                                         onmouseout="this.style.background='#f9fafb'">
                                        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;flex-shrink:0;">
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
                                <div class="empty-state"><i class="bi bi-folder2 d-block"></i><p>No projects yet.</p></div>
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
