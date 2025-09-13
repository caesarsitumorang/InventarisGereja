<?php
require_once("config/koneksi.php");

// Ambil username session untuk pengembali otomatis
$namaAkun = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
// Fungsi generate nomor perbaikan otomatis
function generateNoPerbaikan($koneksi) {
    $prefix = "PB";
    $query = "SELECT no_perbaikan FROM perbaikan 
              WHERE no_perbaikan LIKE '$prefix%' 
              ORDER BY no_perbaikan DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = (int)substr($row['no_perbaikan'], 2);
        $newNumber = str_pad($lastNumber + 1, 7, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0000001";
    }
    return $prefix . $newNumber;
}

// Ambil kerusakan yang belum ada di perbaikan
$kerusakan = mysqli_query($koneksi, "
    SELECT * FROM kerusakan 
    WHERE no_kerusakan NOT IN (SELECT no_kerusakan FROM perbaikan) 
    ORDER BY no_kerusakan ASC
");

if (isset($_POST['submit'])) {
    $no_perbaikan = generateNoPerbaikan($koneksi);
    $tanggal_perbaikan = $_POST['tanggal_perbaikan'];
    $no_kerusakan = $_POST['no_kerusakan'];
    $biaya_perbaikan = $_POST['biaya_perbaikan'];
    $keterangan = $_POST['keterangan'];

    // Cek apakah no_kerusakan ada dan belum diperbaiki
    $cek = mysqli_prepare($koneksi, "SELECT no_kerusakan FROM kerusakan 
                                      WHERE no_kerusakan = ? 
                                      AND no_kerusakan NOT IN (SELECT no_kerusakan FROM perbaikan)");
    mysqli_stmt_bind_param($cek, "s", $no_kerusakan);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) == 0) {
        echo "<script>
                alert('Nomor kerusakan tidak valid atau sudah diperbaiki!');
                window.history.back();
              </script>";
        exit;
    }

    // Ambil data kerusakan
    $sqlKerusakan = "SELECT * FROM kerusakan WHERE no_kerusakan = ?";
    $stmtKerusakan = mysqli_prepare($koneksi, $sqlKerusakan);
    mysqli_stmt_bind_param($stmtKerusakan, "s", $no_kerusakan);
    mysqli_stmt_execute($stmtKerusakan);
    $resultKerusakan = mysqli_stmt_get_result($stmtKerusakan);

    if ($row = mysqli_fetch_assoc($resultKerusakan)) {
        $kode_barang   = $row['kode_barang'];
        $nama_barang   = $row['nama_barang'];
        $lokasi_simpan = $row['lokasi_simpan'];
        $jumlah        = $row['jumlah'];
        $satuan        = $row['satuan'];
        $ket_rusak     = $row['keterangan'];

        // Insert ke tabel perbaikan
        $query = "INSERT INTO perbaikan 
                  (no_perbaikan, tanggal_perbaikan, no_kerusakan, kode_barang, nama_barang, lokasi_simpan, jumlah, satuan, biaya_perbaikan, keterangan, nama_akun) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?  , ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssssssisiss", 
            $no_perbaikan, 
            $tanggal_perbaikan, 
            $no_kerusakan, 
            $kode_barang, 
            $nama_barang, 
            $lokasi_simpan, 
            $jumlah,    
            $satuan, 
            $biaya_perbaikan, 
            $keterangan ,
            $namaAkun
        );
        if (mysqli_stmt_execute($stmt)) {

            $updateInventaris = "UPDATE inventaris SET jumlah = jumlah + ? WHERE kode_barang = ?";
            $stmtInv = mysqli_prepare($koneksi, $updateInventaris);
            mysqli_stmt_bind_param($stmtInv, "is", $jumlah, $kode_barang);
            mysqli_stmt_execute($stmtInv);

            echo "<script>
                    alert('Data perbaikan berhasil ditambahkan');
                    window.location.href='index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/perbaikan/data_perbaikan';
                  </script>";
        } else {
            echo "<script>alert('Gagal menambahkan data perbaikan');</script>";
        }
    } else {
        echo "<script>alert('Nomor kerusakan tidak ditemukan');</script>";
    }
}
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Tambah Data Perbaikan</h2>
        </div>
        
        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group half">
                    <label>No Perbaikan</label>
                    <input type="text" name="no_perbaikan" value="<?= generateNoPerbaikan($koneksi); ?>" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Tanggal Perbaikan</label>
                    <input type="date" name="tanggal_perbaikan" required class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>No Kerusakan</label>
                    <select id="no_kerusakan" name="no_kerusakan" required class="form-control" onchange="updateDataKerusakan()">
                        <option value="">Pilih No Kerusakan</option>
                        <?php while ($row = mysqli_fetch_assoc($kerusakan)) { ?>
                            <option value="<?= htmlspecialchars($row['no_kerusakan']); ?>"
                                data-kode="<?= $row['kode_barang']; ?>"
                                data-nama="<?= htmlspecialchars($row['nama_barang']); ?>"
                                data-lokasi="<?= htmlspecialchars($row['lokasi_simpan']); ?>"
                                data-satuan="<?= htmlspecialchars($row['satuan']); ?>"
                                data-jumlah="<?= $row['jumlah']; ?>"
                                data-ket="<?= htmlspecialchars($row['keterangan']); ?>">
                                <?= $row['no_kerusakan']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group half">
                    <label>Kode Barang</label>
                    <input type="text" id="kode_barang" readonly class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Nama Barang</label>
                    <input type="text" id="nama_barang" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Lokasi Simpan</label>
                    <input type="text" id="lokasi_simpan" readonly class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Jumlah</label>
                    <input type="text" id="jumlah" readonly class="form-control">
                </div>
                <div class="form-group half">
                    <label>Satuan</label>
                    <input type="text" id="satuan" readonly class="form-control">
                </div>
            </div>

            <div class="form-group full">
                <label>Keterangan Kerusakan</label>
                <textarea id="ket_rusak" readonly class="form-control"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>Biaya Perbaikan</label>
                    <input type="number" name="biaya_perbaikan" min="0" required class="form-control">
                </div>
            </div>

            <div class="form-group full">
                <label>Keterangan Tambahan</label>
                <textarea name="keterangan" class="form-control"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Simpan</button>
                <a href="index_stasi_fidelis.php?page_stasi_fidelis=data_transaksi/perbaikan/data_perbaikan" class="btn-cancel">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateDataKerusakan() {
    var select = document.getElementById('no_kerusakan');
    var selected = select.options[select.selectedIndex];

    document.getElementById('kode_barang').value   = selected.getAttribute('data-kode') || '';
    document.getElementById('nama_barang').value   = selected.getAttribute('data-nama') || '';
    document.getElementById('lokasi_simpan').value = selected.getAttribute('data-lokasi') || '';
    document.getElementById('satuan').value        = selected.getAttribute('data-satuan') || '';
    document.getElementById('jumlah').value        = selected.getAttribute('data-jumlah') || '';
    document.getElementById('ket_rusak').value     = selected.getAttribute('data-ket') || '';
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