# Dokumentasi Alur Status Booking

## Status Booking di Sistem Zuraa Kos

### 1. **pending** (Menunggu Persetujuan)
- Status awal saat penghuni membuat booking
- Room status: masih `available`
- Aksi admin: Approve atau Reject

### 2. **approved** (Disetujui - Menunggu Pembayaran)
- Admin sudah menyetujui booking
- Room status: berubah menjadi `occupied`
- Penghuni harus upload bukti pembayaran di halaman Payments
- Aksi penghuni: Upload payment proof
- Aksi admin: Verify payment atau Kick penghuni

### 3. **active** (Aktif - Sudah Bayar)
- Payment sudah diverifikasi admin
- Booking status otomatis berubah ke `active`
- Penghuni sudah resmi tinggal di kamar
- Aksi admin: Kick penghuni jika melanggar

### 4. **rejected** (Ditolak)
- Admin menolak booking
- Room tetap `available`
- Booking tidak bisa diproses lebih lanjut

### 5. **terminated** (Dikeluarkan)
- Admin melakukan kick penghuni
- Room kembali ke status `available`
- Alasan kick tercatat di notes

### 6. **completed** (Selesai)
- Masa sewa berakhir normal
- Penghuni keluar sesuai end_date
- Room kembali ke status `available`

---

## Alur Lengkap

```
[Penghuni Booking] 
    ↓
[pending] → Admin Approve/Reject
    ↓
[approved] → Penghuni Upload Payment
    ↓
[Payment pending] → Admin Verify Payment
    ↓
[active] → Penghuni Tinggal di Kos
    ↓
    ├─→ [completed] (masa sewa habis)
    └─→ [terminated] (admin kick)
```

---

## Fitur Kick Penghuni

### Cara Menggunakan:
1. Buka menu **Admin > Booking**
2. Cari penghuni dengan status `approved` atau `active`
3. Klik tombol **Kick** (merah)
4. Masukkan alasan kick
5. Konfirmasi

### Apa yang Terjadi:
- Booking status → `terminated`
- Room status → `available`
- Alasan kick disimpan di notes dengan timestamp
- Kamar siap untuk penghuni baru

### Catatan:
- Hanya bisa kick penghuni dengan status `approved` atau `active`
- Tidak bisa kick penghuni yang sudah `completed` atau `terminated`
- Alasan kick wajib diisi untuk dokumentasi
