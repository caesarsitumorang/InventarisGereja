<?php
// file: pending_peminjaman.php (contoh nama file)
ob_start();

// Jangan tampilkan warning/notice ke browser — log ke file agar response tetap murni JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error_log.txt');

require_once("config/koneksi.php");

// Lokasi yang ditentukan (tidak bisa diubah)
$lokasi_tetap = 'Stasi St. Fidelis (Karo Simalem)';

// === Update Status (API JSON) ===
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Pastikan tidak ada output HTML/whitespace sisa
    while (ob_get_level()) { ob_end_clean(); }

    header('Content-Type: application/json; charset=utf-8');

    $id_pinjam = isset($_POST['id_pinjam']) ? (int) $_POST['id_pinjam'] : 0;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

    $response = ['success' => false, 'message' => 'Gagal memproses permintaan'];

    if ($id_pinjam > 0 && $new_status !== '') {
        $sql = "UPDATE peminjaman SET status = ? WHERE id_pinjam = ?";
        $stmt = @mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $id_pinjam);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = (strtolower($new_status) === 'ditolak')
                    ? 'Peminjaman berhasil ditolak.'
                    : 'Peminjaman berhasil disetujui.';
            } else {
                // Catat error ke log, kirim pesan generik ke client
                $err = mysqli_stmt_error($stmt);
                error_log("Update peminjaman gagal (id: $id_pinjam): $err");
                $response['message'] = 'Terjadi masalah pada database saat mengubah status.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $err = mysqli_error($koneksi);
            error_log("Prepare statement gagal: $err | SQL: $sql");
            $response['message'] = 'Terjadi masalah pada sistem (prepare statement).';
        }
    } else {
        $response['message'] = 'Data tidak lengkap.';
    }

    echo json_encode($response);
    exit; // WAJIB agar tidak lanjut ke HTML di bawah
}


// === Load Data (AJAX HTML fragment) ===
if (isset($_POST['ajax'])) {
    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';

    $conditions = [];
    $conditions[] = "lokasi_simpan = '" . mysqli_real_escape_string($koneksi, $lokasi_tetap) . "'";
    $conditions[] = "status = 'pending'";

    if (!empty($search)) {
        $search = mysqli_real_escape_string($koneksi, $search);
        $conditions[] = "(nama_barang LIKE '%$search%' OR kode_barang LIKE '%$search%' OR no_peminjaman LIKE '%$search%' OR nama_peminjam LIKE '%$search%')";
    }

    $where = "WHERE " . implode(" AND ", $conditions);

    // Hitung total data
    $total_records_query = "SELECT COUNT(*) as count FROM peminjaman $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ($limit > 0) ? ceil($total_records / $limit) : 1;

    // Ambil data
    $query = "SELECT id_pinjam, no_peminjaman, tanggal_pinjam, lokasi_simpan, 
                     kode_barang, nama_barang, jumlah_pinjam, satuan, 
                     lokasi_pinjam, nama_peminjam, keterangan, status, nama_akun
              FROM peminjaman $where 
              ORDER BY tanggal_pinjam DESC 
              LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start();
    ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No Peminjaman</th>
                    <th>Tanggal Pinjam</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Lokasi Pinjam</th>
                    <th>Nama Peminjam</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th>Nama Akun</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = $start + 1;
                while ($row = mysqli_fetch_assoc($result)) { 
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['no_peminjaman']); ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                        <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                        <td class="text-right"><?= htmlspecialchars($row['jumlah_pinjam']); ?></td>
                        <td><?= htmlspecialchars($row['satuan']); ?></td>
                        <td><?= htmlspecialchars($row['lokasi_pinjam']); ?></td>
                        <td><?= htmlspecialchars($row['nama_peminjam']); ?></td>
                        <td><?= htmlspecialchars($row['keterangan']); ?></td>
                        <td><span class="status-pending">Pending</span></td>
                        <td><?= htmlspecialchars($row['nama_akun'] ?? 'Tidak ada'); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-success btn-sm" onclick="updateStatus(<?= $row['id_pinjam']; ?>, 'Belum Dikembalikan')" title="Terima">
                                    <i class="fas fa-check"></i> Terima
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="updateStatus(<?= $row['id_pinjam']; ?>, 'ditolak')" title="Tolak">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (mysqli_num_rows($result) == 0) { ?>
                    <tr>
                        <td colspan="13" class="text-center">Tidak ada permintaan peminjaman</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_records > 0) { ?>
    <div class="pagination">
        <?php if ($page > 1) { ?>
            <a href="javascript:void(0);" onclick="loadData(1)">First</a>
            <a href="javascript:void(0);" onclick="loadData(<?= $page - 1; ?>)">&lt;&lt;</a>
        <?php } ?>
        
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        for ($i = $start_page; $i <= $end_page; $i++) { ?>
            <a href="javascript:void(0);" onclick="loadData(<?= $i; ?>)" <?= ($i == $page ? 'class="active"' : '') ?>><?= $i; ?></a>
        <?php } ?>
        
        <?php if ($page < $total_pages) { ?>
            <a href="javascript:void(0);" onclick="loadData(<?= $page + 1; ?>)">&gt;&gt;</a>
            <a href="javascript:void(0);" onclick="loadData(<?= $total_pages; ?>)">Last</a>
        <?php } ?>
        
        <span class="pagination-info">
            Halaman <?= $page; ?> dari <?= $total_pages; ?> (Total: <?= $total_records; ?> data)
        </span>
    </div>
    <?php } ?>

    <?php
    echo ob_get_clean();
    exit;
}
?>

