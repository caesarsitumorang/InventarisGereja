<?php
require_once("config/koneksi.php");

if (isset($_POST['ajax'])) {
    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $start = ($page - 1) * $limit;
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $lokasi_filter = isset($_POST['lokasi_filter']) ? trim($_POST['lokasi_filter']) : '';

    // Build where clause
    $where = "";
    $conditions = array();

    // Filter lokasi
    if (!empty($lokasi_filter)) {
        $lokasi_escaped = mysqli_real_escape_string($koneksi, $lokasi_filter);
        $conditions[] = "lokasi_simpan = '$lokasi_escaped'";
    }

    // Search
    if (!empty($search)) {
        $search = mysqli_real_escape_string($koneksi, $search);
        $conditions[] = "(nama_barang LIKE '%$search%' 
                          OR kode_barang LIKE '%$search%' 
                          OR no_perbaikan LIKE '%$search%' 
                          OR no_kerusakan LIKE '%$search%')";
    }

    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }

    // Hitung total
    $total_records_query = "SELECT COUNT(*) as count FROM perbaikan $where";
    $total_result = mysqli_query($koneksi, $total_records_query);
    $total_records = mysqli_fetch_assoc($total_result)['count'];
    $total_pages = ceil($total_records / $limit);

    // Ambil data
    $query = "SELECT id_perbaikan, no_perbaikan, tanggal_perbaikan, no_kerusakan,
                     kode_barang, nama_barang, lokasi_simpan, jumlah, satuan, 
                     biaya_perbaikan, keterangan
              FROM perbaikan $where
              ORDER BY id_perbaikan ASC
              LIMIT $start, $limit";
    $result = mysqli_query($koneksi, $query);

    ob_start();
    ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No Perbaikan</th>
                    <th>Tanggal Perbaikan</th>
                    <th>No Kerusakan</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Lokasi Simpan</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Biaya Perbaikan</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = $start + 1;
                while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['no_perbaikan']); ?></td>
                        <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_perbaikan']))); ?></td>
                        <td><?= htmlspecialchars($row['no_kerusakan']); ?></td>
                        <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                        <td><?= htmlspecialchars($row['lokasi_simpan']); ?></td>
                        <td><?= htmlspecialchars($row['jumlah']); ?></td>
                        <td><?= htmlspecialchars($row['satuan']); ?></td>
                        <td><?= htmlspecialchars(number_format($row['biaya_perbaikan'], 0, ',', '.')); ?></td>
                        <td><?= htmlspecialchars($row['keterangan']); ?></td>
                    </tr>
                <?php } ?>
                <?php if (mysqli_num_rows($result) == 0) { ?>
                    <tr>
                        <td colspan="11" class="text-center">Tidak ada data</td>
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
        <h2>Perbaikan</h2>
    </div>

    <div class="toolbar">
        <div class="left-tools">
            <button class="btn btn-primary" onclick="showAddForm()">
                <i class="fas fa-plus"></i> Tambah Data Perbaikan
            </button>
            <div class="filter-group">
                <select class="form-select" id="filterSelect">
                    <option value="Paroki">Paroki</option>
                    <option value="Stasi St. Fidelis (Karo Simalem)">Stasi St. Fidelis (Karo Simalem)</option>
                    <option value="Stasi St. Yohanes Penginjil (Minas Jaya)">Stasi St. Yohanes Penginjil (Minas Jaya)</option>
                    <option value="Stasi St. Agustinus (Minas Barat)">Stasi St. Agustinus (Minas Barat)</option>
                    <option value="Stasi St. Benediktus (Teluk Siak)">Stasi St. Benediktus (Teluk Siak)</option>
                    <option value="Stasi St. Paulus (Inti 4)">Stasi St. Paulus (Inti 4)</option>
                    <option value="Stasi St. Fransiskus Asisi (Inti 7)">Stasi St. Fransiskus Asisi (Inti 7)</option>
                    <option value="Stasi St. Paulus (Empang Pandan)">Stasi St. Paulus (Empang Pandan)</option>
                    <option value="Stasi Sta. Maria Bunda Karmel (Teluk Merbau)">Stasi Sta. Maria Bunda Karmel (Teluk Merbau)</option>
                    <option value="Stasi Sta. Elisabet (Sialang Sakti)">Stasi Sta. Elisabet (Sialang Sakti)</option>
                    <option value="Stasi St. Petrus (Pangkalan Makmur)">Stasi St. Petrus (Pangkalan Makmur)</option>
                    <option value="Stasi St. Stefanus (Zamrud)">Stasi St. Stefanus (Zamrud)</option>
                    <option value="Stasi St. Mikael (Siak Raya)">Stasi St. Mikael (Siak Raya)</option>
                    <option value="Stasi St. Paulus Rasul (Siak Merambai)">Stasi St. Paulus Rasul (Siak Merambai)</option>
                </select>
            </div>
            <button class="btn btn-success" onclick="showLocationModal()">
                <i class="fas fa-download"></i> Download
            </button>
        </div>
        <div class="right-tools">
            <input type="text" id="searchInput" class="search-input" placeholder="Cari...">
        </div>
    </div>

    <div id="dataTableContainer" class="table-container">
    </div>
</div>

<div id="locationModal" class="modal">
    <div class="modal-content location-modal">
        <div class="modal-header">
            <h3>Download Laporan Perbaikan</h3>
            <span class="close" onclick="closeLocationModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="date-section">
                <h4>Pilih Periode Laporan</h4>
                <div class="date-inputs">
                    <div class="date-group">
                        <label>Tanggal Awal:</label>
                        <input type="date" id="tanggalAwal" class="form-control">
                    </div>
                    <div class="date-group">
                        <label>Tanggal Akhir:</label>
                        <input type="date" id="tanggalAkhir" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="location-section">
                <h4>Pilih Lokasi</h4>
                <div class="location-grid">
                    <div class="location-item" onclick="downloadPDF('Paroki')">
                        <i class="fas fa-church"></i>
                        <span>Paroki</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Fidelis (Karo Simalem)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Fidelis (Karo Simalem)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Yohanes Penginjil (Minas Jaya)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Yohanes Penginjil (Minas Jaya)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Agustinus (Minas Barat)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Agustinus (Minas Barat)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Benediktus (Teluk Siak)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Benediktus (Teluk Siak)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Paulus (Inti 4)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Paulus (Inti 4)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Fransiskus Asisi (Inti 7)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Fransiskus Asisi (Inti 7)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Paulus (Empang Pandan)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Paulus (Empang Pandan)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi Sta. Maria Bunda Karmel (Teluk Merbau)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi Sta. Maria Bunda Karmel (Teluk Merbau)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi Sta. Elisabet (Sialang Sakti)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi Sta. Elisabet (Sialang Sakti)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Petrus (Pangkalan Makmur)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Petrus (Pangkalan Makmur)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Stefanus (Zamrud)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Stefanus (Zamrud)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Mikael (Siak Raya)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Mikael (Siak Raya)</span>
                    </div>
                    <div class="location-item" onclick="downloadPDF('Stasi St. Paulus Rasul (Siak Merambai)')">
                        <i class="fas fa-cross"></i>
                        <span>Stasi St. Paulus Rasul (Siak Merambai)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
#locationModal {
    padding-top: 80px; /* jarak dari atas */
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
    background:  #2980b9;
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

<script>
let currentPage = 1;
let searchTimeout;

function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const lokasi_filter = document.getElementById('filterSelect').value;
    
    // Show loading
    document.getElementById('dataTableContainer').innerHTML = '<div style="text-align: center; padding: 50px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    
    fetch('index_admin_utama.php?page_admin_utama=data_transaksi/perbaikan/data_perbaikan', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=true&page=${page}&search=${encodeURIComponent(search)}&lokasi_filter=${encodeURIComponent(lokasi_filter)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        document.getElementById('dataTableContainer').innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('dataTableContainer').innerHTML = '<div style="text-align: center; padding: 50px; color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan saat memuat data</div>';
    });
}

