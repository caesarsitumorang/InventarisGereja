<?php
require_once("config/koneksi.php");

// Lokasi default halaman pertama
$lokasi_default = 'Stasi St. Fidelis (Karo Simalem)';

if(isset($_POST['ajax'])) {
    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $lokasi_filter = isset($_POST['lokasi_filter']) ? trim($_POST['lokasi_filter']) : $lokasi_default;

    $conditions = [];

    // TAMBAHAN: Exclude status pending
    $conditions[] = "status != 'pending'";
    
    if (!empty($lokasi_filter)) {
        $lokasi_escaped = mysqli_real_escape_string($koneksi, $lokasi_filter);
        $conditions[] = "lokasi_simpan = '$lokasi_escaped'";
    }

    if (!empty($search)) {
        $search = mysqli_real_escape_string($koneksi, $search);
        $conditions[] = "(nama_barang LIKE '%$search%' OR kode_barang LIKE '%$search%' OR no_peminjaman LIKE '%$search%' OR nama_peminjam LIKE '%$search%')";
    }

    // Karena conditions pasti tidak kosong (minimal ada kondisi status != 'pending')
    $where = "WHERE " . implode(" AND ", $conditions);

    // Hitung total data
    $total_records_query = "SELECT COUNT(*) as count FROM peminjaman $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    // Ambil data
    $query = "SELECT id_pinjam, no_peminjaman, tanggal_pinjam, lokasi_simpan, 
                     kode_barang, nama_barang, jumlah_pinjam, satuan, 
                     lokasi_pinjam, nama_peminjam, keterangan, status, nama_akun
              FROM peminjaman $where 
              ORDER BY id_pinjam ASC 
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
                    <th>Lokasi Simpan</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Lokasi Pinjam</th>
                    <th>Nama Peminjam</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th>Nama Akun</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = $start + 1;
                while ($row = mysqli_fetch_assoc($result)) { 
                    $status_class = '';
                    $status_text = $row['status'];
                    switch(strtolower($row['status'])) {
                        case 'sudah kembali':
                            $status_class = 'status-kembali';
                            break;
                        case 'belum kembali':
                            $status_class = 'status-pinjam';
                            break;
                        default:
                            $status_class = 'status-default';
                    }
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['no_peminjaman']); ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                        <td><?= htmlspecialchars($row['lokasi_simpan']); ?></td>
                        <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                        <td class="text-right"><?= htmlspecialchars($row['jumlah_pinjam']); ?></td>
                        <td><?= htmlspecialchars($row['satuan']); ?></td>
                        <td><?= htmlspecialchars($row['lokasi_pinjam']); ?></td>
                        <td><?= htmlspecialchars($row['nama_peminjam']); ?></td>
                        <td><?= htmlspecialchars($row['keterangan']); ?></td>
                        <td><span class="<?= $status_class ?>"><?= htmlspecialchars($status_text); ?></span></td>
                        <td><?= htmlspecialchars($row['nama_akun'] ?? 'Tidak ada'); ?></td>
                    </tr>
                <?php } ?>
                <?php if (mysqli_num_rows($result) == 0) { ?>
                    <tr>
                        <td colspan="13" class="text-center">Tidak ada data</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_records > 0) { ?>
    <div class="pagination">
        <a href="javascript:void(0);" onclick="loadData(1)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>First</a>
        <a href="javascript:void(0);" onclick="loadData(<?= max(1, $page - 1); ?>)" <?= ($page == 1 ? 'class="disabled"' : '') ?>>&lt;&lt;</a>
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        for ($i = $start_page; $i <= $end_page; $i++) { ?>
            <a href="javascript:void(0);" onclick="loadData(<?= $i; ?>)" <?= ($i == $page ? 'class="active"' : '') ?>><?= $i; ?></a>
        <?php } ?>
        <a href="javascript:void(0);" onclick="loadData(<?= min($page + 1, $total_pages); ?>)" <?= ($page == $total_pages ? 'class="disabled"' : '') ?>>&gt;&gt;</a>
        <a href="javascript:void(0);" onclick="loadData(<?= $total_pages; ?>)" <?= ($page == $total_pages ? 'class="disabled"' : '') ?>>Last</a>
    </div>
    <?php } ?>

    <?php
    echo ob_get_clean();
    exit;
}
?>

