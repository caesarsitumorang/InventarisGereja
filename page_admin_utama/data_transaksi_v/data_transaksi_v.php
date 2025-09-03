<?php
require_once("config/koneksi.php");

if(isset($_POST['ajax'])) {
    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;

    $search = isset($_POST['search']) ? mysqli_real_escape_string($koneksi, $_POST['search']) : '';
    $filter = isset($_POST['filter']) ? mysqli_real_escape_string($koneksi, $_POST['filter']) : '';
    
    $where = '';
    if (!empty($search)) {
        $where .= "WHERE nama_barang LIKE '%$search%'";
    }
    if (!empty($filter)) {
        $where .= empty($where) ? "WHERE status = '$filter'" : " AND status = '$filter'";
    }

    // Get total records
    $total_records_query = "SELECT COUNT(*) as count FROM peminjaman $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    // Get records
    $query = "SELECT * FROM peminjaman $where ORDER BY id_pinjam DESC LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start();
    ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>No Peminjaman</th>
                <th>Tanggal</th>
                <th>Lokasi Simpan</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Lokasi Pinjam</th>
                <th>Nama Peminjam</th>
                <th>Keterangan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = $start + 1;
            while ($row = mysqli_fetch_assoc($result)) { 
                $status_class = strtolower($row['status']) == 'sudah kembali' ? 'status-returned' : 'status-pending';
            ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['no_peminjaman']); ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                    <td><?= htmlspecialchars($row['lokasi_simpan']); ?></td>
                    <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?= htmlspecialchars($row['jumlah_pinjam']); ?></td>
                    <td><?= htmlspecialchars($row['satuan']); ?></td>
                    <td><?= htmlspecialchars($row['lokasi_pinjam']); ?></td>
                    <td><?= htmlspecialchars($row['nama_peminjam']); ?></td>
                    <td><?= htmlspecialchars($row['keterangan']); ?></td>
                    <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']); ?></span></td>

                </tr>
            <?php } ?>
            <?php if(mysqli_num_rows($result) == 0) { ?>
                <tr>
                    <td colspan="13" class="text-center">Tidak ada data</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="pagination">
        <a href="javascript:void(0);" onclick="loadData(1)">First</a>
        <a href="javascript:void(0);" onclick="loadData(<?= max(1, $page - 1); ?>)">&lt;&lt;</a>
        
        <?php for($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) { ?>
            <a href="javascript:void(0);" onclick="loadData(<?= $i; ?>)" 
               class="<?= ($i == $page ? 'active' : '') ?>"><?= $i; ?></a>
        <?php } ?>
        
        <a href="javascript:void(0);" onclick="loadData(<?= min($page + 1, $total_pages); ?>)">&gt;&gt;</a>
        <a href="javascript:void(0);" onclick="loadData(<?= $total_pages; ?>)">Last</a>
    </div>
    <?php
    echo ob_get_clean();
    exit;
}
?>

<div class="data-container">
    <div class="data-header">
        <div class="header-left">
            <button class="btn-add" onclick="tambahTransaksi()">
                <i class="fas fa-plus"></i> Transaksi
            </button>
            <select id="filterStatus" class="select-filter" onchange="loadData(1)">
                <option value="">Semua Status</option>
                <option value="sudah kembali">Sudah Kembali</option>
                <option value="belum kembali">Belum Kembali</option>
            </select>
        </div>
        <div class="header-right">
            <button class="btn-download" onclick="downloadData()">
                <i class="fas fa-download"></i> Download
            </button>
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari barang...">
                <button class="search-button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
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

.header-left, .header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-add {
    background: #2ecc71;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.select-filter {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    min-width: 150px;
}

.btn-download {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-container {
    display: flex;
    gap: 0.5rem;
}

.search-input {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    width: 250px;
}

.search-button {
    background: #3498db;
    color: white;
    border: none;
    width: 34px;
    height: 34px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Existing table styles */
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
}

.data-table th {
    background: #3498db;
    padding: 0.75rem;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
    white-space: nowrap;
}

.data-table tr:hover {
    background: #f8f9fa;
}
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.status-returned {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-edit, .btn-delete {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
}

.btn-edit { background: #28a745; }
.btn-delete { background: #dc3545; }

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
}

.pagination a.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.text-center { text-align: center; }
</style>

<script>
let currentPage = 1;
let searchTimeout;

function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const filter = document.getElementById('filterStatus').value;
    
    fetch('index_admin_utama.php?page_admin_utama=data_transaksi_v/data_transaksi_v', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=true&page=${page}&search=${encodeURIComponent(search)}&filter=${encodeURIComponent(filter)}`
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

function tambahTransaksi() {
    window.location.href = 'index_admin_utama.php?page_admin_utama=data_transaksi_v/tambah_transaksi';
}

function editData(id) {
    window.location.href = `index_admin_utama.php?page_admin_utama=data_transaksi_v/edit_transaksi&id=${id}`;
}

function hapusData(id) {
    if(confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        fetch('index_admin_utama.php?page_admin_utama=data_transaksi_v/hapus_transaksi', {
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

function downloadData() {
    window.location.href = 'index_admin_utama.php?page_admin_utama=data_transaksi_v/cetak_pdf';
}

// Search with debounce
document.getElementById('searchInput').addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
});

// Initial load
loadData(1);
</script>