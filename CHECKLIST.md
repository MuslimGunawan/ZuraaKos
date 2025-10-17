# ✅ CHECKLIST - Setup Admin Panel Zuraa Kos

## 📋 Yang Harus Dilakukan Sekarang

### **Step 1: Update Database Schema** ⚠️ PENTING!

Database perlu ditambahkan 2 kolom baru untuk fitur Complaints dan Announcements.

**Pilihan A: Via phpMyAdmin (Recommended)**
1. Buka browser, ketik: `http://localhost/phpmyadmin`
2. Login (biasanya username: `root`, password: kosong)
3. Klik database `kos_management` di sidebar kiri
4. Klik tab **SQL** di atas
5. Copy-paste kode SQL ini:

```sql
-- Update schema untuk Complaints dan Announcements
USE kos_management;

-- Add priority and reply to complaints
ALTER TABLE complaints 
ADD COLUMN IF NOT EXISTS priority ENUM('low','medium','high') DEFAULT 'medium' AFTER status,
ADD COLUMN IF NOT EXISTS reply TEXT AFTER description;

-- Add category and is_published to announcements
ALTER TABLE announcements 
ADD COLUMN IF NOT EXISTS category ENUM('info','important','maintenance','event') DEFAULT 'info' AFTER content,
ADD COLUMN IF NOT EXISTS is_published TINYINT(1) DEFAULT 1 AFTER category;

-- Update existing data
UPDATE complaints SET priority = 'medium' WHERE priority IS NULL;
UPDATE announcements SET category = 'info' WHERE category IS NULL;
UPDATE announcements SET is_published = 1 WHERE is_published IS NULL;

SELECT 'Database updated successfully!' as Status;
```

6. Klik tombol **Go** di kanan bawah
7. Kalau muncul pesan "Database updated successfully!" berarti berhasil! ✅

**Pilihan B: Via MySQL Command Line**
```bash
# Buka Command Prompt/Terminal
cd C:\xampp\mysql\bin
mysql -u root -p kos_management

# Paste SQL query di atas
# Ketik exit; untuk keluar
```

---

### **Step 2: Test Admin Panel** 🧪

**2.1. Login Admin**
1. Buka browser: `http://localhost/ZuraaaKos/login.php`
2. Login dengan:
   - **Username:** `admin`
   - **Password:** `admin123` (atau password admin Anda)

**2.2. Test Setiap Halaman:**

| Halaman | URL | Test Case | Status |
|---------|-----|-----------|--------|
| Dashboard | `admin/dashboard.php` | Lihat statistics cards | [ ] |
| Kelola Kamar | `admin/rooms.php` | Klik "Tambah Kamar", upload gambar | [ ] |
| Kelola Booking | `admin/bookings.php` | Approve/Reject booking (jika ada) | [ ] |
| Kelola Pembayaran | `admin/payments.php` | Verify payment (jika ada) | [ ] |
| Kelola Users | `admin/users.php` | Tambah user baru | [ ] |
| Kelola Keluhan | `admin/complaints.php` | Reply complaint (jika ada) | [ ] |
| Pengumuman | `admin/announcements.php` | Buat pengumuman baru | [ ] |

**2.3. Test Fitur-Fitur:**
- [ ] Upload gambar kamar (format: JPG/PNG, max 5MB)
- [ ] Approve booking (pastikan room status berubah ke "occupied")
- [ ] Change user role (Admin ↔ Penghuni)
- [ ] Reply complaint (pastikan auto-resolve)
- [ ] Publish/Unpublish announcement
- [ ] Filter & search di setiap halaman

---

### **Step 3: Create Test Data** 📊 (Optional)

Jika belum ada data untuk testing, buat dummy data:

**3.1. Add Test User (Penghuni)**
- Masuk ke `admin/users.php`
- Klik "Tambah User"
- Isi form:
  - Username: `testuser`
  - Email: `test@example.com`
  - Full Name: `Test User`
  - Phone: `08123456789`
  - Role: `Penghuni`
  - Password: `test123`
- Klik "Simpan"

**3.2. Add Test Room**
- Masuk ke `admin/rooms.php`
- Klik "Tambah Kamar"
- Isi form kamar baru
- Upload gambar kamar
- Klik "Simpan"

**3.3. Create Test Announcement**
- Masuk ke `admin/announcements.php`
- Klik "Buat Pengumuman"
- Isi:
  - Judul: `Pembayaran Bulan Februari`
  - Kategori: `Important`
  - Konten: `Harap segera melakukan pembayaran...`
  - Centang "Publish sekarang"
