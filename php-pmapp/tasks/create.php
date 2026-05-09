<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$error         = '';
$preProjectId  = (int)($_GET['project_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $projectId   = (int)($_POST['project_id'] ?? 0);
    $assignedTo  = (int)($_POST['assigned_to'] ?? 0) ?: null;
    $status      = $_POST['status'] ?? 'pending';
    $createdBy   = $_SESSION['user_id'];

    if (!$title)     { $error = 'Task title is required.'; }
    elseif (!$projectId) { $error = 'Please select a project.'; }
    else {
        $stmt = $conn->prepare("
            INSERT INTO tasks (project_id, assigned_to, title, description, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisssi', $projectId, $assignedTo, $title, $description, $status, $createdBy);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: /php-pmapp/tasks/index.php?project_id=$projectId&msg=Task+created+successfully");
            exit;
        } else {
            $error = 'Failed to create task.';
            $stmt->close();
        }
    }
}

$projects = $conn->query("SELECT id, title FROM projects ORDER BY title");

// Members in the selected project (or all members if no project selected)
$pid = (int)($_POST['project_id'] ?? $preProjectId);
if ($pid) {
    $ms = $conn->prepare("SELECT u.id, u.name FROM users u JOIN project_members pm ON pm.user_id=u.id WHERE pm.project_id=? ORDER BY u.name");
    $ms->bind_param('i', $pid);
    $ms->execute();
    $members = $ms->get_result();
    $ms->close();
} else {
    $members = $conn->query("SELECT id, name FROM users WHERE role='member' ORDER BY name");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Task — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/php-pmapp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Create New Task</div>
            <a href="/php-pmapp/tasks/index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="page-body">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-plus-square me-2 text-primary"></i>Task Details</span>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="taskForm">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control"
                                           placeholder="e.g. Design landing page mockup"
                                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" class="form-control" rows="3"
                                              placeholder="Optional task details..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                                        <select name="project_id" class="form-select" required id="projectSelect">
                                            <option value="">Select project...</option>
                                            <?php while ($p = $projects->fetch_assoc()): ?>
                                            <option value="<?= $p['id'] ?>"
                                                <?= (($_POST['project_id'] ?? $preProjectId) == $p['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['title']) ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Assign To</label>
                                        <select name="assigned_to" class="form-select">
                                            <option value="">Unassigned</option>
                                            <?php while ($m = $members->fetch_assoc()): ?>
                                            <option value="<?= $m['id'] ?>"
                                                <?= (($_POST['assigned_to'] ?? '') == $m['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['name']) ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Initial Status</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status"
                                                   id="statusPending" value="pending"
                                                   <?= (($_POST['status'] ?? 'pending') === 'pending') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="statusPending">
                                                <span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status"
                                                   id="statusCompleted" value="completed"
                                                   <?= (($_POST['status'] ?? '') === 'completed') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="statusCompleted">
                                                <span class="status-badge badge-completed"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                                        <i class="bi bi-plus-circle me-1"></i> Create Task
                                    </button>
                                    <a href="/php-pmapp/tasks/index.php" class="btn btn-outline-secondary px-4 py-2">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Reload page when project changes so member list updates
document.getElementById('projectSelect').addEventListener('change', function() {
    const pid = this.value;
    if (pid) window.location.href = '/tasks/create.php?project_id=' + pid;
});
</script>
</body>
</html>
