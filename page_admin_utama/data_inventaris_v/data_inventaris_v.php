<?php
require_once("config/koneksi.php");

if(isset($_POST['ajax'])) {
    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;

    $search = isset($_POST['search']) ? mysqli_real_escape_string($koneksi, $_POST['search']) : '';
    $where = empty($search) ? '' : "WHERE nama_barang LIKE '%$search%' OR kode_barang LIKE '%$search%'";

    // Get total records
    $total_records_query = "SELECT COUNT(*) as count FROM inventaris $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    // Get records
    $query = "SELECT * FROM inventaris $where ORDER BY kode_barang ASC LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start();
    ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Jumlah</th>
                <th>Total</th>
                <th>Satuan</th>
                <th>Tgl Pengadaan</th>
                <th>Kondisi</th>
                <th>Sumber</th>
                <th>Harga</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = $start + 1;
            while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?= htmlspecialchars($row['kategori']); ?></td>
                    <td><?= htmlspecialchars($row['lokasi_simpan']); ?></td>
                    <td><?= htmlspecialchars($row['jumlah']); ?></td>
                    <td><?= htmlspecialchars($row['jumlah_total']); ?></td>
                    <td><?= htmlspecialchars($row['satuan']); ?></td>
                    <td><?= htmlspecialchars($row['tgl_pengadaan']); ?></td>
                    <td><?= htmlspecialchars($row['kondisi']); ?></td>
                    <td><?= htmlspecialchars($row['sumber']); ?></td>
                    <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td><?= htmlspecialchars($row['keterangan']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button onclick="editData(<?= $row['id']; ?>)" class="btn-edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $row['id']; ?>)" class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button onclick="showDetail(<?= $row['id']; ?>)" class="btn-detail">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <?php if(mysqli_num_rows($result) == 0) { ?>
                <tr>
                    <td colspan="14" class="text-center">Tidak ada data</td>
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

<div class="data-container">
    <div class="data-header">
        <div class="header-actions">
            <button class="btn-add" onclick="showAddForm()">
                <i class="fas fa-plus"></i> Tambah Data
            </button>
            <button class="btn-download" onclick="downloadPDF()">
                <i class="fas fa-download"></i> Download PDF
            </button>
        </div>
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari berdasarkan nama/kode barang...">
            <button class="search-button" onclick="loadData(1)">
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

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
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
}

.search-button, .btn-add, .btn-download {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    transition: opacity 0.3s;
    font-size: 0.9rem;
}

.search-button {
    background: #3498db;
}

.btn-add {
    background: #2ecc71;
}

.btn-download {
    background: #9b59b6;
}

.search-button:hover, .btn-add:hover, .btn-download:hover {
    opacity: 0.85;
}

.search-button {
    background: #3498db;
}

.btn-add {
    background: #2ecc71;
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
    padding: 0.75rem;
    text-align: left;
    white-space: nowrap;
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
    white-space: nowrap;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.btn-edit, .btn-delete, .btn-detail {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: opacity 0.3s;
}

.btn-edit { background: #2ecc71; }
.btn-delete { background: #e74c3c; }
.btn-detail { background: #3498db; }

.btn-edit:hover, .btn-delete:hover, .btn-detail:hover {
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

.text-center {
    text-align: center;
}
</style>

<script>
let currentPage = 1;
let searchTimeout;

function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    
    fetch('index_admin_utama.php?page_admin_utama=data_inventaris_v/data_inventaris_v', {
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

function showAddForm() {
    window.location.href = 'index_admin_utama.php?page_admin_utama=data_inventaris_v/tambah_inventaris';
}

function editData(id) {
    window.location.href = `index_admin_utama.php?page_admin_utama=data_inventaris_v/edit_inventaris&id=${id}`;
}

function confirmDelete(id) {
    if(confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        fetch('index_admin_utama.php?page_admin_utama=data_inventaris_v/hapus_inventaris', {
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

function showDetail(id) {
    window.location.href = `index_admin_utama.php?page_admin_utama=data_inventaris_v/detail_inventaris&id=${id}`;
}

// Search with debounce
document.getElementById('searchInput').addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
});

// Initial load
loadData(1);
</script>