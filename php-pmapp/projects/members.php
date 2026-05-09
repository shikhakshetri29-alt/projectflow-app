<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /php-pmapp/projects/index.php'); exit; }

$project = $conn->query("SELECT * FROM projects WHERE id = $id")->fetch_assoc();
if (!$project) { header('Location: /php-pmapp/projects/index.php'); exit; }

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberIds = $_POST['members'] ?? [];

    // Clear existing members
    $conn->query("DELETE FROM project_members WHERE project_id = $id");

    // Insert selected
    if (!empty($memberIds)) {
        $ins = $conn->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
        foreach ($memberIds as $uid) {
            $uid = (int)$uid;
            if ($uid > 0) {
                $ins->bind_param('ii', $id, $uid);
                $ins->execute();
            }
        }
        $ins->close();
    }

    $message = 'Project members updated successfully.';
}

// All members
$allMembers = $conn->query("SELECT id, name, email FROM users WHERE role = 'member' ORDER BY name");

// Current project members
$currentMemberIds = [];
$res = $conn->query("SELECT user_id FROM project_members WHERE project_id = $id");
while ($row = $res->fetch_assoc()) {
    $currentMemberIds[] = $row['user_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/php-pmapp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Manage Members — <?= htmlspecialchars($project['title']) ?></div>
            <a href="/php-pmapp/projects/view.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Project
            </a>
        </div>
        <div class="page-body">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-people me-2 text-primary"></i>Team Members</span>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-success d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($message) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <p class="text-muted small mb-3">Check the members you want to add to this project.</p>
                                <?php if ($allMembers && $allMembers->num_rows > 0): ?>
                                    <?php while ($m = $allMembers->fetch_assoc()): ?>
                                    <div class="form-check border rounded-3 p-3 mb-2 <?= in_array($m['id'], $currentMemberIds) ? 'border-primary bg-light' : '' ?>">
                                        <input class="form-check-input" type="checkbox"
                                               name="members[]" value="<?= $m['id'] ?>"
                                               id="m<?= $m['id'] ?>"
                                               <?= in_array($m['id'], $currentMemberIds) ? 'checked' : '' ?>>
                                        <label class="form-check-label d-flex align-items-center gap-3" for="m<?= $m['id'] ?>">
                                            <div style="width:36px;height:36px;border-radius:50%;
                                                        background:linear-gradient(135deg,#6366f1,#8b5cf6);
                                                        display:flex;align-items:center;justify-content:center;
                                                        color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                                                <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($m['name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($m['email']) ?></div>
                                            </div>
                                        </label>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No member accounts exist yet.
                                        <a href="/php-pmapp/auth/register.php" target="_blank">Create one</a>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Save Members
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