function showLocationModal() {
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    document.getElementById('tanggalAkhir').value = today.toISOString().split('T')[0];
    document.getElementById('tanggalAwal').value = thirtyDaysAgo.toISOString().split('T')[0];
    
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
    
    // Validasi tanggal
    if (!tanggalAwal || !tanggalAkhir) {
        alert('Harap pilih tanggal awal dan tanggal akhir');
        return;
    }
    
    if (tanggalAwal > tanggalAkhir) {
        alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir');
        return;
    }
    
    closeLocationModal();
    
    // Show loading notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #2ecc71;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 2000;
        font-weight: 500;
    `;
    notification.innerHTML = '<i class="fas fa-download"></i> Mengunduh PDF untuk ' + lokasi + '...';
    document.body.appendChild(notification);
    
    setTimeout(() => {
        document.body.removeChild(notification);
    }, 3000);
    
    // Open PDF in new tab with date parameters
    const url = 'index_admin_utama.php?page_admin_utama=data_transaksi/perbaikan/cetak_perbaikan&lokasi=' + 
                encodeURIComponent(lokasi) + 
                '&tanggal_awal=' + encodeURIComponent(tanggalAwal) + 
                '&tanggal_akhir=' + encodeURIComponent(tanggalAkhir);
    window.open(url, '_blank');
}

function showAddForm() {
    window.location.href = 'index_admin_utama.php?page_admin_utama=data_transaksi/perbaikan/tambah_perbaikan';
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadData(1);
        }, 500);
    });
    
    // Filter functionality
    document.getElementById('filterSelect').addEventListener('change', function() {
        loadData(1);
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('locationModal');
        if (event.target === modal) {
            closeLocationModal();
        }
    });
    
    // Load initial data with default filter (Paroki)
    loadData(1);
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLocationModal();
    }
});
</script>