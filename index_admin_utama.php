<?php
ob_start();
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once("config/koneksi.php");

// Ambil parameter halaman
$halaman = isset($_GET['page_admin_utama']) ? $_GET['page_admin_utama'] : '';
$halaman = trim($halaman);
$halaman = str_replace(['..', './', '//'], '', $halaman);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Manajemen Inventaris Gereja</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --primary-dark: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #ffffff;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --border-light: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-secondary);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Enhanced Top Bar - FIXED POSITIONING */
        .top-bar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-lg);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .left-section {
            display: flex;
            align-items: center;
            gap: 32px;
            min-width: 280px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--text-light);
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            box-shadow: var(--shadow-md);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .username {
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.025em;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
            color: var(--accent-color);
            font-weight: 500;
        }

        .user-popup {
            position: absolute;
            top: calc(100% + 12px);
            left: 20px;
            background: var(--bg-primary);
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            padding: 0;
            min-width: 260px;
            display: none;
            z-index: 1100;
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .user-popup.show {
            display: block;
        }

        .user-popup-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--bg-tertiary), var(--bg-secondary));
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-popup-header .user-avatar {
            width: 45px;
            height: 45px;
        }

        .user-popup-header .user-info .username {
            color: var(--text-primary);
            font-size: 1rem;
        }

        .user-popup-links {
            padding: 8px;
        }

        .user-popup-links a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .user-popup-links a:hover {
            background: var(--bg-tertiary);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .user-popup-links a i {
            font-size: 1.1rem;
            width: 20px;
            color: var(--primary-color);
        }

        /* Enhanced Navigation */
        nav {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .nav-list {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            height: 100%;
            gap: 6px;
        }

        .nav-item {
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
        }

        .nav-link {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 22px;
            height: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            opacity: 0.9;
            border-radius: 10px;
            margin: 0 2px;
            font-size: 0.9rem;
            letter-spacing: 0.025em;
            cursor: pointer;
        }

        .nav-link:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .nav-link.active {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background: var(--text-light);
            border-radius: 50%;
        }

        .nav-link i {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Dropdown Menu Styles */
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 15px);
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-primary);
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            padding: 8px;
            min-width: 200px;
            display: none;
            z-index: 1100;
            border: 1px solid var(--border-light);
            opacity: 0;
            transform: translateX(-50%) translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .dropdown-item {
            display: block;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .dropdown-item:hover {
            background: var(--bg-tertiary);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .dropdown-item.active {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .dropdown-item:last-child {
            margin-bottom: 0;
        }

        /* Dropdown arrow indicator */
        .dropdown-toggle::after {
            content: '';
            display: inline-block;
            margin-left: 6px;
            vertical-align: middle;
            border-top: 4px solid currentColor;
            border-right: 4px solid transparent;
            border-bottom: 0;
            border-left: 4px solid transparent;
            transition: transform 0.3s ease;
        }

        .dropdown.show .dropdown-toggle::after {
            transform: rotate(180deg);
        }

        /* Enhanced Logout Button */
        .logout-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logout-btn {
            color: var(--text-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 10px;
            background: var(--danger-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 0.9rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .logout-btn i {
            font-size: 1rem;
        }

        /* Logout Confirmation Popup */
        .logout-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .logout-overlay.show {
            display: flex;
        }

        .logout-popup {
            background: var(--bg-primary);
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            padding: 0;
            width: 420px;
            max-width: 90vw;
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        .logout-popup-header {
            padding: 24px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logout-icon {
            width: 48px;
            height: 48px;
            background: var(--danger-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
        }

        .logout-popup-content {
            padding: 24px;
        }

        .logout-popup h3 {
            color: var(--text-primary);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .logout-popup p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .logout-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-light);
        }

        .btn-cancel:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .btn-confirm {
            background: var(--danger-color);
            color: white;
        }

        .btn-confirm:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* MAIN CONTENT CONTAINER - CRITICAL FIX */
        #content {
            margin-top: 70px;
            min-height: calc(100vh - 70px);
            position: relative;
            z-index: 1;
            background: var(--bg-secondary);
        }

        .container {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
            background: transparent;
        }

        /* Dashboard Content */
        .main-content {
            padding: 2rem;
        }

        .dashboard-container {
            background: var(--bg-primary);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            border: 1px solid var(--border-light);
        }

        .dashboard-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card h3 {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-card p {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .church-preview {
            width: 100%;
            max-width: 600px;
            height: 400px;
            margin: 2rem auto;
            background: var(--bg-tertiary);
            border: 2px dashed var(--border-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-bar {
                padding: 0 1rem;
                height: 60px;
            }

            #content {
                margin-top: 60px;
                min-height: calc(100vh - 60px);
            }

            .nav-list {
                display: none;
            }

            .left-section {
                min-width: auto;
            }

            .main-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Prevent any child elements from affecting header */
        #content > * {
            position: relative;
            z-index: 1;
        }

        /* Ensure data table containers don't interfere with header */
        .data-container,
        .data-table-container,
        .main-wrapper {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <!-- Combined Top Bar and Navigation - FIXED HEADER -->
    <header class="top-bar">
        <div class="left-section">
            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="username"><?php echo $_SESSION['username']; ?></div>
                    <div class="user-role">Admin Utama</div>
                </div>
            </div>
        </div>

        <nav>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="index_admin_utama.php" class="nav-link <?php echo empty($halaman) ? 'active' : ''; ?>">
                        <i class="fas fa-dashboard"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index_admin_utama.php?page_admin_utama=data_akun/data_akun" 
                       class="nav-link <?php echo ($halaman == 'data_akun/data_akun') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Data Akun
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (strpos($halaman, 'paroki/data_inventaris_paroki') !== false) ? 'active' : ''; ?>" href="#" id="inventarisDropdown">
                        <i class="fas fa-boxes"></i> Data Inventaris
                    </a>
                    <div class="dropdown-menu" id="inventarisDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/paroki/data_inventaris_paroki') ? 'active' : ''; ?>" 
                           href="index_admin_utama.php?page_admin_utama=data_inventaris/paroki/data_inventaris_paroki">
                           Paroki
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/fidelis/data_inventaris_fidelis') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/fidelis/data_inventaris_fidelis">
                        Stasi St. Fidelis
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/yohanes/data_inventaris_yohanes') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/yohanes/data_inventaris_yohanes">
                        Stasi St. Yohanes Penginjil 
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/agustinus/data_inventaris_agustinus') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/agustinus/data_inventaris_agustinus">
                        Stasi St. Agustinus 
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/benediktus/data_inventaris_benediktus') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/benediktus/data_inventaris_benediktus">
                        Stasi St. Benediktus 
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/paulus_inti/data_inventaris_paulus_inti') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/paulus_inti/data_inventaris_paulus_inti">
                        Stasi St. Paulus Inti
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/st_fransiskus_asisi') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/fransiskus/data_inventaris_fransiskus">
                        Stasi St. Fransiskus Asisi 
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/st_paulus_empang') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/st_paulus_empang">
                        Stasi St. Paulus Empang
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/sta_maria_karmel') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/sta_maria_karmel">
                        Stasi Sta. Maria Bunda Karmel 
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/sta_elisabet') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/sta_elisabet">
                        Stasi Sta. Elisabet
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/st_petrus') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/st_petrus">
                        Stasi St. Petrus 
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/st_stefanus') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/st_stefanus">
                        Stasi St. Stefanus
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/st_mikael') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/st_mikael">
                        Stasi St. Mikael
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_inventaris/st_paulus_merambai') ? 'active' : ''; ?>" 
                        href="index_admin_utama.php?page_admin_utama=data_inventaris/st_paulus_merambai">
                        Stasi St. Paulus Rasul
                        </a>

                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (strpos($halaman, 'data_transaksi_v') !== false) ? 'active' : ''; ?>" href="#" id="transaksiDropdown">
                        <i class="fas fa-exchange-alt"></i> Data Transaksi
                    </a>
                    <div class="dropdown-menu" id="transaksiDropdownMenu">
                        <a class="dropdown-item <?php echo ($halaman == 'data_transaksi_v/data_transaksi_v') ? 'active' : ''; ?>" 
                           href="index_admin_utama.php?page_admin_utama=data_transaksi_v/data_transaksi_v">
                           Peminjaman
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_transaksi_v/data_pengembalian') ? 'active' : ''; ?>" 
                           href="index_admin_utama.php?page_admin_utama=data_transaksi_v/data_pengembalian">
                           Pengembalian
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_transaksi_v/data_kerusakan') ? 'active' : ''; ?>" 
                           href="index_admin_utama.php?page_admin_utama=data_transaksi_v/data_kerusakan">
                           Kerusakan
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_transaksi_v/data_perbaikan') ? 'active' : ''; ?>" 
                           href="index_admin_utama.php?page_admin_utama=data_transaksi_v/data_perbaikan">
                           Perbaikan
                        </a>
                        <a class="dropdown-item <?php echo ($halaman == 'data_transaksi_v/data_mutasi') ? 'active' : ''; ?>" 
                           href="index_admin_utama.php?page_admin_utama=data_transaksi_v/data_mutasi">
                           Mutasi
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <div class="logout-section">
            <button class="logout-btn" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </div>

        <div class="user-popup" id="userPopup">
            <div class="user-popup-header">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="username"><?php echo $_SESSION['username']; ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <div class="user-popup-links">
                <a href="index_admin_utama.php?page_admin_utama=profil/edit_profil">
                    <i class="fas fa-edit"></i> Edit Profil
                </a>
            </div>
        </div>
    </header>

    <!-- Logout Confirmation Popup -->
    <div class="logout-overlay" id="logoutOverlay">
        <div class="logout-popup">
            <div class="logout-popup-header">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div>
                    <h3>Konfirmasi Logout</h3>
                </div>
            </div>
            <div class="logout-popup-content">
                <p>Apakah Anda yakin ingin keluar dari sistem? Semua sesi yang aktif akan dihentikan dan Anda akan diarahkan ke halaman login.</p>
                <div class="logout-actions">
                    <button class="btn btn-cancel" id="cancelLogout">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <a href="logout.php" class="btn btn-confirm">
                        <i class="fas fa-check"></i> Ya, Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div id="content">
        <div class="container">
            <?php
            if ($halaman == "") {
                include "page_admin_utama/dashboard.php";
            } else if (file_exists("page_admin_utama/$halaman.php")) {
                include "page_admin_utama/$halaman.php";
            } else {
                include "page_admin_utama/404.php";
            }
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userProfileBtn = document.getElementById('userProfileBtn');
            const userPopup = document.getElementById('userPopup');
            const logoutBtn = document.getElementById('logoutBtn');
            const logoutOverlay = document.getElementById('logoutOverlay');
            const cancelLogout = document.getElementById('cancelLogout');
            
            // Get all dropdown elements
            const dropdowns = document.querySelectorAll('.dropdown');
            const inventarisDropdown = document.getElementById('inventarisDropdown');
            const inventarisDropdownMenu = document.getElementById('inventarisDropdownMenu');
            const transaksiDropdown = document.getElementById('transaksiDropdown');
            const transaksiDropdownMenu = document.getElementById('transaksiDropdownMenu');

            // User profile popup toggle
            userProfileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userPopup.classList.toggle('show');
                // Close all dropdowns when user popup opens
                closeAllDropdowns();
            });

            // Function to close all dropdowns
            function closeAllDropdowns() {
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                    const menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) menu.classList.remove('show');
                });
            }

            // Inventaris dropdown toggle
            inventarisDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                transaksiDropdown.parentElement.classList.remove('show');
                transaksiDropdownMenu.classList.remove('show');
                userPopup.classList.remove('show');
                
                // Toggle current dropdown
                inventarisDropdown.parentElement.classList.toggle('show');
                inventarisDropdownMenu.classList.toggle('show');
            });

            // Transaksi dropdown toggle
            transaksiDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                inventarisDropdown.parentElement.classList.remove('show');
                inventarisDropdownMenu.classList.remove('show');
                userPopup.classList.remove('show');
                
                // Toggle current dropdown
                transaksiDropdown.parentElement.classList.toggle('show');
                transaksiDropdownMenu.classList.toggle('show');
            });

            // Close all dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                // Close user popup
                if (!userPopup.contains(e.target) && !userProfileBtn.contains(e.target)) {
                    userPopup.classList.remove('show');
                }
                
                // Close all dropdowns if click is outside
                let clickedInsideDropdown = false;
                dropdowns.forEach(dropdown => {
                    if (dropdown.contains(e.target)) {
                        clickedInsideDropdown = true;
                    }
                });
                if (!clickedInsideDropdown) {
                    closeAllDropdowns();
                }
            });

            // Logout overlay toggle
            logoutBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                logoutOverlay.classList.toggle('show');
            });

            // Close logout overlay
            cancelLogout.addEventListener('click', function(e) {
                e.stopPropagation();
                logoutOverlay.classList.remove('show');
            });
        });
    </script>
</body>
</html>