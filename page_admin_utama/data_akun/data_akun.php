<?php
require_once("config/koneksi.php");

if (isset($_POST['ajax'])) {
    $limit = 4;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;

    $search = isset($_POST['search']) ? mysqli_real_escape_string($koneksi, $_POST['search']) : '';
    $where = empty($search) ? '' : "WHERE nama LIKE '%$search%'";

    // Hitung total data
    $total_records_query = "SELECT COUNT(*) as count FROM pengguna $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    // Data akun
    $query = "SELECT * FROM pengguna $where ORDER BY id_akun ASC LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start(); ?>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Photo</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Peran</th>
                <th>Alamat</th>
                <th>No HP</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = $start + 1;
            while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td>
                        <?php if (!empty($row['photo'])) { ?>
                            <img src="upload/<?= htmlspecialchars($row['photo']); ?>" 
                                alt="Photo" class="photo">
                        <?php } else { ?>
                            <img src="assets/img/default-user.png" 
                                alt="Default Photo" class="photo">
                        <?php } ?>
                    </td>
                    <td><?= htmlspecialchars($row['nama']); ?></td>
                    <td><?= htmlspecialchars($row['username']); ?></td>
                    <td><?= htmlspecialchars($row['peran']); ?></td>
                    <td><?= htmlspecialchars($row['alamat']); ?></td>
                    <td><?= htmlspecialchars($row['no_hp']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="index_admin_utama.php?page_admin_utama=data_akun/edit_akun&id=<?= $row['id_akun']; ?>" class="btn-edit">Edit</a>
                            <a href="index_admin_utama.php?page_admin_utama=data_akun/hapus_akun&id=<?= $row['id_akun']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Yakin ingin menghapus akun ini?')">Hapus</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <?php if (mysqli_num_rows($result) == 0) { ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="pagination">
        <a href="javascript:void(0);" onclick="loadData(1)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>First</a>
        <a href="javascript:void(0);" onclick="loadData(<?= max(1, $page - 1); ?>)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>&laquo;</a>
        
        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) { ?>
            <a href="javascript:void(0);" onclick="loadData(<?= $i; ?>)" <?= ($i == $page ? 'class="active"' : '') ?>><?= $i; ?></a>
        <?php } ?>
        
        <a href="javascript:void(0);" onclick="loadData(<?= min($page + 1, $total_pages); ?>)" <?= ($page == $total_pages ? 'class="disabled"' : '') ?>>&raquo;</a>
        <a href="javascript:void(0);" onclick="loadData(<?= $total_pages; ?>)" <?= ($page == $total_pages ? 'class="disabled"' : '') ?>>Last</a>
    </div>
    
    <?php
    echo ob_get_clean();
    exit;
}
?>

<div class="data-container">
    <div class="data-header">
        <div class="header-left">
            <button class="btn-add" onclick="tambahAkun()">
                <i class="fas fa-plus"></i> Tambah Data Akun
            </button>
        </div>
        <div class="header-right">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari berdasarkan nama...">
                <!-- <button class="search-button">
                    <i class="fas fa-search"></i>
                </button> -->
            </div>
        </div>
    </div>

    <div id="dataTableContainer" class="data-table-container">
        <!-- Data loaded here -->
    </div>
</div>

<style>
/* Container */
.data-container {
    margin: 0;
    padding: 16px;
    background: #f4f6f9;
    min-height: calc(100vh - 60px);
}

/* Header */
.data-header {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Button Tambah */
.btn-add {
    background:  #1e40af;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn-add:hover {
    background: #3d006fff;
    transform: translateY(-1px);
}

/* Search */
.search-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-input {
    width: 280px;
    height: 38px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.search-input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.15);
}

.search-button {
    height: 38px;
    width: 38px;
    border: none;
    border-radius: 6px;
    background: #3498db;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.search-button:hover {
    background: #2980b9;
}

/* Table */
.data-table-container {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #ddd;
    font-size: 14px;
    color: #333;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    color: #555;
}

.data-table tr:hover {
    background: #f9f9f9;
}

/* Photo */
.photo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 6px;
}

.btn-edit, .btn-delete {
    padding: 6px 10px;
    font-size: 13px;
    border-radius: 6px;
    color: #fff;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-edit {
    background: #2ecc71;
}

.btn-delete {
    background: #e74c3c;
}

.btn-edit:hover {
    background: #27ae60;
}

.btn-delete:hover {
    background: #c0392b;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
}

.pagination a {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #3498db;
    font-size: 14px;
    background: #fff;
    transition: all 0.2s;
}

.pagination a:hover:not(.disabled) {
    background: #f0f8ff;
    border-color: #3498db;
}

.pagination .active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination .disabled {
    color: #ccc;
    cursor: not-allowed;
    pointer-events: none;
}

/* Responsive */
@media (max-width: 768px) {
    .data-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .search-input {
        width: 100%;
    }
}
</style>

<script>
let currentPage = 1;
let searchTimeout;

function tambahAkun() {
    window.location.href = 'index_admin_utama.php?page_admin_utama=data_akun/tambah_akun';
}

function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    
    fetch('index_admin_utama.php?page_admin_utama=data_akun/data_akun', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=true&page=${page}&search=${encodeURIComponent(search)}`
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('dataTableContainer').innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data');
    });
}

// Search debounce
document.getElementById('searchInput').addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
});

// Load awal
loadData(1);
</script>
