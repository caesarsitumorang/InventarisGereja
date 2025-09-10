<?php
require_once("config/koneksi.php");

// === Fungsi Generate Kode Barang Berdasarkan Kategori & Lokasi ===
function generateKodeBarang($koneksi, $kategori, $lokasi) {
    // Mapping kategori -> base kode
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

    if (!isset($kategoriPrefix[$kategori])) {
        return null; // kategori tidak ditemukan
    }

    $baseKode = $kategoriPrefix[$kategori];

    // cek kode terakhir di lokasi & kategori yg sama
    $query = "SELECT kode_barang 
              FROM inventaris 
              WHERE kategori = ? AND lokasi_simpan = ? 
              ORDER BY kode_barang DESC LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $kategori, $lokasi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        return (string)($row['kode_barang'] + 1); // lanjut nomor terakhir
    } else {
        return (string)$baseKode; 
    }
}

// === Proses Simpan ===
if(isset($_POST['submit'])) {
    $nama_barang   = substr(mysqli_real_escape_string($koneksi, $_POST['nama_barang']), 0, 50);
    $kategori      = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $lokasi_simpan = substr(mysqli_real_escape_string($koneksi, $_POST['lokasi_simpan']), 0, 100);
    $jumlah_total  = (int)$_POST['jumlah_total'];
    $jumlah        = $jumlah_total; // otomatis sama dengan jumlah_total
    $satuan        = substr(mysqli_real_escape_string($koneksi, $_POST['satuan']), 0, 10);
    $tgl_pengadaan = mysqli_real_escape_string($koneksi, $_POST['tgl_pengadaan']);
    $kondisi       = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $sumber        = mysqli_real_escape_string($koneksi, $_POST['sumber']);
    $harga         = substr(str_replace([',', '.'], '', $_POST['harga']), 0, 30);
    $keterangan    = substr(mysqli_real_escape_string($koneksi, $_POST['keterangan']), 0, 100);

    // ambil username dari session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $namaAkun = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';

    // generate kode barang berdasarkan kategori & lokasi
    $kode_barang = generateKodeBarang($koneksi, $kategori, $lokasi_simpan);

    $query = "INSERT INTO inventaris 
              (kode_barang, nama_barang, kategori, lokasi_simpan, jumlah, jumlah_total, satuan, tgl_pengadaan, kondisi, sumber, harga, keterangan, nama_akun) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssssiisssssss", 
        $kode_barang, $nama_barang, $kategori, $lokasi_simpan, $jumlah, $jumlah_total,
        $satuan, $tgl_pengadaan, $kondisi, $sumber, $harga, $keterangan, $namaAkun
    );
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data berhasil ditambahkan');
                window.location.href='index_admin_utama.php?page_admin_utama=data_inventaris/mikael/data_inventaris_mikael';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data');</script>";
    }
}
?>


<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Inventaris</h2>
        </div>
        
        <form method="POST" class="add-form ">
            <div class="form-row">
                <div class="form-group half">
                    <label for="lokasi_simpan">Lokasi Penyimpanan</label>
                    <select id="lokasi_simpan" name="lokasi_simpan" required class="form-control" onchange="updateKodeBarang()">
                        <option value="">Pilih Lokasi</option>
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

                <div class="form-group half">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required class="form-control" onchange="updateKodeBarang()">
                        <option value="">Pilih Kategori</option>
                        <option value="Bangunan">Bangunan</option>
                        <option value="Liturgi">Liturgi</option>
                        <option value="Pakaian Misa">Pakaian Misa</option>
                        <option value="Pakaian Misdinar">Pakaian Misdinar</option>
                        <option value="Buku Misa">Buku Misa</option>
                        <option value="Mebulair">Mebulair</option>
                        <option value="Alat Elektronik">Alat Elektronik</option>
                        <option value="Alat Rumah Tangga">Alat Rumah Tangga</option>
                    </select>
                </div>
            </div>

            <!-- Kode Barang (readonly) -->
            <div class="form-row">
                <div class="form-group half">
                    <label for="kode_barang">Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang_display" class="form-control" readonly>
                    <!-- hidden input untuk submit ke server -->
                    <input type="hidden" id="kode_barang_hidden" name="kode_barang">
                </div>
            </div>

            <!-- Sisa Form -->
            <div class="form-row">
                <div class="form-group half">
                    <label for="nama_barang">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang" maxlength="50" required class="form-control">
                </div>

                <div class="form-group half">
                    <label for="tgl_pengadaan">Tanggal Pengadaan</label>
                    <input type="date" id="tgl_pengadaan" name="tgl_pengadaan" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group third">
                    <label for="jumlah_total">Jumlah Total</label>
                    <input type="number" id="jumlah_total" name="jumlah_total" required class="form-control" min="0">
                </div>

                <div class="form-group third">
                    <label for="satuan">Satuan</label>
                    <input type="text" id="satuan" name="satuan" maxlength="10" required class="form-control">
                </div>

                <div class="form-group third">
                    <label for="kondisi">Kondisi</label>
                    <select id="kondisi" name="kondisi" required class="form-control">
                        <option value="">Pilih Kondisi</option>
                        <option value="Baru">Baru</option>
                        <option value="Lama">Lama</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="sumber">Sumber Pengadaan</label>
                    <select id="sumber" name="sumber" required class="form-control">
                        <option value="">Pilih Sumber</option>
                        <option value="Beli">Beli</option>
                        <option value="Donasi">Donasi</option>
                        <option value="Hibah">Hibah</option>
                    </select>
                </div>

                <div class="form-group half">
                    <label for="harga">Harga</label>
                    <input type="text" id="harga" name="harga" required class="form-control" 
                           onkeyup="formatRupiah(this)" maxlength="30">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" maxlength="100" class="form-control"></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index_admin_utama.php?page_admin_utama=data_inventaris/agustinus/data_inventaris_agustinus" class="btn-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateKodeBarang() {
    let kategori = document.getElementById("kategori").value;
    let lokasi   = document.getElementById("lokasi_simpan").value;

    if(kategori && lokasi){
        // panggil get_kode_barang.php di folder yang sesuai
        fetch("page_admin_utama/data_inventaris/agustinus/get_kode_barang.php?kategori="+encodeURIComponent(kategori)+"&lokasi="+encodeURIComponent(lokasi))
        .then(res => res.text())
        .then(data => {
            document.getElementById("kode_barang").value = data;
            document.getElementById("kode_barang_hidden").value = data;
        });
    } else {
        document.getElementById("kode_barang").value = "";
        document.getElementById("kode_barang_hidden").value = "";
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