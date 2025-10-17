# 🧪 TESTING CHECKLIST - Zuraa Kos System

## ⚠️ PENTING: Update Database Dulu!

Sebelum testing, jalankan query ini di phpMyAdmin:

```sql
-- Update booking status ENUM to include 'terminated'
ALTER TABLE bookings 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'active', 'completed', 'terminated') DEFAULT 'pending';
```

---

## 🔍 Bug Fixes Testing

### 1. Modal "Getar" di Dashboard - TEST NOW ✅

**Admin Dashboard:**
- [ ] Login sebagai admin
- [ ] Buka `admin/dashboard.php`
- [ ] Klik icon mata (👁️) di tabel Recent Bookings
- [ ] **Expected:** Modal muncul dan TETAP terbuka
- [ ] **Should NOT:** Getar/kelap-kelip/langsung hilang
- [ ] Tutup modal dengan tombol X atau "Tutup"
- [ ] Coba buka modal booking yang berbeda
- [ ] **Expected:** Semua modal berfungsi normal

**Admin Bookings:**
- [ ] Buka `admin/bookings.php`
- [ ] Klik tombol "Detail" (icon mata)
- [ ] **Expected:** Modal detail terbuka dan stabil
- [ ] Coba klik "Kick" untuk booking active
- [ ] **Expected:** Modal kick terbuka normal
- [ ] Test beberapa booking berbeda

---

## 🆕 New Features Testing

### 2. My Bookings Page (Penghuni) - NEW FEATURE ✅

**Setup Test Data:**
- [ ] Login sebagai admin
- [ ] Buat beberapa booking dengan status berbeda:
  - 1 booking `pending`
  - 1 booking `approved`
  - 1 booking `active`
  - 1 booking `rejected`

**Test as Penghuni:**
- [ ] Logout, login sebagai penghuni
- [ ] Klik menu "Booking Saya" di sidebar
- [ ] **Expected:** Halaman `my_bookings.php` terbuka

**Statistics Cards:**
- [ ] Lihat card "Total Booking" - angka benar?
- [ ] Lihat card "Aktif" - hitung approved + active
- [ ] Lihat card "Menunggu" - hitung pending

**Filter Buttons:**
- [ ] Klik filter "Semua" - tampil semua booking
- [ ] Klik filter "Pending" - hanya pending
- [ ] Klik filter "Approved" - hanya approved
- [ ] Klik filter "Active" - hanya active
- [ ] Klik filter "Rejected" - hanya rejected
- [ ] Klik filter "Completed" - hanya completed

**Booking Table:**
- [ ] Tabel tampil dengan kolom lengkap
- [ ] Status badge warna sesuai:
  - Pending = kuning (warning)
  - Approved = hijau (success)
  - Active = biru (primary)
  - Rejected = merah (danger)
  - Completed = abu (info)
- [ ] Tombol "Detail" ada di setiap row
- [ ] Tombol "Upload Bayar" muncul untuk status `approved`

**Modal Detail:**
- [ ] Klik "Detail" pada booking pending
- [ ] **Expected:** Modal terbuka, info lengkap
- [ ] **Expected:** Alert kuning "Menunggu persetujuan admin"
- [ ] Tutup modal

- [ ] Klik "Detail" pada booking approved
- [ ] **Expected:** Alert hijau "Silakan upload pembayaran"
- [ ] **Expected:** Link ke halaman Pembayaran
- [ ] Tutup modal

- [ ] Klik "Detail" pada booking active
- [ ] **Expected:** Alert biru "Selamat! Sudah resmi penghuni"
- [ ] **Expected:** Tombol "Lihat Kamar" di footer
- [ ] Tutup modal

- [ ] Klik "Detail" pada booking rejected
- [ ] **Expected:** Alert merah "Booking ditolak"
- [ ] Tutup modal

**No Booking State:**
- [ ] Hapus semua booking penghuni (via admin)
- [ ] Refresh `my_bookings.php`
- [ ] **Expected:** Icon inbox + "Belum ada booking"
- [ ] **Expected:** Tombol "Cari Kamar"
- [ ] Klik tombol, redirect ke `../rooms.php`

---

### 3. Sidebar Navigation - ALL PAGES ✅

**Test di Semua Halaman Penghuni:**

| Page | Menu "Booking Saya" Ada? | Active State Benar? |
|------|--------------------------|---------------------|
| `dashboard.php` | [ ] | [ ] |
| `my_room.php` | [ ] | [ ] |
| `my_bookings.php` | [ ] | [ ] |
| `payments.php` | [ ] | [ ] |
| `complaints.php` | [ ] | [ ] |
| `announcements.php` | [ ] | [ ] |

**Urutan Menu Harus:**
1. Dashboard
2. Kamar Saya
3. Booking Saya ← **NEW**
4. Pembayaran
5. Keluhan
6. Pengumuman
---
7. Beranda
8. Logout

