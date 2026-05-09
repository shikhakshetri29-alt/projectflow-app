<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$user      = getCurrentUser();
$projectId = (int)($_GET['project_id'] ?? 0);
$status    = $_GET['status'] ?? '';

$params = [];
$types  = '';
$where  = [];

if (isAdmin()) {
    if ($projectId) { $where[] = "t.project_id = ?"; $params[] = $projectId; $types .= 'i'; }
    if ($status)    { $where[] = "t.status = ?";     $params[] = $status;    $types .= 's'; }
} else {
    $where[]  = "t.assigned_to = ?";
    $params[] = $user['id'];
    $types   .= 'i';
    if ($projectId) { $where[] = "t.project_id = ?"; $params[] = $projectId; $types .= 'i'; }
    if ($status)    { $where[] = "t.status = ?";     $params[] = $status;    $types .= 's'; }
}

$sql = "
    SELECT t.*, p.title AS project_title, u.name AS assignee_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assigned_to = u.id
" . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY t.created_at DESC
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tasks = $stmt->get_result();
$stmt->close();

// Projects for filter
$projectsForFilter = isAdmin()
    ? $conn->query("SELECT id, title FROM projects ORDER BY title")
    : $conn->prepare("SELECT p.id, p.title FROM projects p JOIN project_members pm ON pm.project_id=p.id AND pm.user_id=" . $user['id'] . " ORDER BY p.title") && false;

