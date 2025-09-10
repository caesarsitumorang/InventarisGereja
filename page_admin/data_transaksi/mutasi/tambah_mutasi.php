<?php
require_once("config/koneksi.php");

// Fungsi generate kode barang otomatis
function generateKodeBarang($koneksi, $kategori, $lokasi) {
    $kategoriPrefix = [
        "Bangunan" => 10001,
        "Liturgi" => 20001,
        "Pakaian Misa" => 30001,
        "Pakaian Misdinar" => 40001,
        "Buku Misa" => 50001,
        "Mebulair" => 60001,
        "Alat Elektronik" => 70001,
        "Alat Rumah Tangga" => 80001,
    ];

    if (!isset($kategoriPrefix[$kategori])) return null;

    $baseKode = $kategoriPrefix[$kategori];

    $query = "SELECT kode_barang FROM inventaris WHERE kategori=? AND lokasi_simpan=? ORDER BY kode_barang DESC LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $kategori, $lokasi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        return (string)($row['kode_barang'] + 1);
    } else {
        return (string)$baseKode;
    }
}

// Fungsi generate nomor mutasi
function generateNoMutasi($koneksi) {
    $prefix = "MT";
    $query = "SELECT no_mutasi FROM mutasi WHERE no_mutasi LIKE '$prefix%' ORDER BY no_mutasi DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_mutasi'], 2);
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001";
    }
    return $prefix . $newNumber;
}

// Ambil semua inventaris
$inventaris = mysqli_query($koneksi, "SELECT kode_barang, nama_barang, lokasi_simpan, jumlah, satuan, kategori FROM inventaris ORDER BY nama_barang ASC");

// Ambil semua lokasi unik
$lokasi = mysqli_query($koneksi, "SELECT DISTINCT lokasi_simpan FROM inventaris ORDER BY lokasi_simpan ASC");

