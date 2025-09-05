<?php
include "config/koneksi.php";

if (!isset($_GET['id'])) {
  echo "<script>alert('ID Inventaris tidak ditemukan.'); window.location='index_admin.php?page_admin=data_inventaris/maria/data_inventaris_maria';</script>";
  exit;
}

$id = $_GET['id'];

// Ambil data akun terlebih dahulu
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM inventaris WHERE id = '$id'"));

if (!$data) {
  echo "<script>alert('Data Inventaris tidak ditemukan.'); window.location='index_admin.php?page_admin=data_inventaris/maria/data_inventaris_maria';</script>";
  exit;
}

// Hapus akun
$delete = mysqli_query($koneksi, "DELETE FROM inventaris WHERE id = '$id'");

if ($delete) {
  echo "<script>alert('Data berhasil dihapus.'); window.location='index_admin.php?page_admin=data_inventaris/maria/data_inventaris_maria';</script>";
} else {
  echo "<script>alert('Gagal menghapus akun.'); window.location='index_admin.php?page_admin=data_inventaris/maria/data_inventaris_maria';</script>";
}
?>
