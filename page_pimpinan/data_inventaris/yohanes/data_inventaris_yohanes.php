<?php
require_once("config/koneksi.php");

$kategori_query = "SELECT kategori, SUM(jumlah) AS total_jumlah 
                   FROM inventaris 
                   GROUP BY kategori";
$kategori_result = mysqli_query($koneksi, $kategori_query);
$kategori_data = [];

while($row = mysqli_fetch_assoc($kategori_result)) {
    $kategori_data[$row['kategori']] = $row['total_jumlah'];
}

if(isset($_POST['ajax'])) {
    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;

    $search = isset($_POST['search']) ? mysqli_real_escape_string($koneksi, $_POST['search']) : '';
    $lokasi = "Stasi St. Yohanes Penginjil (Minas Jaya)";

    $where = "WHERE lokasi_simpan = '$lokasi'";
    if(!empty($search)) {
        $where .= " AND (nama_barang LIKE '%$search%' OR kode_barang LIKE '%$search%')";
    }

    $total_records_query = "SELECT COUNT(*) as count FROM inventaris $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    $query = "SELECT * FROM inventaris $where ORDER BY kode_barang ASC LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start();
    ?>
    <div class="table-scroll">
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
                <th>Nama Akun</th>
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
                    <td><?= htmlspecialchars($row['nama_akun']); ?></td>
                    <td>
                        <div class="action-buttons">
                           
                            <button type="button" 
                                    class="btn-detail" 
                                    onclick='showDetail(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>
                                Lihat Detail
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
    </div>

    <div class="pagination">
        <a href="javascript:void(0);" onclick="loadData(1)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>First</a>
        <a href="javascript:void(0);" onclick="loadData(<?= max(1, $page - 1); ?>)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>&laquo;</a>
        <?php for($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++) { ?>
            <a href="javascript:void(0);" onclick="loadData(<?= $i; ?>)" <?= ($i == $page ? 'class="active"' : '') ?>><?= $i; ?></a>
        <?php } ?>
        <a href="javascript:void(0);" onclick="loadData(<?= min($page + 1, $total_pages); ?>)" <?= ($page == $total_pages ? 'class="disabled"' : '') ?>>&raquo;</a>
        <a href="javascript:void(0);" onclick="loadData(<?= $total_pages; ?>)" <?= ($page == $total_pages ? 'class="disabled"' : '') ?>>Last</a>
    </div>
   <div class="category-list">
    <?php 
    require_once("config/koneksi.php");

    // Ambil semua kategori dari tabel inventaris dan jumlahnya
    $query = "SELECT kategori, SUM(jumlah) AS total_jumlah 
              FROM inventaris 
              GROUP BY kategori";
    $result = mysqli_query($koneksi, $query);

    $kategori_data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $kategori_data[$row['kategori']] = $row['total_jumlah'];
    }

    // Tampilkan kategori
    foreach($kategori_data as $kategori => $total) { 
    ?>
        <div class="category-item">
            <span class="kategori-name"><?= htmlspecialchars($kategori); ?></span>
            <span class="kategori-total">: <?= number_format($total); ?></span>
        </div>
    <?php } ?>
</div>

    <?php
    
    echo ob_get_clean();
    exit;
    
}
?>

<div class="data-container">
    <div class="data-header">
        <div class="header-actions">
            <button class="btn-download" onclick="downloadPDF()">
                <i class="fas fa-download"></i> Download PDF
            </button>
        </div>
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari berdasarkan nama/kode barang...">
        </div>
    </div>

    <div id="dataTableContainer" class="data-table-container">
        <!-- Data will be loaded here -->
    </div>
</div>



<div id="detailModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Detail Inventaris</h2>
    <table class="detail-table">
      <tbody id="detailBody"></tbody>
    </table>
  </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
