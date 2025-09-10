<?php
require_once("../../../config/koneksi.php"); 

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

    $query = "SELECT kode_barang 
              FROM inventaris 
              WHERE kategori = ? AND lokasi_simpan = ? 
              ORDER BY kode_barang DESC LIMIT 1";
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

// Ambil kategori & lokasi dari AJAX GET
if(isset($_GET['kategori']) && isset($_GET['lokasi'])){
    $kategori = $_GET['kategori'];
    $lokasi   = $_GET['lokasi'];
    echo generateKodeBarang($koneksi, $kategori, $lokasi);
}
?>