- Klik "Simpan"

---

### **Step 4: Security Check** 🔒

Pastikan security sudah aktif:

- [ ] File `.htaccess` ada di folder `config/`, `includes/`, `uploads/`
- [ ] Session timeout bekerja (coba idle 30 menit, harus auto-logout)
- [ ] CSRF token active (inspect element form, harus ada input hidden `csrf_token`)
- [ ] Password di-hash dengan Argon2ID (cek di database, harus ada `$argon2id$`)
- [ ] Rate limiting login (coba salah password 5x, harus diblock 15 menit)

**Test Security:**
```php
// Buka admin/dashboard.php
// View Page Source (Ctrl+U)
// Cari: csrf_token
// Harus ada: <input type="hidden" name="csrf_token" value="...">
```

---

### **Step 5: Performance Check** ⚡

- [ ] Halaman load < 2 detik (test dengan Chrome DevTools)
- [ ] Database connection menggunakan Singleton (1 koneksi saja)
- [ ] Gambar ter-compress (max 5MB per file)
- [ ] No console errors (tekan F12, cek Console tab)

---

## 🐛 Troubleshooting

### **Problem 1: SQL Error - Column Already Exists**
```
Error: Duplicate column name 'priority'
```
**Solution:** Column sudah ada, skip error ini (aman).

---

### **Problem 2: CSRF Token Invalid**
```
Error: Token keamanan tidak valid
```
**Solution:** 
- Clear browser cache (Ctrl+Shift+Del)
- Logout, lalu login lagi
- Pastikan session PHP aktif

---

### **Problem 3: Image Upload Failed**
```
Error: Gagal mengupload gambar
```
**Solution:**
- Cek folder `uploads/` exists dan writable (chmod 755)
- Ukuran file < 5MB
- Format: JPG, JPEG, PNG only
- File gambar tidak corrupt

---

### **Problem 4: Database Connection Error**
```
Error: Connection failed: Access denied
```
**Solution:**
- Cek `config/database.php`:
  - DB_HOST = 'localhost'
  - DB_USER = 'root'
  - DB_PASS = '' (kosong atau password MySQL Anda)
  - DB_NAME = 'kos_management'
- Pastikan XAMPP Apache & MySQL running

---

### **Problem 5: Page Not Found (404)**
```
Not Found: The requested URL was not found
```
**Solution:**
- Pastikan struktur folder benar:
  ```
  ZuraaaKos/
  ├── admin/
  │   ├── dashboard.php
  │   ├── rooms.php
  │   ├── bookings.php
  │   ├── payments.php
  │   ├── users.php ← NEW
  │   ├── complaints.php ← NEW
  │   └── announcements.php ← NEW
  ```
- URL harus: `http://localhost/ZuraaaKos/admin/dashboard.php`

---

## 📞 Quick Commands

### **Check MySQL Status:**
```bash
# Windows
cd C:\xampp
xampp-control.exe

# Pastikan MySQL hijau (running)
```

### **Restart Apache & MySQL:**
1. Buka XAMPP Control Panel
2. Klik "Stop" pada Apache & MySQL
3. Tunggu 5 detik
4. Klik "Start" pada Apache & MySQL

### **Check PHP Errors:**
```bash
# Buka file
C:\xampp\apache\logs\error.log

# Cari error terakhir
```

---

## ✅ Final Checklist

Before going to production:

- [ ] Database schema updated (Step 1)
- [ ] All 7 admin pages tested (Step 2)
- [ ] Test data created (Step 3)
- [ ] Security features verified (Step 4)
- [ ] Performance checked (Step 5)
- [ ] No console errors
- [ ] No PHP errors in log
- [ ] Images uploading correctly
- [ ] CSRF tokens working
- [ ] Session timeout working

---

## 🎉 Setelah Semua Checklist Complete:

**Admin Panel Status: PRODUCTION-READY! ✅**

Files yang berhasil dibuat:
- ✅ `admin/users.php` (23.4 KB)
- ✅ `admin/complaints.php` (27.5 KB)
- ✅ `admin/announcements.php` (24.2 KB)
- ✅ `setup/update_schema.sql` (Database updates)
- ✅ `COMPLETION_SUMMARY.md` (Documentation)
- ✅ `CHECKLIST.md` (This file!)

**Total: 6 files baru, ~75 KB kode production-ready!**

---

**Next:** Kalau ada error atau pertanyaan, refer ke `COMPLETION_SUMMARY.md` atau tanya saya! 😊