if (!isAdmin()) {
    $fs = $conn->prepare("SELECT p.id, p.title FROM projects p JOIN project_members pm ON pm.project_id=p.id WHERE pm.user_id=? ORDER BY p.title");
    $fs->bind_param('i', $user['id']);
    $fs->execute();
    $projectsForFilter = $fs->get_result();
    $fs->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        /* ── MOBILE CARD TABLE ── */
        @media (max-width: 768px) {
            .mobile-task-table { display: none !important; }
            .mobile-task-cards { display: block !important; }
        }
        @media (min-width: 769px) {
            .mobile-task-table { display: table !important; }
            .mobile-task-cards { display: none !important; }
        }

        /* Task Card Style */
        .task-card-item {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid rgba(148,163,184,0.15);
            box-shadow: 0 2px 8px rgba(99,102,241,0.07);
            transition: all 0.3s ease;
        }
        .task-card-item:hover {
            box-shadow: 0 6px 20px rgba(99,102,241,0.13);
            transform: translateY(-2px);
        }
        .task-card-item .task-title {
            font-weight: 700;
            font-size: 0.97rem;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .task-card-item .task-desc {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 10px;
        }
        .task-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .task-meta-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            margin-right: 2px;
        }
        .task-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid rgba(148,163,184,0.12);
        }

        /* Filter form mobile fix */
        @media (max-width: 768px) {
            .filter-form .col-sm-5,
            .filter-form .col-sm-4,
            .filter-form .col-sm-3 {
                width: 100% !important;
            }
            .filter-form .btn {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title"><?= isAdmin() ? 'All Tasks' : 'My Tasks' ?></div>
            <?php if (isAdmin()): ?>
            <a href="/tasks/create.php" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-plus-lg me-1"></i> New Task
            </a>
            <?php endif; ?>
        </div>

        <div class="page-body">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="content-card mb-4">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-end filter-form">
                        <div class="col-sm-5">
                            <label class="form-label small fw-semibold mb-1">Filter by Project</label>
                            <select name="project_id" class="form-select form-select-sm">
                                <option value="">All Projects</option>
                                <?php if ($projectsForFilter): while ($p = $projectsForFilter->fetch_assoc()): ?>
                                    <option value="<?= $p['id'] ?>" <?= $projectId == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['title']) ?>
                                    </option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold mb-1">Filter by Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="pending"   <?= $status === 'pending'   ? 'selected' : '' ?>>Pending</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-card">
                <div class="card-body p-0">
                    <?php
                    $taskRows = [];
                    if ($tasks->num_rows > 0) {
                        while ($t = $tasks->fetch_assoc()) $taskRows[] = $t;
                    }
                    ?>

                    <?php if (count($taskRows) > 0): ?>

                    <!-- ══ DESKTOP TABLE ══ -->
                    <table class="table mb-0 mobile-task-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Task</th>
                                <th>Project</th>
                                <?php if (isAdmin()): ?><th>Assigned To</th><?php endif; ?>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($taskRows as $task): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold"><?= htmlspecialchars($task['title']) ?></div>
                                <?php if ($task['description']): ?>
                                <small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 55)) ?><?= strlen($task['description']) > 55 ? '…' : '' ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/projects/view.php?id=<?= $task['project_id'] ?>"
                                   class="badge bg-light text-dark border text-decoration-none">
                                    <?= htmlspecialchars($task['project_title']) ?>
                                </a>
                            </td>
                            <?php if (isAdmin()): ?>
                            <td class="text-muted small"><?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?></td>
                            <?php endif; ?>
                            <td>
                                <?php if ($task['status'] === 'completed'): ?>
                                    <span class="status-badge badge-completed"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                <?php else: ?>
                                    <span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('M j, Y', strtotime($task['created_at'])) ?></td>
                            <td>
                                <?php $canUpdate = isAdmin() || $task['assigned_to'] == $user['id']; ?>
                                <?php if ($canUpdate): ?>
                                <form method="POST" action="/tasks/update_status.php" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $task['status'] === 'pending' ? 'completed' : 'pending' ?>">
                                    <input type="hidden" name="redirect" value="/tasks/index.php?project_id=<?= $projectId ?>&status=<?= urlencode($status) ?>">
                                    <button type="submit" class="btn btn-sm <?= $task['status'] === 'pending' ? 'btn-outline-success' : 'btn-outline-secondary' ?>">
                                        <?php if ($task['status'] === 'pending'): ?>
                                            <i class="bi bi-check2"></i> Done
                                        <?php else: ?>
                                            <i class="bi bi-arrow-counterclockwise"></i> Reopen
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- ══ MOBILE CARDS ══ -->
                    <div class="mobile-task-cards p-3">
                        <?php foreach ($taskRows as $task): ?>
                        <div class="task-card-item">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <?php if ($task['description']): ?>
                            <div class="task-desc"><?= htmlspecialchars(substr($task['description'], 0, 80)) ?><?= strlen($task['description']) > 80 ? '…' : '' ?></div>
                            <?php endif; ?>

                            <div class="task-card-meta">
                                <!-- Project badge -->
                                <div>
                                    <span class="task-meta-label">Project</span>
                                    <a href="/projects/view.php?id=<?= $task['project_id'] ?>"
                                       class="badge bg-light text-dark border text-decoration-none" style="font-size:0.78rem;">
                                        <?= htmlspecialchars($task['project_title']) ?>
                                    </a>
                                </div>

                                <?php if (isAdmin() && !empty($task['assignee_name'])): ?>
                                <!-- Assigned To -->
                                <div>
                                    <span class="task-meta-label">Assigned</span>
                                    <span style="font-size:0.82rem;color:#475569;"><?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="task-card-footer">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <!-- Status -->
                                    <?php if ($task['status'] === 'completed'): ?>
                                        <span class="status-badge badge-completed"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                    <?php endif; ?>
                                    <!-- Date -->
                                    <span style="font-size:0.75rem;color:#94a3b8;">
                                        <i class="bi bi-calendar3 me-1"></i><?= date('M j, Y', strtotime($task['created_at'])) ?>
                                    </span>
                                </div>

                                <!-- Action Button -->
                                <?php $canUpdate = isAdmin() || $task['assigned_to'] == $user['id']; ?>
                                <?php if ($canUpdate): ?>
                                <form method="POST" action="/tasks/update_status.php">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $task['status'] === 'pending' ? 'completed' : 'pending' ?>">
                                    <input type="hidden" name="redirect" value="/tasks/index.php?project_id=<?= $projectId ?>&status=<?= urlencode($status) ?>">
                                    <button type="submit" class="btn btn-sm <?= $task['status'] === 'pending' ? 'btn-outline-success' : 'btn-outline-secondary' ?>" style="min-width:90px;">
                                        <?php if ($task['status'] === 'pending'): ?>
                                            <i class="bi bi-check2"></i> Mark Done
                                        <?php else: ?>
                                            <i class="bi bi-arrow-counterclockwise"></i> Reopen
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-check2-square d-block"></i>
                            <h6 class="fw-bold mb-1">No tasks found</h6>
                            <p>Try adjusting your filters.</p>
                            <?php if (isAdmin()): ?>
                            <a href="/tasks/create.php" class="btn btn-primary btn-sm mt-2">Add a Task</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
