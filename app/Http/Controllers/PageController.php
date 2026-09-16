<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function map(): View
    {
        return $this->placeholder('Peta Real-time', route('map'), 'Peta hewan real-time dengan Leaflet.js akan diimplementasikan di Sesi 5.');
    }

    public function animals(): View
    {
        return $this->placeholder('Hewan', route('animals.index'), 'Modul CRUD hewan ternak akan diimplementasikan di Sesi berikutnya.');
    }

    public function devices(): View
    {
        return $this->placeholder('Perangkat', route('devices.index'), 'Modul CRUD perangkat ESP32 akan diimplementasikan di Sesi berikutnya.');
    }

    public function fences(): View
    {
        return $this->placeholder('Virtual Fence', route('fences.index'), 'Modul CRUD pagar virtual (polygon) akan diimplementasikan di Sesi berikutnya.');
    }

    public function calibration(): View
    {
        return $this->placeholder('Kalibrasi Fence', route('calibration.index'), 'Fitur kalibrasi fence via GPS smartphone akan diimplementasikan di sesi khusus kalibrasi.');
    }

    public function logs(): View
    {
        return $this->placeholder('Log Pelacakan', route('logs.index'), 'Riwayat lokasi dan log perangkat akan diimplementasikan di Sesi berikutnya.');
    }

    public function alerts(): View
    {
        return $this->placeholder('Alert', route('alerts.index'), 'Daftar lengkap alert dan riwayat akan diimplementasikan di sesi alert & notifikasi.');
    }

    public function alertSettings(): View
    {
        return $this->placeholder('Preferensi Notifikasi', route('alerts.settings'), 'Pengaturan channel notifikasi (Email, Telegram, WhatsApp) akan diimplementasikan di sesi alert & notifikasi.');
    }

    public function reports(): View
    {
        return $this->placeholder('Laporan', route('reports.index'), 'Modul laporan & export PDF/Excel akan diimplementasikan di Sesi berikutnya.');
    }

    public function settings(): View
    {
        return $this->placeholder('Pengaturan', route('settings.index'), 'Pengaturan aplikasi, farm, dan profil akan diimplementasikan di Sesi berikutnya.');
    }

    private function placeholder(string $title, string $url, string $description): View
    {
        return view('pages.placeholder', [
            'pageTitle' => $title,
            'url' => $url,
            'description' => $description,
        ]);
    }
}
