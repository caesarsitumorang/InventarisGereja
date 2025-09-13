<?php
include "config/koneksi.php";

if (!isset($_GET['id'])) {
  echo "<script>alert('ID Inventaris tidak ditemukan.'); window.location='index_stasi_fidelis.php?page_stasi_fidelis=data_inventaris/fidelis/data_inventaris_fidelis';</script>";
  exit;
}

$id = $_GET['id'];

// Ambil data akun terlebih dahulu
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM inventaris WHERE id = '$id'"));

if (!$data) {
  echo "<script>alert('Data Inventaris tidak ditemukan.'); window.location='index_stasi_fidelis.php?page_stasi_fidelis=data_inventaris/fidelis/data_inventaris_fidelis';</script>";
  exit;
}

// Hapus akun
$delete = mysqli_query($koneksi, "DELETE FROM inventaris WHERE id = '$id'");

if ($delete) {
  echo "<script>alert('Data berhasil dihapus.'); window.location='index_stasi_fidelis.php?page_stasi_fidelis=data_inventaris/fidelis/data_inventaris_fidelis';</script>";
} else {
  echo "<script>alert('Gagal menghapus akun.'); window.location='index_stasi_fidelis.php?page_stasi_fidelis=data_inventaris/fidelis/data_inventaris_fidelis';</script>";
}
?>
