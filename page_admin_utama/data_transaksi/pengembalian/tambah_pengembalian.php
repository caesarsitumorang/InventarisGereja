<?php
require_once("config/koneksi.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ambil username session untuk pengembali otomatis
$namaAkun = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';

// Fungsi Generate No Pengembalian
function generateNoPengembalian($koneksi) {
    $prefix = "PG";
    $query = "SELECT no_pengembalian FROM pengembalian 
              WHERE no_pengembalian LIKE '$prefix%' 
              ORDER BY no_pengembalian DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_pengembalian'], 2);
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001";
    }
    return $prefix . $newNumber;
}

// Ambil data peminjaman yang BELUM dikembalikan
$peminjaman = mysqli_query($koneksi, "
    SELECT p.no_peminjaman, p.nama_peminjam, p.kode_barang, p.nama_barang, p.lokasi_simpan, p.jumlah_pinjam, p.satuan 
    FROM peminjaman p
    LEFT JOIN pengembalian pg ON p.no_peminjaman = pg.no_peminjaman
    WHERE p.status != 'Sudah Dikembalikan'
    ORDER BY p.no_peminjaman ASC
");

if (isset($_POST['submit'])) {
    $no_pengembalian = generateNoPengembalian($koneksi);
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $no_peminjaman   = $_POST['no_peminjaman'];
    $lokasi_simpan   = $_POST['lokasi_simpan'];
    $kode_barang     = $_POST['kode_barang'];
    $nama_barang     = $_POST['nama_barang'];
    $jumlah          = (int)$_POST['jumlah'];
    $satuan          = $_POST['satuan'];
    $pengembali      = $_POST['pengembali']; // ambil dari input user, bukan session
    $kondisi_barang  = $_POST['kondisi_barang'];
    $keterangan      = $_POST['keterangan'];

    $query = "INSERT INTO pengembalian 
              (no_pengembalian, tanggal_kembali, no_peminjaman, lokasi_simpan, kode_barang, nama_barang, jumlah, satuan, pengembali, kondisi_barang, keterangan, nama_akun) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssssssisssss",
        $no_pengembalian, $tanggal_kembali, $no_peminjaman, $lokasi_simpan,
        $kode_barang, $nama_barang, $jumlah, $satuan, $pengembali, $kondisi_barang, $keterangan, $namaAkun
    );

    if (mysqli_stmt_execute($stmt)) {
        // Update status peminjaman jadi sudah dikembalikan
        mysqli_query($koneksi, "UPDATE peminjaman SET status='Sudah Dikembalikan' WHERE no_peminjaman='$no_peminjaman'");

        // Update jumlah inventaris sesuai jumlah pengembalian
        $updateInventaris = "UPDATE inventaris SET jumlah = jumlah + ? WHERE kode_barang = ?";
        $stmtInv = mysqli_prepare($koneksi, $updateInventaris);
        mysqli_stmt_bind_param($stmtInv, "is", $jumlah, $kode_barang);
        mysqli_stmt_execute($stmtInv);

        echo "<script>
                alert('Data pengembalian berhasil ditambahkan');
                window.location.href='index_admin_utama.php?page_admin_utama=data_transaksi/pengembalian/data_pengembalian';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data pengembalian');</script>";
    }
}
?>



<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Data Pengembalian</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label>No Pengembalian</label>
                    <input type="text" name="no_pengembalian" value="<?= generateNoPengembalian($koneksi); ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Tanggal Kembali</label>
                    <input type="date" name="tanggal_kembali" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>No Peminjaman</label>
                    <select id="no_peminjaman" name="no_peminjaman" required class="form-control" onchange="updateDataPeminjaman()">
                        <option value="">Pilih No Peminjaman</option>
                        <?php while ($row = mysqli_fetch_assoc($peminjaman)) { ?>
                            <option value="<?= $row['no_peminjaman']; ?>"
                                data-barang="<?= htmlspecialchars($row['nama_barang']); ?>"
                                data-kode="<?= $row['kode_barang']; ?>"
                                data-lokasi="<?= htmlspecialchars($row['lokasi_simpan']); ?>"
                                data-jumlah="<?= $row['jumlah_pinjam']; ?>"
                                data-satuan="<?= htmlspecialchars($row['satuan']); ?>">
                                <?= $row['no_peminjaman']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label>Nama Pengembali</label>
                   <input type="text" id="pengembali" name="pengembali" required class="form-control">

                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" readonly required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Lokasi Simpan</label>
                    <input type="text" id="lokasi_simpan" name="lokasi_simpan" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Jumlah</label>
                    <input type="text" id="jumlah" name="jumlah" readonly required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Satuan</label>
                    <input type="text" id="satuan" name="satuan" readonly required class="form-control">
                </div>
                <div class="form-group half">
                    <label>Kondisi Barang</label>
                    <select id="kondisi_barang" name="kondisi_barang" required class="form-control">
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                    </select>
                </div>
            </div>

            <div class="form-group full">
                <label>Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_admin_utama.php?page_admin_utama=data_transaksi/pengembalian/data_pengembalian" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateDataPeminjaman() {
    var select = document.getElementById('no_peminjaman');
    var selected = select.options[select.selectedIndex];

    document.getElementById('nama_barang').value   = selected.getAttribute('data-barang') || '';
    document.getElementById('kode_barang').value   = selected.getAttribute('data-kode') || '';
    document.getElementById('lokasi_simpan').value = selected.getAttribute('data-lokasi') || '';
    document.getElementById('jumlah').value        = selected.getAttribute('data-jumlah') || '';
    document.getElementById('satuan').value        = selected.getAttribute('data-satuan') || '';
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