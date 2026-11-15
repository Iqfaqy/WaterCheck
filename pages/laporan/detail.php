<?php
/**
 * WaterCheck - Pratinjau Dokumen Laporan
 * File: pages/laporan/detail.php
 */

$pageTitle = 'Pratinjau Laporan';
$activeMenu = 'laporan';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

$tipe = $_GET['tipe'] ?? 'pemeriksaan';
$id   = (int)($_GET['id'] ?? 0);

if ($tipe === 'kalkulasi') {
    redirect(BASE_URL . "pages/laporan/cetak.php?tipe=kalkulasi&id={$id}&html=1");
} else {
    redirect(BASE_URL . "pages/pemeriksaan/detail.php?id={$id}");
}
