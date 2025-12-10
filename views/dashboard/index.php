<?php
// File: views/dashboard/index.php

require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
$middleware = new AuthMiddleware();
$middleware->requireLogin();

$userData = $middleware->getUserData();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Agenda Pimpinan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if ($userData['avatar'] && $userData['avatar'] !== 'default.png'): ?>
                            <img src="assets/uploads/avatars/<?php echo e($userData['avatar']); ?>" 
                                 alt="<?php echo e($userData['full_name']); ?>">
                        <?php else: ?>
                            <div class="avatar-initials">
                                <?php echo getUserInitials($userData['full_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <h4><?php echo e($userData['full_name']); ?></h4>
                        <span class="user-role"><?php echo roleDisplayName($userData['role']); ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="index.php?page=dashboard">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=agenda">
                            <i class="fas fa-calendar-alt"></i> Agenda
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=tindaklanjut">
                            <i class="fas fa-tasks"></i> Tindak Lanjut
                        </a>
                    </li>
                    
                    <?php if (can('user_view')): ?>
                    <li>
                        <a href="index.php?page=users">
                            <i class="fas fa-users"></i> Manajemen User
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (can('settings_view')): ?>
                    <li>
                        <a href="index.php?page=settings">
                            <i class="fas fa-cog"></i> Pengaturan
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="index.php?page=profile" class="profile-link">
                    <i class="fas fa-user"></i> Profil
                </a>
                <a href="logout.php" class="logout-link" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <?php echo breadcrumb(['Dashboard', 'Home']); ?>
                </div>
                
                <div class="header-right">
                    <div class="header-actions">
                        <button class="btn-notification" title="Notifikasi">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        
                        <button class="btn-theme-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        
                        <div class="user-dropdown">
                            <button class="btn-user">
                                <span><?php echo e($userData['full_name']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="index.php?page=profile">
                                    <i class="fas fa-user"></i> Profil
                                </a>
                                <a href="logout.php" onclick="return confirm('Yakin ingin logout?')">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="content-wrapper">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Agenda</h3>
                            <p class="stat-number">24</p>
                            <span class="stat-change">+2 dari bulan lalu</span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Selesai</h3>
                            <p class="stat-number">18</p>
                            <span class="stat-change">75% completion rate</span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Proses</h3>
                            <p class="stat-number">4</p>
                            <span class="stat-change">2 overdue</span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pengguna</h3>
                            <p class="stat-number">12</p>
                            <span class="stat-change">4 aktif sekarang</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="recent-activities">
                    <div class="card">
                        <div class="card-header">
                            <h3>Aktivitas Terbaru</h3>
                            <a href="#" class="btn-view-all">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <ul class="activity-list">
                                <li class="activity-item">
                                    <div class="activity-icon activity-success">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><strong>Rapat Koordinasi</strong> telah selesai</p>
                                        <span class="activity-time">2 jam yang lalu</span>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon activity-primary">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Agenda baru <strong>Kunjungan Kerja</strong> ditambahkan</p>
                                        <span class="activity-time">Hari ini, 09:30</span>
                                    </div>
                                </li>
                                <li class="activity-item">
                                    <div class="activity-icon activity-warning">
                                        <i class="fas fa-exclamation"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><strong>Rapat Budgeting</strong> mendekati deadline</p>
                                        <span class="activity-time">Kemarin, 16:45</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Aksi Cepat</h3>
                    <div class="action-buttons">
                        <?php if (can('agenda_create')): ?>
                        <a href="index.php?page=agenda&action=create" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Tambah Agenda</span>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (can('user_create')): ?>
                        <a href="index.php?page=users&action=create" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Tambah User</span>
                        </a>
                        <?php endif; ?>
                        
                        <a href="export.php" class="action-btn">
                            <i class="fas fa-download"></i>
                            <span>Ekspor Data</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="main-footer">
                <p>&copy; <?php echo date('Y'); ?> Sistem Agenda Pimpinan v2.0</p>
                <p>Login sebagai: <strong><?php echo e($userData['username']); ?></strong> 
                (<?php echo roleDisplayName($userData['role']); ?>)</p>
            </footer>
        </main>
    </div>

    <script>
        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
            
            const icon = document.querySelector('.btn-theme-toggle i');
            if (document.body.classList.contains('dark-mode')) {
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        }
        
        // Initialize dark mode
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            const icon = document.querySelector('.btn-theme-toggle i');
            if (icon) icon.className = 'fas fa-sun';
        }
        
        // User dropdown
        document.querySelector('.btn-user').addEventListener('click', function(e) {
            e.stopPropagation();
            this.parentElement.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown) dropdown.classList.remove('show');
        });
        
        // Auto logout after 30 minutes of inactivity
        let idleTime = 0;
        const idleInterval = setInterval(timerIncrement, 60000); // 1 minute
        
        function timerIncrement() {
            idleTime++;
            if (idleTime > 30) { // 30 minutes
                clearInterval(idleInterval);
                window.location.href = 'logout.php?timeout=1';
            }
        }
        
        // Reset idle time on user activity
        document.addEventListener('mousemove', resetIdleTime);
        document.addEventListener('keypress', resetIdleTime);
        
        function resetIdleTime() {
            idleTime = 0;
        }
    </script>
</body>
</html>