body, button, input, table, th, td, a, .btn-add, .btn-edit, .btn-delete, .category-list, .category-item,.cate {
    font-family: 'Poppins', sans-serif !important;
}
body { font-family: 'Poppins', sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
.data-container { padding: 1.5rem; min-height: 100vh; }

.data-header { background: #fff; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.05); display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem; }
.header-actions { display:flex; gap:0.75rem; flex-wrap:wrap; }
.search-container { display:flex; gap:0.5rem; flex-wrap:wrap; }
.search-input { padding:0.6rem 1rem; border:1px solid #ccc; border-radius:6px; width:280px; transition:0.3s; }
.search-input:focus { border-color:#3498db; outline:none; box-shadow:0 0 4px rgba(52,152,219,0.4); }

.search-button, .btn-add, .btn-download { padding:0.6rem 1.2rem; border:none; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:0.4rem; color:white; font-weight:500; transition:all 0.3s; font-size:0.9rem; }
.search-button { background:#3498db; } .btn-add { background:#3498db; } .btn-download { background:#3498db; }
.search-button:hover { background:#2980b9; } .btn-add:hover { background:#27ae60; } .btn-download:hover { background:#8e44ad; }

.table-scroll { overflow-x:auto; } /* <-- Perbaikan: scroll horizontal di tabel */
.data-table-container { background:white; padding:1rem; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.05); }
.data-table { width:100%; border-collapse:collapse; font-size:0.9rem; min-width:1200px; }
.data-table th { background:#3498db; color:white; padding:0.75rem; text-align:left; border-bottom:2px solid #ddd; }
.data-table td { padding:0.75rem; border-bottom:1px solid #eee; color:#333; }
.data-table tr:hover { background:#f8faff; }
.action-buttons {
    display: flex;
    gap: 0.4rem;
    flex-wrap: nowrap;      
    white-space: nowrap;    
}

.btn-edit, .btn-delete, .btn-detail {
    padding: 0.4rem 0.8rem; 
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    transition: 0.3s;
    display: inline-block;
    text-align: center;
}
.btn-edit { background:#2ecc71; } .btn-delete { background:#e74c3c; } .btn-detail { background:#3498db; }
.btn-edit:hover { background:#27ae60; } .btn-delete:hover { background:#c0392b; } .btn-detail:hover { background:#2980b9; }

/* Pagination */
.pagination { display:flex; justify-content:center; gap:0.4rem; margin-top:1.2rem; flex-wrap:wrap; }
.pagination a { padding:0.5rem 0.9rem; border:1px solid #ccc; border-radius:6px; text-decoration:none; color:#3498db; background:white; transition:all 0.3s; font-size:0.85rem; }
.pagination a:hover:not(.disabled) { background:#ecf0f1; border-color:#3498db; }
.pagination .active { background:#3498db; color:white; border-color:#3498db; }
.pagination .disabled { color:#bbb; background:#f8f9fa; cursor:not-allowed; pointer-events:none; }

/* Modal */
.modal { display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:flex-start; justify-content:center; padding:2rem 1rem; }
.modal-content { background:#fff; border-radius:12px; padding:2rem; max-width:750px; width:100%; position:relative; margin-top:40px; box-shadow:0 6px 18px rgba(0,0,0,0.25); animation:fadeIn .3s ease; }
.modal-content h2 { margin:0 0 1rem 0; font-size:1.3rem; color:#3498db; text-align:center; }
.close { position:absolute; right:16px; top:12px; font-size:1.6rem; font-weight:bold; color:#888; cursor:pointer; transition:0.3s; }
.close:hover { color:#333; }
.detail-table { width:100%; border-collapse:collapse; margin-bottom:1rem; }
.detail-table th { text-align:left; background:#f8f9fa; padding:8px 12px; width:35%; border-bottom:1px solid #eee; font-weight:600; font-size:0.9rem; }
.detail-table td { padding:8px 12px; border-bottom:1px solid #eee; font-size:0.9rem; }
.category-list {
    margin-top: 20px;
    background-color:#3498db;
    padding: 15px;
    font-family: Arial, sans-serif;
    width: 250px; /* bisa disesuaikan */
}

.category-item {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
}
.kategori-name {
    color: white;
    flex: 1;
}
.kategori-total {
    text-align: right;
    color: white;
}
@keyframes fadeIn { from {opacity:0; transform:translateY(-10px);} to {opacity:1; transform:translateY(0);} }
</style>


<script>
let currentPage = 1;
let searchTimeout;

function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    
    fetch('index_pimpinan.php?page_pimpinan=data_inventaris/yohanes/data_inventaris_yohanes', {
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
function downloadPDF() {
    window.open('index_pimpinan.php?page_pimpinan=data_inventaris/yohanes/cetak_inventaris_yohanes', '_blank');
}

function showAddForm() {
    window.location.href = 'index_pimpinan.php?page_pimpinan=data_inventaris/yohanes/tambah_inventaris_yohanes';
}

// Search with debounce
document.getElementById('searchInput').addEventListener('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
});
function showDetail(data) {
  let html = `
    <tr><th>Kode Barang</th><td>${data.kode_barang}</td></tr>
    <tr><th>Nama Barang</th><td>${data.nama_barang}</td></tr>
    <tr><th>Kategori</th><td>${data.kategori}</td></tr>
    <tr><th>Lokasi</th><td>${data.lokasi_simpan}</td></tr>
    <tr><th>Jumlah</th><td>${data.jumlah}</td></tr>
    <tr><th>Total</th><td>${data.jumlah_total}</td></tr>
    <tr><th>Satuan</th><td>${data.satuan}</td></tr>
    <tr><th>Tgl Pengadaan</th><td>${data.tgl_pengadaan}</td></tr>
    <tr><th>Kondisi</th><td>${data.kondisi}</td></tr>
    <tr><th>Sumber</th><td>${data.sumber}</td></tr>
    <tr><th>Harga</th><td>Rp ${new Intl.NumberFormat('id-ID').format(data.harga)}</td></tr>
    <tr><th>Keterangan</th><td>${data.keterangan}</td></tr>
  `;
  document.getElementById("detailBody").innerHTML = html;
  document.getElementById("detailModal").style.display = "flex";
}

function closeModal() {
  document.getElementById("detailModal").style.display = "none";
}

// Klik luar modal untuk menutup
window.onclick = function(e) {
  let modal = document.getElementById("detailModal");
  if (e.target === modal) {
    closeModal();
  }
}


// Initial load
loadData(1);
</script>