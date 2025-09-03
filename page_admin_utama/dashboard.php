<?php
// Get counts from database
require_once("config/koneksi.php");

// Count total inventaris
$query_total = "SELECT COUNT(*) as total FROM inventaris";
$result_total = mysqli_query($koneksi, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_inventaris = $row_total['total'];

// Count active peminjaman
$query_active = "SELECT COUNT(*) as active FROM peminjaman WHERE status = 'active'";
$result_active = mysqli_query($koneksi, $query_active);
$row_active = mysqli_fetch_assoc($result_active);
$active_peminjaman = $row_active['active'];

// Count damaged inventaris
$query_rusak = "SELECT COUNT(*) as rusak FROM inventaris WHERE kondisi = 'rusak'";
$result_rusak = mysqli_query($koneksi, $query_rusak);
$row_rusak = mysqli_fetch_assoc($result_rusak);
$inventaris_rusak = $row_rusak['rusak'];
?>

<style>
    .dashboard-container {
        padding: 2rem;
        background: #f5f6fa;
    }

    .dashboard-welcome {
        font-size: 24px;
        color: var(--primary-color);
        padding: 1.5rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #eee;
    }

    .dashboard-content {
        display: flex;
        gap: 2rem;
        margin-top: 2rem;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #eee;
    }

    .stats-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        width: 300px;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: left;
        border: 1px solid #eee;
        transition: transform 0.2s ease;
    }

    .stat-card:hover {
        transform: translateX(5px);
    }

    .stat-card h3 {
        margin: 0;
        color: var(--primary-color);
        font-size: 16px;
        font-weight: 600;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: var(--accent-color);
        margin-top: 8px;
    }

    .church-image-container {
        flex: 1;
        height: 500px;
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #eee;
    }

    .church-image {
        max-width: 100%;
        max-height: 100%;
        border-radius: 4px;
        object-fit: contain;
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-welcome">
        Selamat Datang, di Sistem Manajemen Inventaris Gereja
    </div>

    <div class="dashboard-content">
    <div class="stats-container">
        <div class="stat-card">
            <h3>Jumlah Inventaris</h3>
            <div class="stat-value"><?php echo $total_inventaris; ?></div>
        </div>
        <div class="stat-card">
            <h3>Peminjaman Aktif</h3>
            <div class="stat-value"><?php echo $active_peminjaman; ?></div>
        </div>
        <div class="stat-card">
            <h3>Inventaris Rusak</h3>
            <div class="stat-value"><?php echo $inventaris_rusak; ?></div>
        </div>
    </div>

    <div class="church-image-container">
        <img src="../upload/gereja.jpg" alt="Gambar Gereja" class="church-image">
    </div>
</div></div>