<div class="container">
    <div class="page-header">
        <h2>Peminjaman - Lokasi: <span id="currentLokasi"><?= htmlspecialchars($lokasi_default); ?></span></h2>
    </div>

    <div class="toolbar">
        <div class="left-tools">
            <!-- Tombol hanya muncul jika lokasi Fidelis -->
            <!-- Bagian Fidelis Buttons -->
<div id="fidelisButtons" style="display: none; gap: 10px;" class="button-group">
    <button class="btn btn-primary" onclick="showAddForm()">
        <i class="fas fa-plus"></i> Tambah Peminjaman
    </button>
    <button class="btn btn-primary" onclick="showAddFormLuar()">
        <i class="fas fa-plus"></i> Ajukan Peminjaman Luar Lokasi
    </button>
    <button class="btn btn-primary" onclick="showAddNotifikasi()">
        <i class="fas fa-plus"></i> Notifikasi
    </button>
    <button class="btn btn-success" onclick="showLocationModal()">
        <i class="fas fa-download"></i> Download
    </button>
</div>


            <div class="filter-group">
                <select class="form-select" id="filterSelect">
                    <?php
                    $lokasi_q = mysqli_query($koneksi, "SELECT nama_lokasi FROM lokasi ORDER BY nama_lokasi ASC");
                    while ($rowLokasi = mysqli_fetch_assoc($lokasi_q)) {
                        $selected = ($rowLokasi['nama_lokasi'] == $lokasi_default) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($rowLokasi['nama_lokasi'])."' $selected>".htmlspecialchars($rowLokasi['nama_lokasi'])."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="right-tools">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari...">
        </div>
    </div>

    <div id="dataTableContainer" class="table-container"></div>
</div>

<div id="locationModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#fff; padding:20px; border-radius:10px; width:400px; max-width:90%;">
        <h4>Pilih Rentang Tanggal</h4>
        <div class="form-group">
            <label for="tanggalAwal">Tanggal Awal</label>
            <input type="date" id="tanggalAwal" class="form-control">
        </div>
        <div class="form-group">
            <label for="tanggalAkhir">Tanggal Akhir</label>
            <input type="date" id="tanggalAkhir" class="form-control">
        </div>
        <div style="margin-top:15px; display:flex; justify-content:end; gap:10px;">
            <button class="btn btn-secondary" onclick="closeLocationModal()">Batal</button>
            <button class="btn btn-success" onclick="downloadPDF(document.getElementById('filterSelect').value)">Download</button>
        </div>
    </div>
</div>
<script>
let currentPage = 1;
const lokasiDefault = "<?= addslashes($lokasi_default); ?>";

function updateFidelisButtons(lokasi) {
    const buttons = document.getElementById('fidelisButtons');
    const lokasiLabel = document.getElementById('currentLokasi');
    lokasiLabel.textContent = lokasi;
    if (lokasi === lokasiDefault) {
        buttons.style.display = 'inline-flex';
    } else {
        buttons.style.display = 'none';
    }
}

function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const lokasi_filter = document.getElementById('filterSelect').value;

    updateFidelisButtons(lokasi_filter);

    document.getElementById('dataTableContainer').innerHTML = '<div style="text-align:center; padding:50px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';

    fetch('index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/data_peminjaman', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=true&page=${page}&search=${encodeURIComponent(search)}&lokasi_filter=${encodeURIComponent(lokasi_filter)}`
    })
    .then(response => response.text())
    .then(html => document.getElementById('dataTableContainer').innerHTML = html)
    .catch(err => console.error(err));
}

function showLocationModal() {
    document.getElementById('locationModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLocationModal() {
    document.getElementById('locationModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}
function downloadPDF(lokasi) {
    const tanggalAwal = document.getElementById('tanggalAwal').value;
    const tanggalAkhir = document.getElementById('tanggalAkhir').value;
    
    if (!tanggalAwal || !tanggalAkhir) {
        alert('Harap pilih tanggal awal dan tanggal akhir');
        return;
    }
    if (tanggalAwal > tanggalAkhir) {
        alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir');
        return;
    }
    
    closeLocationModal();
    
    const url = 'index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/cetak_peminjaman&lokasi=' + 
                encodeURIComponent(lokasi) + 
                '&tanggal_awal=' + encodeURIComponent(tanggalAwal) + 
                '&tanggal_akhir=' + encodeURIComponent(tanggalAkhir);
    window.open(url, '_blank');
}

function showAddForm() { window.location.href = 'index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/tambah_peminjaman'; }
function showAddFormLuar() { window.location.href = 'index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/tambah_peminjaman_luar'; }
function showAddNotifikasi() { window.location.href = 'index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/notifikasi'; }
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('searchInput').addEventListener('keyup', () => loadData(1));
    document.getElementById('filterSelect').addEventListener('change', () => loadData(1));

    loadData(1); // load halaman pertama
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
#locationModal {
    padding-top: 80px; /* jarak dari atas */
}

.button-group {
    display: flex;
    flex-wrap: wrap;   /* supaya kalau layar kecil, tombol turun ke bawah */
    gap: 10px;         /* jarak antar tombol */
    margin-bottom: 10px;
}

.button-group .btn {
    flex-shrink: 0;    /* tombol tidak mengecil */
}

/* Flex container untuk tombol agar rapi */
#fidelisButtons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

#fidelisButtons button {
    white-space: nowrap; /* agar teks tombol tidak pecah */
}
#locationModal .modal-content.location-modal {
    margin: 0 auto; 
}

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

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-success {
    background: #2980b9;
    color: white;
}

.btn-success:hover {
    background: #219a52;
}

.form-select {
    padding: 10px 15px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    min-width: 200px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
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
    min-width: 1200px;
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

.status-kembali {
    background: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pinjam {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-default {
    background: #f8f9fa;
    color: #6c757d;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    padding: 20px 30px;
    background: white;
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

.pagination a:hover:not(.disabled) {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination .active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.pagination .disabled {
    color: #ced4da;
    border-color: #e9ecef;
    cursor: not-allowed;
    background: #f8f9fa;
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
    margin: 3% auto;
    border-radius: 16px;
    max-width: 1000px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 25px 30px;
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
    font-size: 20px;
    font-weight: 600;
}

.close {
    font-size: 28px;
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
    padding: 30px;
}

.date-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e8ed;
}

.date-section h4, .location-section h4 {
    margin-bottom: 15px;
    font-size: 16px;
    color: #2c3e50;
    font-weight: 600;
}

.date-inputs {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.date-group {
    flex: 1;
    min-width: 200px;
}

.date-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.location-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
}

.location-item {
    background: linear-gradient(135deg, #f8faff 0%, #f1f4ff 100%);
    border: 2px solid #e1e8ed;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 15px;
}

.location-item:hover {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    border-color: #3498db;
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
}

.location-item i {
    font-size: 24px;
    color: #3498db;
    transition: all 0.2s ease;
}

.location-item:hover i {
    color: white;
}

.location-item span {
    font-weight: 500;
    font-size: 14px;
    line-height: 1.4;
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
    
    .form-select {
        min-width: 180px;
    }
    
    .location-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
    
    .page-header, .toolbar, .modal-body {
        padding: 15px 20px;
    }
    
    .date-inputs {
        flex-direction: column;
    }
}
</style>
