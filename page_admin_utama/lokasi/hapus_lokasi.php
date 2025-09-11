<?php
include "config/koneksi.php";

if (!isset($_GET['kode_lokasi'])) {
  echo "<script>
          alert('Kode Lokasi tidak ditemukan.');
          window.location='index_admin_utama.php?page_admin_utama=lokasi/data_lokasi';
        </script>";
  exit;
}

$kode_lokasi = mysqli_real_escape_string($koneksi, $_GET['kode_lokasi']);

// Ambil data lokasi terlebih dahulu
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM lokasi WHERE kode_lokasi = '$kode_lokasi'"));

if (!$data) {
  echo "<script>
          alert('Data Lokasi tidak ditemukan.');
          window.location='index_admin_utama.php?page_admin_utama=lokasi/data_lokasi';
        </script>";
  exit;
}

// Hapus lokasi
$delete = mysqli_query($koneksi, "DELETE FROM lokasi WHERE kode_lokasi = '$kode_lokasi'");

if ($delete) {
  echo "<script>
          alert('Data lokasi berhasil dihapus.');
          window.location='index_admin_utama.php?page_admin_utama=lokasi/data_lokasi';
        </script>";
} else {
  echo "<script>
          alert('Gagal menghapus data lokasi.');
          window.location='index_admin_utama.php?page_admin_utama=lokasi/data_lokasi';
        </script>";
}
?>
