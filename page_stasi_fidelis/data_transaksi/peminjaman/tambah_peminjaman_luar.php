<?php
require_once("config/koneksi.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// === Fungsi Generate No Peminjaman ===
function generateNoPeminjaman($koneksi) {
    $prefix = "PJ";
    $query = "SELECT no_peminjaman FROM peminjaman 
              WHERE no_peminjaman LIKE '$prefix%' 
              ORDER BY no_peminjaman DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_peminjaman'], 2); 
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001"; 
    }
    return $prefix . $newNumber; 
}

$lokasi_query = "SELECT nama_lokasi FROM lokasi WHERE nama_lokasi != 'Stasi St. Fidelis (Karo Simalem)' ORDER BY nama_lokasi ASC";
$lokasi_result = mysqli_query($koneksi, $lokasi_query);
$lokasi_unik = [];
while ($row = mysqli_fetch_assoc($lokasi_result)) {
    $lokasi_unik[] = $row['nama_lokasi'];
}

$inventaris = mysqli_query($koneksi, "SELECT kode_barang, nama_barang, lokasi_simpan, jumlah, satuan FROM inventaris ORDER BY lokasi_simpan, nama_barang ASC");
$data_inventaris = [];
while($row = mysqli_fetch_assoc($inventaris)) {
    $data_inventaris[] = $row;
}

if(isset($_POST['submit'])) {
    $no_peminjaman = generateNoPeminjaman($koneksi);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $lokasi_simpan = $_POST['lokasi_simpan'];
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $jumlah_pinjam = (int)$_POST['jumlah_pinjam'];
    $satuan = $_POST['satuan'];
    $lokasi_pinjam = $_POST['lokasi_pinjam']; 
    $nama_peminjam = $_POST['nama_peminjam'];
    $keterangan = $_POST['keterangan'];
    $status = "Pending"; 
    $namaAkun = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';

    $query = "INSERT INTO peminjaman 
              (no_peminjaman, tanggal_pinjam, lokasi_simpan, kode_barang, nama_barang, 
               jumlah_pinjam, satuan, lokasi_pinjam, nama_peminjam, keterangan, status, nama_akun) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "sssssissssss", 
        $no_peminjaman, $tanggal_pinjam, $lokasi_simpan, $kode_barang, $nama_barang, 
        $jumlah_pinjam, $satuan, $lokasi_pinjam, $nama_peminjam, $keterangan, $status, $namaAkun
    );

    if(mysqli_stmt_execute($stmt)) {
        // Update stok inventaris
        $update = "UPDATE inventaris SET jumlah = jumlah - ? WHERE kode_barang = ?";
        $stmt2 = mysqli_prepare($koneksi, $update);
        mysqli_stmt_bind_param($stmt2, "is", $jumlah_pinjam, $kode_barang);
        mysqli_stmt_execute($stmt2);

        echo "<script>
                alert('Data transaksi berhasil ditambahkan');
                window.location.href='index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/data_peminjaman';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data transaksi');</script>";
    }
}
?>


<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Pengajuan Peminjaman Luar Lokasi</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label for="no_peminjaman">No Peminjaman</label>
                    <input type="text" id="no_peminjaman" name="no_peminjaman" 
                           value="<?= generateNoPeminjaman($koneksi); ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label for="tanggal_pinjam">Tanggal Pinjam</label>
                    <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="lokasi_simpan">Lokasi Simpan</label>
                    <select id="lokasi_simpan" name="lokasi_simpan" required class="form-control" onchange="filterBarang()">
                        <option value="">Pilih Lokasi</option>
                        <?php foreach($lokasi_unik as $lok) { ?>
                            <option value="<?= htmlspecialchars($lok); ?>"><?= htmlspecialchars($lok); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label for="nama_barang">Nama Barang</label>
                    <select id="nama_barang" name="nama_barang" required class="form-control" onchange="updateBarang()" disabled>
                        <option value="">Pilih Nama Barang</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="kode_barang">Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="jumlah_tersedia">Jumlah Tersedia</label>
                    <input type="text" id="jumlah_tersedia" readonly class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="jumlah_pinjam">Jumlah Pinjam</label>
                    <input type="number" id="jumlah_pinjam" name="jumlah_pinjam" min="1" required class="form-control" oninput="validasiJumlah()">
                </div>
                <div class="form-group half">
                    <label for="satuan">Satuan</label>
                    <input type="text" id="satuan" name="satuan" readonly required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="lokasi_pinjam">Lokasi Penggunaan</label>
                    <input type="text" id="lokasi_pinjam" name="lokasi_pinjam" required class="form-control">
                </div>
                <div class="form-group half">
                    <label for="nama_peminjam">Nama Peminjam</label>
                    <input type="text" id="nama_peminjam" name="nama_peminjam" required class="form-control">
                </div>
            </div>

            <div class="form-group full">
                <label for="keterangan">Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"></textarea>
            </div>

            <input type="hidden" name="status" value="Pending">

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/peminjaman/data_peminjaman" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
var inventaris = <?= json_encode($data_inventaris); ?>;

function filterBarang() {
    var lokasi = document.getElementById("lokasi_simpan").value;
    var barangSelect = document.getElementById("nama_barang");
    barangSelect.innerHTML = "<option value=''>Pilih Nama Barang</option>";

    if(lokasi === "") {
        barangSelect.disabled = true;
    } else {
        barangSelect.disabled = false;
        inventaris.forEach(function(item) {
            if (item.lokasi_simpan === lokasi) {
                var option = document.createElement("option");
                option.value = item.nama_barang;
                option.text = item.nama_barang;
                option.setAttribute("data-kode", item.kode_barang);
                option.setAttribute("data-jumlah", item.jumlah);
                option.setAttribute("data-satuan", item.satuan);
                barangSelect.appendChild(option);
            }
        });
    }

    // reset detail
    document.getElementById("kode_barang").value = "";
    document.getElementById("jumlah_tersedia").value = "";
    document.getElementById("satuan").value = "";
    document.getElementById("jumlah_pinjam").value = "";
}

function updateBarang() {
    var select = document.getElementById("nama_barang");
    var kode = select.options[select.selectedIndex].getAttribute("data-kode");
    var jumlah = select.options[select.selectedIndex].getAttribute("data-jumlah");
    var satuan = select.options[select.selectedIndex].getAttribute("data-satuan");

    document.getElementById("kode_barang").value = kode;
    document.getElementById("jumlah_tersedia").value = jumlah;
    document.getElementById("satuan").value = satuan;
}

function validasiJumlah() {
    var jumlahPinjam = parseInt(document.getElementById("jumlah_pinjam").value) || 0;
    var jumlahTersedia = parseInt(document.getElementById("jumlah_tersedia").value) || 0;

    if (jumlahPinjam > jumlahTersedia) {
        alert("Jumlah pinjam tidak boleh lebih besar dari jumlah tersedia!");
        document.getElementById("jumlah_pinjam").value = jumlahTersedia;
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