---

### 4. Payment Auto-Activate Booking ✅

**Setup:**
- [ ] Buat booking baru, approve sebagai admin
- [ ] Login sebagai penghuni
- [ ] Upload bukti pembayaran
- [ ] Logout, login sebagai admin

**Test:**
- [ ] Buka `admin/payments.php`
- [ ] Klik "Verify" pada payment tadi
- [ ] **Expected:** Success message "Pembayaran berhasil diverifikasi dan booking diaktifkan!"
- [ ] Buka `admin/bookings.php`
- [ ] **Expected:** Status booking berubah jadi `active` (biru)
- [ ] Login sebagai penghuni
- [ ] Buka "Booking Saya"
- [ ] **Expected:** Status booking = Active

---

### 5. Kick Penghuni Feature ✅

**Setup:**
- [ ] Pastikan ada booking dengan status `active` atau `approved`
- [ ] Login sebagai admin
- [ ] Buka `admin/bookings.php`

**Test:**
- [ ] Cari booking dengan status `active`
- [ ] **Expected:** Ada tombol merah "Kick"
- [ ] Klik tombol "Kick"
- [ ] **Expected:** Modal merah terbuka
- [ ] **Expected:** Form textarea "Alasan Kick"
- [ ] Ketik alasan: "Melanggar peraturan"
- [ ] Klik "Kick Penghuni"
- [ ] **Expected:** Konfirmasi pop-up browser
- [ ] Konfirmasi "OK"
- [ ] **Expected:** Success message "Penghuni berhasil dikeluarkan"
- [ ] **Expected:** Status booking berubah jadi `terminated`
- [ ] **Expected:** Badge abu-abu (secondary)

**Check Room Status:**
- [ ] Buka `admin/rooms.php`
- [ ] Cari kamar yang tadi di-kick
- [ ] **Expected:** Room status kembali `available`

**Check Notes:**
- [ ] Buka detail booking yang di-kick
- [ ] **Expected:** Notes berisi:
  - "[KICKED] Melanggar peraturan - [timestamp]"

---

### 6. Announcements Title Display ✅

**Test:**
- [ ] Login sebagai admin
- [ ] Buat announcement baru dengan judul jelas
- [ ] Set target_role = "penghuni"
- [ ] Publish
- [ ] Login sebagai penghuni
- [ ] Buka `penghuni/announcements.php`

**Check:**
- [ ] **Expected:** Header card biru dengan judul BESAR
- [ ] **Expected:** Icon megaphone di sebelah judul
- [ ] **Expected:** Badge target role (info putih)
- [ ] **Expected:** Footer dengan icon person-badge dan calendar
- [ ] **Expected:** Content dengan spacing bagus

---

## 🔒 Security Testing

### CSRF Protection:
- [ ] Coba submit form tanpa token
- [ ] **Expected:** Error "Token keamanan tidak valid"

### SQL Injection:
- [ ] Test input `' OR '1'='1` di login
- [ ] **Expected:** Login gagal atau sanitized

### XSS Prevention:
- [ ] Test input `<script>alert('XSS')</script>` di complaint
- [ ] **Expected:** Tersimpan sebagai text, tidak execute

---

## 📊 Data Consistency Testing

### Payment-Booking Sync:
- [ ] Verify payment → booking jadi active? ✅
- [ ] Kick penghuni → room jadi available? ✅
- [ ] Approve booking → room jadi occupied? ✅

### Status Flow:
```
pending → approved → (payment) → active → completed/terminated
```
- [ ] Test full flow dari awal sampai akhir

---

## 🎨 UI/UX Testing

### Responsive:
- [ ] Buka di mobile (F12 → Toggle Device Toolbar)
- [ ] Sidebar collapse?
- [ ] Table scroll horizontal?
- [ ] Modal fit screen?

### Browser Compatibility:
- [ ] Chrome ✅
- [ ] Firefox ✅
- [ ] Edge ✅

---

## ✅ Final Checklist

- [ ] Semua modal berfungsi tanpa getar
- [ ] My Bookings page accessible
- [ ] Sidebar konsisten di semua halaman
- [ ] Payment auto-activate booking
- [ ] Kick penghuni bekerja
- [ ] Announcements title jelas
- [ ] No console errors (F12)
- [ ] No PHP errors di halaman
- [ ] Database updated (terminated status)

---

## 🐛 Bug Reporting Template

Jika menemukan bug, laporkan dengan format:

```
**Page:** admin/dashboard.php
**Action:** Klik icon mata
**Expected:** Modal terbuka
**Actual:** Modal getar/hilang
**Browser:** Chrome 120
**Screenshot:** [paste here]
```

---

## 🎉 Testing Complete?

Jika semua checklist ✅, sistem ready for production!
