<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $memberIds   = $_POST['members'] ?? [];
    $createdBy   = $_SESSION['user_id'];

    if (!$title) {
        $error = 'Project title is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO projects (title, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $title, $description, $createdBy);

        if ($stmt->execute()) {
            $projectId = $stmt->insert_id;
            $stmt->close();

            // Add selected members
            if (!empty($memberIds)) {
                $ins = $conn->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
                foreach ($memberIds as $uid) {
                    $uid = (int)$uid;
                    if ($uid > 0) {
                        $ins->bind_param('ii', $projectId, $uid);
                        $ins->execute();
                    }
                }
                $ins->close();
            }

            header("Location: /php-pmapp/projects/view.php?id=$projectId&msg=Project+created+successfully");
            exit;
        } else {
            $error = 'Failed to create project. Please try again.';
            $stmt->close();
        }
    }
}

$members = $conn->query("SELECT id, name, email FROM users WHERE role = 'member' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Project — ProjectFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/php-pmapp/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Create New Project</div>
            <a href="/php-pmapp/projects/index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="page-body">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="content-card">
                        <div class="card-header">
                            <span><i class="bi bi-folder-plus me-2 text-primary"></i>Project Details</span>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Project Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control"
                                           placeholder="e.g. Website Redesign Q3"
                                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" class="form-control" rows="4"
                                              placeholder="Brief description of this project..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Assign Members</label>
                                    <p class="text-muted small mb-2">Select team members to add to this project.</p>
                                    <?php if ($members && $members->num_rows > 0): ?>
                                        <div class="border rounded-3 p-3" style="max-height:200px;overflow-y:auto;">
                                            <?php while ($m = $members->fetch_assoc()): ?>
                                            <div class="form-check py-1">
                                                <input class="form-check-input" type="checkbox"
                                                       name="members[]" value="<?= $m['id'] ?>"
                                                       id="member_<?= $m['id'] ?>"
                                                       <?= in_array($m['id'], $_POST['members'] ?? []) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="member_<?= $m['id'] ?>">
                                                    <strong><?= htmlspecialchars($m['name']) ?></strong>
                                                    <span class="text-muted small ms-1"><?= htmlspecialchars($m['email']) ?></span>
                                                </label>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-1"></i>
                                            No member accounts found. <a href="/php-pmapp/auth/register.php" target="_blank">Create one first.</a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                                        <i class="bi bi-plus-circle me-1"></i> Create Project
                                    </button>
                                    <a href="/php-pmapp/projects/index.php" class="btn btn-outline-secondary px-4 py-2">
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
</body>
</html>
