<?php
require_once("config/koneksi.php");

if(isset($_GET['lokasi_simpan'])) {
    $lokasi = mysqli_real_escape_string($koneksi, $_GET['lokasi_simpan']);
    $query = mysqli_query($koneksi, "SELECT * FROM inventaris WHERE lokasi_simpan='$lokasi' ORDER BY nama_barang ASC");

    echo "<option value=''>Pilih Nama Barang</option>";
    while($row = mysqli_fetch_assoc($query)) {
        echo "<option value='".htmlspecialchars($row['nama_barang'])."' 
                     data-kode='".$row['kode_barang']."' 
                     data-jumlah='".$row['jumlah']."' 
                     data-satuan='".htmlspecialchars($row['satuan'])."'>"
            .htmlspecialchars($row['nama_barang'])."</option>";
    }
}
?>
