<?php
require_once("config/koneksi.php");

if(isset($_GET['kategori']) && isset($_GET['lokasi'])) {
    $kategori = mysqli_real_escape_string($koneksi, $_GET['kategori']);
    $lokasi   = mysqli_real_escape_string($koneksi, $_GET['lokasi']);

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
        echo "";
        exit;
    }

    $baseKode = $kategoriPrefix[$kategori];

    $query = "SELECT kode_barang 
              FROM inventaris 
              WHERE kategori = ? AND lokasi_simpan = ? 
              ORDER BY kode_barang DESC LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $kategori, $lokasi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        echo $row['kode_barang'] + 1;
    } else {
        echo $baseKode;
    }
}
