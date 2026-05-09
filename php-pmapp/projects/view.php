<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /php-pmapp/projects/index.php'); exit; }

if (isAdmin()) {
    $stmt = $conn->prepare("
        SELECT p.*, u.name AS creator_name
        FROM projects p JOIN users u ON p.created_by = u.id
        WHERE p.id = ?
    ");
} else {
    // Member can only view projects they belong to
    $stmt = $conn->prepare("
        SELECT p.*, u.name AS creator_name
        FROM projects p
        JOIN users u ON p.created_by = u.id
        JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE p.id = ?
    ");
    $stmt->bind_param('ii', $_SESSION['user_id'], $id);
}
if (isAdmin()) $stmt->bind_param('i', $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) { header('Location: /php-pmapp/projects/index.php'); exit; }

// Tasks in this project
if (isAdmin()) {
    $stmt = $conn->prepare("
        SELECT t.*, u.name AS assignee_name
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.project_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param('i', $id);
} else {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT t.*, u.name AS assignee_name
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.project_id = ? AND t.assigned_to = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param('ii', $id, $uid);
}
$stmt->execute();
$tasks = $stmt->get_result();
$stmt->close();

// Members in this project
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email
    FROM project_members pm
    JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$projectMembers = $stmt->get_result();
$stmt->close();

// Stats
$statsStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status='completed') AS done,
        SUM(status='pending') AS pending
    FROM tasks WHERE project_id = ?
");
$statsStmt->bind_param('i', $id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();
$pct = ($stats['total'] > 0) ? round($stats['done'] / $stats['total'] * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/php-pmapp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div>
                <div class="topbar-title"><?= htmlspecialchars($project['title']) ?></div>
                <small class="text-muted">by <?= htmlspecialchars($project['creator_name']) ?></small>
            </div>
            <?php if (isAdmin()): ?>
            <div class="d-flex gap-2">
                <a href="/php-pmapp/tasks/create.php?project_id=<?= $id ?>" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add Task
                </a>
                <a href="/php-pmapp/projects/members.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-people me-1"></i> Members
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="page-body">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-list-task"></i></div>
                        <div><div class="stat-label">Total Tasks</div><div class="stat-value"><?= (int)$stats['total'] ?></div></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                        <div><div class="stat-label">Completed</div><div class="stat-value"><?= (int)$stats['done'] ?></div></div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
                        <div><div class="stat-label">Pending</div><div class="stat-value"><?= (int)$stats['pending'] ?></div></div>
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div class="content-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold small">Project Progress</span>
                        <span class="fw-bold text-primary"><?= $pct ?>%</span>
                    </div>
                    <div class="progress" style="height:8px;border-radius:50px;">
                        <div class="progress-bar" style="width:<?= $pct ?>%;border-radius:50px;
                            background:linear-gradient(90deg,#6366f1,#8b5cf6)!important;"></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-check2-square me-2 text-primary"></i>Tasks</span>
                            <?php if (isAdmin()): ?>
                            <a href="/php-pmapp/tasks/create.php?project_id=<?= $id ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Task
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($tasks->num_rows > 0): ?>
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Task</th>
                                        <th>Assigned To</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($task = $tasks->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold"><?= htmlspecialchars($task['title']) ?></div>
                                        <?php if ($task['description']): ?>
                                        <small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 60)) ?><?= strlen($task['description']) > 60 ? '…' : '' ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <?php if ($task['status'] === 'completed'): ?>
                                            <span class="status-badge badge-completed"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                        <?php else: ?>
                                            <span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $isAssigned = ($task['assigned_to'] == $_SESSION['user_id']);
                                        $canUpdate  = isAdmin() || $isAssigned;
                                        if ($canUpdate && $task['status'] === 'pending'):
                                        ?>
                                        <form method="POST" action="/tasks/update_status.php" style="display:inline;">
                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <input type="hidden" name="redirect" value="/projects/view.php?id=<?= $id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check2 me-1"></i>Mark Done
                                            </button>
                                        </form>
                                        <?php elseif ($canUpdate && $task['status'] === 'completed'): ?>
                                        <form method="POST" action="/tasks/update_status.php" style="display:inline;">
                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                            <input type="hidden" name="status" value="pending">
                                            <input type="hidden" name="redirect" value="/projects/view.php?id=<?= $id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reopen
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-check2-square d-block"></i>
                                    <p>No tasks in this project yet.</p>
                                    <?php if (isAdmin()): ?>
                                    <a href="/php-pmapp/tasks/create.php?project_id=<?= $id ?>" class="btn btn-primary btn-sm mt-2">Add First Task</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-people me-2 text-primary"></i>Team Members</span>
                        </div>
                        <div class="card-body">
                            <?php if ($projectMembers->num_rows > 0): ?>
                                <?php while ($m = $projectMembers->fetch_assoc()): ?>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div style="width:36px;height:36px;border-radius:50%;
                                                background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                                display:flex;align-items:center;justify-content:center;
                                                color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($m['name']) ?></div>
                                        <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($m['email']) ?></div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state py-3">
                                    <i class="bi bi-people d-block"></i>
                                    <p class="small">No members assigned yet.</p>
                                </div>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                            <a href="/php-pmapp/projects/members.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                                <i class="bi bi-person-plus me-1"></i> Manage Members
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($project['description']): ?>
                    <div class="content-card mt-4">
                        <div class="card-header"><span><i class="bi bi-info-circle me-2 text-primary"></i>About</span></div>
                        <div class="card-body">
                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                        </div>
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
