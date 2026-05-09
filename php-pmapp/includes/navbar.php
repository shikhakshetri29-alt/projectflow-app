<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
$user    = getCurrentUser();
$initials = strtoupper(substr($user['name'], 0, 1));
$current  = basename($_SERVER['PHP_SELF']);
$dir      = basename(dirname($_SERVER['PHP_SELF']));

function navActive(string $page, string $currentPage): string {
    return str_contains($currentPage, $page) ? 'active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-brand">

    <div class="brand-icon">
        <i class="bi bi-columns-gap"></i>
    </div>

    <div class="brand-title">
        ProjectFlow
    </div>

</div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Main</div>
        <a href="/php-pmapp/dashboard/index.php" class="nav-link <?= ($dir === 'dashboard') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="sidebar-section-label">Work</div>
        <a href="/php-pmapp/projects/index.php" class="nav-link <?= ($dir === 'projects') ? 'active' : '' ?>">
            <i class="bi bi-folder2-open"></i> Projects
        </a>
        <a href="/php-pmapp/tasks/index.php" class="nav-link <?= ($dir === 'tasks') ? 'active' : '' ?>">
            <i class="bi bi-check2-square"></i> My Tasks
        </a>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section-label">Admin</div>
        <a href="/php-pmapp/projects/create.php" class="nav-link">
            <i class="bi bi-plus-circle"></i> New Project
        </a>
        <a href="/php-pmapp/tasks/create.php" class="nav-link">
            <i class="bi bi-plus-square"></i> New Task
        </a>
        <a href="/php-pmapp/auth/register.php" class="nav-link">
            <i class="bi bi-person-plus"></i> Add User
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar"><?= $initials ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="user-role"><?= $user['role'] ?></div>
            </div>
        </div>
        <a href="/php-pmapp/auth/logout.php" class="nav-link mt-2 text-danger" style="color:#f87171!important">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</div>
