<?php
require_once("config/koneksi.php");

// Count total inventaris
$query_total = "SELECT COUNT(*) as total FROM inventaris";
$result_total = mysqli_query($koneksi, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_inventaris = $row_total['total'];

// Count active peminjaman
$query_active = "SELECT COUNT(*) as active FROM peminjaman WHERE status = 'dipinjam'";
$result_active = mysqli_query($koneksi, $query_active);
$row_active = mysqli_fetch_assoc($result_active);
$active_peminjaman = $row_active['active'];

// Count damaged inventaris from table kerusakan
$query_rusak = "SELECT COUNT(*) as rusak FROM kerusakan";
$result_rusak = mysqli_query($koneksi, $query_rusak);
$row_rusak = mysqli_fetch_assoc($result_rusak);
$inventaris_rusak = $row_rusak['rusak'];
?>

<style>
.dashboard-container {
    padding: 2rem;
    background: #f0f2f5;
    font-family: 'Poppins', sans-serif;
}

.dashboard-welcome {
    font-size: 26px;
    font-weight: 700;
    padding: 1.5rem 2rem;
    background:  #3498db;
    color: white;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.dashboard-content {
    display: flex;
    gap: 2rem;
    margin-top: 2rem;
}

.stats-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    width: 350px;
}

.stat-card {
    background: white;
    padding: 1.8rem;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    text-align: left;
    border-left: 6px solid #3498db;
    transition: all 0.3s ease;
    cursor: default;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-card h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    margin-top: 12px;
    color:  #3498db;
}

.stat-icon {
    font-size: 32px;
    float: right;
    color: #00b894;
    opacity: 0.7;
}

.church-image-container {
    flex: 1;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #eee;
}

.church-image {
    max-width: 100%;
    max-height: 100%;
    border-radius: 8px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.church-image:hover {
    transform: scale(1.02);
}
</style>

<div class="dashboard-container">
    <div class="dashboard-welcome">
        Selamat Datang di Sistem Manajemen Inventaris Gereja
    </div>

    <div class="dashboard-content">
        <div class="stats-container">
            <div class="stat-card">
                <h3>Jumlah Inventaris <span class="stat-icon">📦</span></h3>
                <div class="stat-value"><?= $total_inventaris; ?></div>
            </div>
            <div class="stat-card">
                <h3>Peminjaman Aktif <span class="stat-icon">🕒</span></h3>
                <div class="stat-value"><?= $active_peminjaman; ?></div>
            </div>
            <div class="stat-card">
                <h3>Inventaris Rusak <span class="stat-icon">⚠️</span></h3>
                <div class="stat-value"><?= $inventaris_rusak; ?></div>
            </div>
        </div>

        <div class="church-image-container">
            <img src="img/gereja.jpg" alt="Gambar Gereja" class="church-image">
        </div>
    </div>
</div>
