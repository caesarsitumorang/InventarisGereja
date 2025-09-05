<?php
include "config/koneksi.php";

if (!isset($_GET['id'])) {
  echo "<script>alert('ID akun tidak ditemukan.'); window.location='index_admin.php?page_admin=data_akun/data_akun';</script>";
  exit;
}

$id = $_GET['id'];

// Ambil data akun terlebih dahulu
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengguna WHERE id_akun = '$id'"));

if (!$data) {
  echo "<script>alert('Data akun tidak ditemukan.'); window.location='index_admin.php?page_admin=data_akun/data_akun';</script>";
  exit;
}

// Jika ada foto, hapus juga dari folder upload
if (!empty($data['photo'])) {
    $file = "upload/" . $data['photo'];
    if (file_exists($file)) {
        unlink($file);
    }
}

// Hapus akun
$delete = mysqli_query($koneksi, "DELETE FROM pengguna WHERE id_akun = '$id'");

if ($delete) {
  echo "<script>alert('Akun berhasil dihapus.'); window.location='index_admin.php?page_admin=data_akun/data_akun';</script>";
} else {
  echo "<script>alert('Gagal menghapus akun.'); window.location='index_admin.php?page_admin=data_akun/data_akun';</script>";
}
?>
