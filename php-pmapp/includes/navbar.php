<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
$user     = getCurrentUser();
$initials = strtoupper(substr($user['name'], 0, 1));
$dir      = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- Mobile Hamburger Button -->
<button class="mobile-menu-toggle" id="menuToggle" onclick="toggleSidebar()">
    <i class="bi bi-list" id="menuIcon"></i>
</button>

<!-- Sidebar Overlay (mobile background) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-columns-gap"></i>
        </div>
        <div class="brand-title">ProjectFlow</div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Main</div>
        <a href="/dashboard/index.php" class="nav-link <?= ($dir === 'dashboard') ? 'active' : '' ?>" onclick="closeSidebar()">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="sidebar-section-label">Work</div>
        <a href="/projects/index.php" class="nav-link <?= ($dir === 'projects') ? 'active' : '' ?>" onclick="closeSidebar()">
            <i class="bi bi-folder2-open"></i> Projects
        </a>
        <a href="/tasks/index.php" class="nav-link <?= ($dir === 'tasks') ? 'active' : '' ?>" onclick="closeSidebar()">
            <i class="bi bi-check2-square"></i> My Tasks
        </a>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section-label">Admin</div>
        <a href="/projects/create.php" class="nav-link" onclick="closeSidebar()">
            <i class="bi bi-plus-circle"></i> New Project
        </a>
        <a href="/tasks/create.php" class="nav-link" onclick="closeSidebar()">
            <i class="bi bi-plus-square"></i> New Task
        </a>
        <a href="/auth/register.php" class="nav-link" onclick="closeSidebar()">
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
        <a href="/auth/logout.php" class="nav-link mt-2" style="color:#f87171!important">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</div>

<style>
.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 16px;
    left: 16px;
    z-index: 200;
    background: white;
    border: none;
    border-radius: 12px;
    width: 44px;
    height: 44px;
    font-size: 1.4rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    cursor: pointer;
    align-items: center;
    justify-content: center;
}
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 99;
    backdrop-filter: blur(2px);
}
.sidebar-overlay.active { display: block; }

@media (max-width: 768px) {
    .mobile-menu-toggle { display: flex; }
    .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
    .sidebar.sidebar-open { transform: translateX(0); }
    .main-content { margin-left: 0 !important; width: 100% !important; }
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('sidebar-open');
    overlay.classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('sidebar-open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
</script>