<div class="container">
    <div class="page-header">
        <h2>Permintaan Peminjaman Pending - <?= htmlspecialchars($lokasi_tetap); ?></h2>
    </div>

    <div class="toolbar">
        <div class="left-tools">
             <button class="btn btn-primary" onclick="window.location.href='index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/data_peminjaman'">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
        </div>

        <div class="right-tools">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari no peminjaman, barang, atau peminjam...">
        </div>
    </div>

    <div id="dataTableContainer" class="table-container"></div>
</div>

<script>
let currentPage = 1;
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadData(1);
    
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        if (e.key === 'Enter' || this.value !== currentSearch) {
            currentSearch = this.value;
            loadData(1);
        }
    });
    
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value;
            loadData(1);
        }, 500);
    });
});

function loadData(page = 1) {
    currentPage = page;
    const searchValue = document.getElementById('searchInput').value;
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('page', page);
    formData.append('search', searchValue);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('dataTableContainer').innerHTML = data;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data');
    });
}

function updateStatus(idPinjam, newStatus) {
    const actionText = newStatus === 'ditolak' ? 'menolak' : 'menerima';
    if (!confirm(`Apakah Anda yakin ingin ${actionText} peminjaman ini?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id_pinjam', idPinjam);
    formData.append('new_status', newStatus);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // ambil sebagai teks dulu
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            // Kalau bukan JSON, tampilkan response di console untuk debugging
            console.error('Invalid JSON response from server:', text);
            alert('Terjadi kesalahan saat memproses permintaan. Periksa console (Network → response).');
            return;
        }

        if (data.success) {
            // pakai alert sederhana (sesuai permintaan: jangan modal lagi)
            alert(data.message);
            loadData(currentPage); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Terjadi kesalahan saat memproses permintaan');
    });
}
</script>


<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f5f7fa;
    color: #333;
    line-height: 1.6;
}

.container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.page-header h2 {
    color: #2c3e50;
    font-size: 28px;
    font-weight: 600;
}

.toolbar {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.left-tools {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.right-tools {
    display: flex;
    align-items: center;
    gap: 15px;
}

.info-badge {
    background: #e3f2fd;
    color: #1565c0;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #219a52;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.search-input {
    padding: 10px 15px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 14px;
    min-width: 300px;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-scroll {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1300px;
}

.data-table th {
    background: #3498db;
    color: white;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    border-bottom: none;
    white-space: nowrap;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #e1e8ed;
    font-size: 13px;
    vertical-align: middle;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.text-center {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 30px !important;
}

.text-right {
    text-align: right;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    padding: 20px 30px;
    background: white;
    flex-wrap: wrap;
}

.pagination a {
    padding: 8px 12px;
    margin: 0 2px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 40px;
    text-align: center;
}

.pagination a:hover {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination .active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination-info {
    margin-left: 15px;
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    margin: 10% auto;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.alert-modal {
    max-width: 400px;
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e1e8ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #3498db;
    color: white;
    border-radius: 16px 16px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: white;
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
    transition: all 0.2s ease;
}

.close:hover {
    opacity: 0.8;
}

.modal-body {
    padding: 25px;
    text-align: center;
    font-size: 16px;
    line-height: 1.6;
}

.modal-footer {
    padding: 0 25px 25px 25px;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .left-tools, .right-tools {
        justify-content: center;
    }
    
    .search-input {
        min-width: 250px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .pagination {
        flex-wrap: wrap;
        gap: 3px;
    }
    
    .pagination-info {
        width: 100%;
        text-align: center;
        margin: 10px 0 0 0;
    }
    
    .modal-content {
        margin: 20% auto;
        width: 95%;
    }
    
    .page-header, .toolbar, .modal-body {
        padding: 15px 20px;
    }
}
</style>
