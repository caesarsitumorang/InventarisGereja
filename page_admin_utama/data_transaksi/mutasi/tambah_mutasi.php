<?php
require_once("config/koneksi.php");

function generateNoMutasi($koneksi) {
    $prefix = "MT";
    $query = "SELECT no_mutasi FROM mutasi 
              WHERE no_mutasi LIKE '$prefix%' 
              ORDER BY no_mutasi DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_mutasi'], 2);
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001";
    }
    return $prefix . $newNumber;
}

$barang = mysqli_query($koneksi, "SELECT kode_barang, nama_barang, lokasi_simpan, jumlah_total, satuan 
                                  FROM inventaris ORDER BY nama_barang ASC");

if (isset($_POST['submit'])) {
    $no_mutasi      = generateNoMutasi($koneksi);
    $tanggal_mutasi = $_POST['tanggal_mutasi'];
    $lokasi_awal    = $_POST['lokasi_awal'];
    $lokasi_mutasi  = $_POST['lokasi_mutasi'];
    $kode_barang    = $_POST['kode_barang'];
    $nama_barang    = $_POST['nama_barang']; // <-- sesuaikan dengan form
    $jumlah         = (int)$_POST['jumlah'];
    $satuan         = $_POST['satuan'];
    $keterangan     = $_POST['keterangan'];

    // Insert ke tabel mutasi
    $query = "INSERT INTO mutasi 
              (no_mutasi, tanggal_mutasi, lokasi_awal, kode_barang, nama_barang, jumlah, satuan, lokasi_mutasi, keterangan) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);

    mysqli_stmt_bind_param($stmt, "sssssssss",
        $no_mutasi,
        $tanggal_mutasi,
        $lokasi_awal,
        $kode_barang,
        $nama_barang,
        $jumlah,
        $satuan,
        $lokasi_mutasi,
        $keterangan
    );

    if (mysqli_stmt_execute($stmt)) {
        // Kurangi jumlah_total di inventaris
        $updateTotal = "UPDATE inventaris SET jumlah_total = jumlah_total - ? WHERE kode_barang = ?";
        $stmtTotal = mysqli_prepare($koneksi, $updateTotal);
        mysqli_stmt_bind_param($stmtTotal, "is", $jumlah, $kode_barang);
        mysqli_stmt_execute($stmtTotal);

        echo "<script>
                alert('Data mutasi berhasil ditambahkan');
                window.location.href='index_admin_utama.php?page_admin_utama=data_transaksi/mutasi/data_mutasi';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data mutasi');</script>";
    }
}
?>


<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Data Mutasi</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label>No Mutasi</label>
                    <input type="text" name="no_mutasi" value="<?= generateNoMutasi($koneksi); ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Tanggal Mutasi</label>
                    <input type="date" name="tanggal_mutasi" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Nama Barang</label>
                    <select id="nama_barang" name="nama_barang" required class="form-control" onchange="updateDataBarang()">
                        <option value="">Pilih Barang</option>
                        <?php while ($row = mysqli_fetch_assoc($barang)) { ?>
                            <option value="<?= htmlspecialchars($row['nama_barang']); ?>"
                                data-kode="<?= $row['kode_barang']; ?>"
                                data-lokasi="<?= htmlspecialchars($row['lokasi_simpan']); ?>"
                                data-satuan="<?= htmlspecialchars($row['satuan']); ?>"
                                data-jumlah="<?= $row['jumlah_total']; ?>">
                                <?= $row['nama_barang']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label>Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" readonly required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Lokasi Awal</label>
                    <input type="text" id="lokasi_awal" name="lokasi_awal" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Jumlah Tersedia</label>
                    <input type="number" id="stok" readonly class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Jumlah Mutasi</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Satuan</label>
                    <input type="text" id="satuan" name="satuan" readonly required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Lokasi Mutasi</label>
                    <select name="lokasi_mutasi" required class="form-control">
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
            </div>

            <div class="form-group full">
                <label>Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_admin_utama.php?page_admin_utama=data_transaksi/mutasi/data_mutasi" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateDataBarang() {
    var select = document.getElementById('nama_barang');
    var selected = select.options[select.selectedIndex];

    document.getElementById('kode_barang').value   = selected.getAttribute('data-kode') || '';
    document.getElementById('lokasi_awal').value   = selected.getAttribute('data-lokasi') || '';
    document.getElementById('satuan').value        = selected.getAttribute('data-satuan') || '';
    document.getElementById('stok').value          = selected.getAttribute('data-jumlah') || '';
    document.getElementById('jumlah').max          = selected.getAttribute('data-jumlah') || '';
}
</script>


<style>
    .form-group.third {
    flex: 1;
}
.form-container {
    padding: 16px;
    background: #f8f9fa;
    min-height: calc(100vh - 60px);
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.form-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    width: 100%;
    max-width: 800px;
    margin-top: 16px;
}

.form-header {
    padding: 16px 24px;
    border-bottom: 1px solid #eee;
}

.form-header h2 {
    margin: 0;
    font-size: 18px;
    color: #2d3436;
    font-weight: 600;
}

.add-form {
    padding: 24px;
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group.half {
    flex: 1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #2d3436;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dce1e6;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

select.form-control {
    height: 38px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M0 2l4 4 4-4z' fill='%23333'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 8px;
    padding-right: 32px;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

textarea.form-control {
    min-height: 80px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #eee;
}

.btn-submit, .btn-cancel {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-submit {
    background: #3498db;
    color: white;
    border: none;
}

.btn-cancel {
    background: #e74c3c;
    color: white;
    border: none;
}

.btn-submit:hover, .btn-cancel:hover {
    opacity: 0.9;
}

@media (max-width: 768px) {
    .form-container {
        padding: 8px;
    }
    
    .form-card {
        margin-top: 8px;
    }

    .add-form {
        padding: 16px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 8px;
    }

    .form-actions {
        flex-direction: column;
    }
    
    .btn-submit, .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function formatRupiah(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if(value != '') {
        value = parseInt(value).toLocaleString('id-ID');
        input.value = value;
    }
}


</script>