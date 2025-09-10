<?php
require_once("config/koneksi.php");

// Fungsi generate nomor kerusakan otomatis
function generateNoKerusakan($koneksi) {
    $prefix = "KS";
    $query = "SELECT no_kerusakan FROM kerusakan 
              WHERE no_kerusakan LIKE '$prefix%' 
              ORDER BY no_kerusakan DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_kerusakan'], 2);
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001";
    }
    return $prefix . $newNumber;
}

// Ambil semua inventaris
$inventaris = mysqli_query($koneksi, "SELECT kode_barang, nama_barang, lokasi_simpan, jumlah, satuan FROM inventaris ORDER BY nama_barang ASC");

// Ambil semua lokasi unik
$lokasi = mysqli_query($koneksi, "SELECT DISTINCT lokasi_simpan FROM inventaris ORDER BY lokasi_simpan ASC");

if (isset($_POST['submit'])) {
    $no_kerusakan = generateNoKerusakan($koneksi);
    $tanggal_kerusakan = $_POST['tanggal_kerusakan'];
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $lokasi_simpan = $_POST['lokasi_simpan'];
    $jumlah = (int)$_POST['jumlah'];
    $satuan = $_POST['satuan'];
    $keterangan = $_POST['keterangan'];

    if (session_status() === PHP_SESSION_NONE) session_start();
    $namaAkun = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';

    // Insert ke tabel kerusakan
    $query = "INSERT INTO kerusakan 
              (no_kerusakan, tanggal_kerusakan, lokasi_simpan, kode_barang, nama_barang, jumlah, satuan, keterangan, nama_akun) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "sssssisss",
        $no_kerusakan, $tanggal_kerusakan, $lokasi_simpan, $kode_barang, 
        $nama_barang, $jumlah, $satuan, $keterangan, $namaAkun
    );

    if (mysqli_stmt_execute($stmt)) {
        // Kurangi jumlah barang di inventaris
        $update = "UPDATE inventaris SET jumlah = jumlah - ? WHERE kode_barang = ?";
        $stmt2 = mysqli_prepare($koneksi, $update);
        mysqli_stmt_bind_param($stmt2, "is", $jumlah, $kode_barang);
        mysqli_stmt_execute($stmt2);

        echo "<script>
                alert('Data kerusakan berhasil ditambahkan');
                window.location.href='index_admin.php?page_admin=data_transaksi/kerusakan/data_kerusakan';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data kerusakan');</script>";
    }
}
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Data Kerusakan</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label>No Kerusakan</label>
                    <input type="text" name="no_kerusakan" value="<?= generateNoKerusakan($koneksi); ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Tanggal Kerusakan</label>
                    <input type="date" name="tanggal_kerusakan" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Lokasi Simpan</label>
                    <select id="lokasi_simpan" name="lokasi_simpan" required class="form-control" onchange="loadBarang()">
                        <option value="">Pilih Lokasi</option>
                        <?php while ($row = mysqli_fetch_assoc($lokasi)) { ?>
                            <option value="<?= htmlspecialchars($row['lokasi_simpan']); ?>"><?= htmlspecialchars($row['lokasi_simpan']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label>Nama Barang</label>
                    <select id="nama_barang" name="nama_barang" required class="form-control" onchange="updateDataBarang()">
                        <option value="">Pilih Barang</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Jumlah Rusak</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Satuan</label>
                    <input type="text" id="satuan" name="satuan" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Stok Tersedia</label>
                    <input type="text" id="stok" name="stok" readonly class="form-control">
                </div>
            </div>

            <div class="form-group full">
                <label>Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_admin.php?page_admin=data_transaksi/kerusakan/data_kerusakan" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
const inventaris = [
<?php
mysqli_data_seek($inventaris, 0);
while($row = mysqli_fetch_assoc($inventaris)) {
    echo "{kode_barang:'".addslashes($row['kode_barang'])."', nama_barang:'".addslashes($row['nama_barang'])."', lokasi_simpan:'".addslashes($row['lokasi_simpan'])."', jumlah:".(int)$row['jumlah'].", satuan:'".addslashes($row['satuan'])."'},";
}
?>
];

function loadBarang() {
    const lokasi = document.getElementById('lokasi_simpan').value;
    const nama_barang = document.getElementById('nama_barang');
    nama_barang.innerHTML = '<option value="">Pilih Barang</option>';

    if (!lokasi) return;

    inventaris.forEach(item => {
        if(item.lokasi_simpan === lokasi) {
            const option = document.createElement('option');
            option.value = item.nama_barang;
            option.text = item.nama_barang;
            option.setAttribute('data-kode', item.kode_barang);
            option.setAttribute('data-jumlah', item.jumlah);
            option.setAttribute('data-satuan', item.satuan);
            nama_barang.appendChild(option);
        }
    });

    // reset detail
    document.getElementById('kode_barang').value = '';
    document.getElementById('stok').value = '';
    document.getElementById('satuan').value = '';
    document.getElementById('jumlah').value = '';
}

function updateDataBarang() {
    const select = document.getElementById('nama_barang');
    const selected = select.options[select.selectedIndex];

    document.getElementById('kode_barang').value   = selected.getAttribute('data-kode') || '';
    document.getElementById('stok').value          = selected.getAttribute('data-jumlah') || '';
    document.getElementById('satuan').value        = selected.getAttribute('data-satuan') || '';
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