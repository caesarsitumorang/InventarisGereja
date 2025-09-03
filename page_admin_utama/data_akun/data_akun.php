<?php
require_once("config/koneksi.php");

if(isset($_POST['ajax'])) {
    $limit = 10; 
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;

    $search = isset($_POST['search']) ? mysqli_real_escape_string($koneksi, $_POST['search']) : '';
    $where = empty($search) ? '' : "WHERE nama LIKE '%$search%'";

    // Total data
    $total_records_query = "SELECT COUNT(*) as count FROM pengguna $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    // Data akun
    $query = "SELECT * FROM pengguna $where ORDER BY id_akun ASC LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start();
    ?>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Photo</th>
                <th width="15%">Nama</th>
                <th width="10%">Username</th>
                <th width="10%">Peran</th>
                <th width="15%">Alamat</th>
                <th width="10%">No HP</th>
                <th width="15%">Email</th>
                <th width="10%">Aksi</th>
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
                                 alt="Photo" 
                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                        <?php } else { ?>
                            <img src="assets/img/default-user.png" 
                                 alt="Default Photo" 
                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
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
                            <button onclick="editData(<?= $row['id_akun']; ?>)" class="btn-edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $row['id_akun']; ?>)" class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <?php if(mysqli_num_rows($result) == 0) { ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="pagination">
        <a href="javascript:void(0);" onclick="loadData(1)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>First</a>
        <a href="javascript:void(0);" onclick="loadData(<?= max(1, $page - 1); ?>)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>&laquo;</a>
        
        <?php for($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) { ?>
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

<!-- Main Content -->
<div class="data-container">
    <div class="data-header">
        <h2 class="header-title">Data Akun</h2>
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari berdasarkan nama...">
            <button type="button" class="search-button">
                <i class="fas fa-search"></i> Cari
            </button>
        </div>
    </div>
    <div id="dataTableContainer" class="data-table-container">
        <!-- Data will be loaded here -->
    </div>
</div>

<style>
.data-container {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.data-header {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title {
    font-size: 1.25rem;
    color: #2c3e50;
    margin: 0;
}

.search-container {
    display: flex;
    gap: 0.5rem;
}

.search-input {
    padding: 0.5rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    width: 300px;
    font-size: 0.875rem;
}

.search-button {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.3s;
}

.search-button:hover {
    background: #2980b9;
}

.data-table-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    background: #3498db;
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 500;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.text-center {
    text-align: center;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-edit, .btn-delete {
    padding: 0.4rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    color: white;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.3s;
}

.btn-edit {
    background: #2ecc71;
}

.btn-delete {
    background: #e74c3c;
}

.btn-edit:hover, .btn-delete:hover {
    opacity: 0.8;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.pagination a {
    padding: 0.5rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-decoration: none;
    color: #3498db;
    background: white;
    transition: all 0.3s;
}

.pagination a:hover:not(.disabled) {
    background: #e9ecef;
    color: #2980b9;
}

.pagination .active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination .disabled {
    color: #adb5bd;
    cursor: not-allowed;
    pointer-events: none;
}
</style>

<script>
let currentPage = 1;
let searchTimeout;

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

function editData(id) {
    window.location.href = `index_admin_utama.php?page_admin_utama=data_akun/edit_akun&id=${id}`;
}

function confirmDelete(id) {
    if (confirm("Apakah Anda yakin ingin menghapus akun ini?")) {
        fetch('index_admin_utama.php?page_admin_utama=data_akun/hapus_akun', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Data berhasil dihapus');
                loadData(currentPage);
            } else {
                alert('Gagal menghapus data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        });
    }
}

// Search with debounce
document.getElementById('searchInput').addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
});

// Initial load
loadData(1);
</script>