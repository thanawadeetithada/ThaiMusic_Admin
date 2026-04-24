<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    :root {
        --sidebar-bg: #11222c;
        --sidebar-hover: #1d3542;
        --primary-gold: #d4af37;
        --topbar-bg: #203a43;
    }

    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    
    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: var(--sidebar-bg);
        position: fixed;
        top: 0;
        left: 0;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-brand {
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-gold);
        font-size: 22px;
        font-weight: 700;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        letter-spacing: 1px;
    }

    .sidebar-brand i {
        margin-right: 10px;
        font-size: 24px;
    }

    .sidebar-nav {
        list-style: none;
        padding: 20px 0;
        margin: 0;
    }

    .sidebar-nav li {
        padding: 0 15px;
        margin-bottom: 5px;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        color: #a0a5b1;
        padding: 12px 20px;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 15px;
    }

    .sidebar-nav a i {
        width: 25px;
        font-size: 18px;
    }

    .sidebar-nav a:hover {
        background-color: var(--sidebar-hover);
        color: #fff;
    }

    
    .sidebar-nav a.active {
        background-color: var(--primary-gold);
        color: #fff;
        box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3);
    }

    
    .top-navbar {
        height: 70px;
        background-color: var(--topbar-bg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        color: white;
    }

    .toggle-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }

    .toggle-btn:hover {
        color: var(--primary-gold);
    }

    
    .main-wrapper {
        margin-left: 250px;
        transition: all 0.3s ease;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .content-area {
        padding: 30px;
        flex-grow: 1;
    }

    
    @media (max-width: 768px) {
        .sidebar {
            left: -250px; 
        }
        .sidebar.active {
            left: 0; 
        }
        .main-wrapper {
            margin-left: 0;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.active {
            display: block;
        }
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-music"></i> Thai Music
    </div>
    <ul class="sidebar-nav">
        <li>
            <a href="ensembles.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> หน้าหลัก
            </a>
        </li>
        <li>
            <a href="user_management.php" class="<?= ($current_page == 'user_management.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> จัดการผู้ใช้งาน
            </a>
        </li>
    </ul>
</aside>

<div class="main-wrapper">
    <nav class="top-navbar">
        <div>
            <button class="toggle-btn" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
        </div>
        <div class="d-flex align-items-center">
            <span class="me-3 fw-light">
                <i class="fa-solid fa-circle-user text-warning me-1"></i> 
                <?= htmlspecialchars($_SESSION['first_name'] ?? 'Admin') ?>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-light" style="border-radius: 20px;">
                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>
    </nav>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleMenu() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }

            toggleBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu); // กดที่เงาดำเพื่อปิดเมนู
        });
    </script>