if (isset($_POST['submit'])) {
    $no_mutasi        = generateNoMutasi($koneksi);
    $tanggal_mutasi   = $_POST['tanggal_mutasi'];
    $lokasi_awal      = $_POST['lokasi_awal'];
    $lokasi_mutasi    = $_POST['lokasi_mutasi'];
    $kode_barang_lama = $_POST['kode_barang_lama'];
    $kode_barang_baru = $_POST['kode_barang_baru'];
    $jumlah           = (int)$_POST['jumlah'];
    $satuan           = $_POST['satuan'];
    $kategori         = $_POST['kategori'];
    $keterangan       = $_POST['keterangan'];

    if (session_status() === PHP_SESSION_NONE) session_start();
    $namaAkun = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';

    // Ambil data inventaris lama
    $queryLama = "SELECT * FROM inventaris WHERE kode_barang = ?";
    $stmtLama = mysqli_prepare($koneksi, $queryLama);
    mysqli_stmt_bind_param($stmtLama, "s", $kode_barang_lama);
    mysqli_stmt_execute($stmtLama);
    $resultLama = mysqli_stmt_get_result($stmtLama);
    $dataLama   = mysqli_fetch_assoc($resultLama);

    if ($dataLama) {
    $nama_barang = $_POST['nama_barang']; // ambil dari form POST

    // === Insert data ke tabel mutasi ===
    // === Insert data ke tabel mutasi ===
$query = "INSERT INTO mutasi 
    (no_mutasi, tanggal_mutasi, lokasi_awal, kode_barang, nama_barang, jumlah, satuan, lokasi_mutasi, keterangan, nama_akun) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param(
    $stmt, "ssssssssss",
    $no_mutasi,
    $tanggal_mutasi,
    $lokasi_awal,
    $kode_barang_baru, // kode baru
    $nama_barang,      // ini string, bukan int
    $jumlah,
    $satuan,
    $lokasi_mutasi,
    $keterangan,
    $namaAkun
);
mysqli_stmt_execute($stmt);


    $updateLama = "UPDATE inventaris SET jumlah = jumlah - ?, jumlah_total = jumlah_total - ? WHERE kode_barang = ?";
    $stmtUpdate = mysqli_prepare($koneksi, $updateLama);
    mysqli_stmt_bind_param($stmtUpdate, "iis", $jumlah, $jumlah, $kode_barang_lama);
    mysqli_stmt_execute($stmtUpdate);

    // === Insert inventaris baru ===
    $insertBaru = "INSERT INTO inventaris 
        (kode_barang, nama_barang, kategori, lokasi_simpan, jumlah, jumlah_total, satuan, tgl_pengadaan, kondisi, sumber, harga, keterangan, nama_akun) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = mysqli_prepare($koneksi, $insertBaru);

    $sumber = "Hibah";
    $harga  = isset($dataLama['harga']) ? $dataLama['harga'] : 0;

    mysqli_stmt_bind_param(
        $stmtInsert, "ssssisssssdss",
        $kode_barang_baru,     // s
        $nama_barang,          // s (tetap pakai yang dipilih user)
        $kategori,             // s
        $lokasi_mutasi,        // s
        $jumlah,               // i
        $jumlah,               // i
        $satuan,               // s
        $tanggal_mutasi,       // s
        $dataLama['kondisi'],  // s
        $sumber,               // s
        $harga,                // d
        $keterangan,           // s
        $namaAkun              // s
    );
    mysqli_stmt_execute($stmtInsert);
}


    echo "<script>alert('Data mutasi berhasil ditambahkan');
          window.location.href='index_admin.php?page_admin=data_transaksi/mutasi/data_mutasi';
          </script>";
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
                    <label>Lokasi Awal</label>
                    <select id="lokasi_awal" name="lokasi_awal" required class="form-control" onchange="loadBarang()">
                        <option value="">Pilih Lokasi</option>
                        <?php while($row = mysqli_fetch_assoc($lokasi)) { ?>
                            <option value="<?= htmlspecialchars($row['lokasi_simpan']); ?>"><?= htmlspecialchars($row['lokasi_simpan']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Nama Barang</label>
                    <select id="nama_barang" name="nama_barang" required class="form-control" onchange="updateDataBarang()">
                        <option value="">Pilih Barang</option>
                    </select>
                </div>
                <div class="form-group half">
                    <label>Kode Barang Lama</label>
                    <input type="text" id="kode_barang_lama" name="kode_barang_lama" readonly required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Jumlah Tersedia</label>
                    <input type="number" id="stok" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Satuan</label>
                    <input type="text" id="satuan" name="satuan" readonly class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Jumlah Mutasi</label>
                    <input type="number" id="jumlah" name="jumlah" min="1" required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Lokasi Mutasi</label>
                    <select id="lokasi_mutasi" name="lokasi_mutasi" required class="form-control" onchange="generateKodeBarangBaru()">
                        <option value="">Pilih Lokasi Mutasi</option>
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

            <!-- Kode Barang Baru tidak tampil ke user, tapi tetap ada untuk submit -->
            <input type="hidden" id="kode_barang_baru" name="kode_barang_baru">
            <input type="hidden" id="kategori" name="kategori">

            <div class="form-group full">
                <label>Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_admin.php?page_admin=data_transaksi/mutasi/data_mutasi" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
const inventaris = [
<?php
mysqli_data_seek($inventaris, 0);
while($row = mysqli_fetch_assoc($inventaris)) {
    echo "{kode_barang:'".addslashes($row['kode_barang'])."', nama_barang:'".addslashes($row['nama_barang'])."', lokasi_simpan:'".addslashes($row['lokasi_simpan'])."', jumlah:".(int)$row['jumlah'].", satuan:'".addslashes($row['satuan'])."', kategori:'".addslashes($row['kategori'])."'},";
}
?>
];

function loadBarang() {
    const lokasi = document.getElementById('lokasi_awal').value;
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
            option.setAttribute('data-kategori', item.kategori);
            nama_barang.appendChild(option);
        }
    });

    document.getElementById('kode_barang_lama').value = '';
    document.getElementById('kode_barang_baru').value = '';
    document.getElementById('stok').value = '';
    document.getElementById('satuan').value = '';
    document.getElementById('kategori').value = '';
    document.getElementById('jumlah').value = '';
}

function updateDataBarang() {
    const select = document.getElementById('nama_barang');
    const selected = select.options[select.selectedIndex];

    document.getElementById('kode_barang_lama').value = selected.getAttribute('data-kode') || '';
    document.getElementById('satuan').value = selected.getAttribute('data-satuan') || '';
    document.getElementById('kategori').value = selected.getAttribute('data-kategori') || '';
    document.getElementById('stok').value = selected.getAttribute('data-jumlah') || '';
    document.getElementById('jumlah').max = selected.getAttribute('data-jumlah') || '';

    generateKodeBarangBaru();
}

function generateKodeBarangBaru() {
    let kategori = document.getElementById("kategori").value;
    let lokasi = document.getElementById("lokasi_mutasi").value;

    if(kategori && lokasi) {
        fetch("page_admin/data_inventaris/agustinus/get_kode_barang.php?kategori="+encodeURIComponent(kategori)+"&lokasi="+encodeURIComponent(lokasi))
        .then(res => res.text())
        .then(data => {
            document.getElementById("kode_barang_baru").value = data.trim();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById("kode_barang_baru").value = '';
        });
    } else {
        document.getElementById("kode_barang_baru").value = '';
    }
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