# 📸 PANDUAN UPLOAD GAMBAR KOS

## 🎯 Lokasi File Gambar

Anda telah mengupload gambar kos di:
```
Asset/
├── kos-depan.jpg    (Tampak depan kos)
├── kamar-1.jpg      (Kamar tipe single)
└── kamar-2.jpg      (Kamar tipe double/interior)
```

## ✅ Update yang Sudah Diterapkan

### 1. **Halaman Landing (index.php)**
- ✅ Menampilkan **Gallery Section** dengan foto kos-depan.jpg
- ✅ Foto kamar-1.jpg dan kamar-2.jpg di gallery
- ✅ Hover effect dan overlay pada gallery
- ✅ Fallback ke gambar default jika file tidak ditemukan

### 2. **Halaman Rooms (rooms.php)**
- ✅ Prioritas menampilkan gambar dari database
- ✅ Fallback ke Asset/kamar-1.jpg jika tidak ada gambar
- ✅ Security: validasi file existence sebelum display
- ✅ Lazy loading untuk performa

### 3. **Security Gambar**
- ✅ File validation dengan `file_exists()`
- ✅ XSS prevention dengan `cleanOutput()`
- ✅ Lazy loading untuk efisiensi
- ✅ Alt text yang descriptive

---

## 🔧 Cara Menambahkan Gambar Baru

### Option 1: Upload via Admin Panel (Recommended)
Ketika admin panel sudah ready, upload via:
1. Login sebagai admin
2. Kelola Kamar → Edit Kamar
3. Upload gambar baru
4. Gambar akan tersimpan di `uploads/` dengan nama random untuk security

### Option 2: Manual Upload
1. Upload gambar ke folder `Asset/` atau `uploads/`
2. Nama file harus jelas: `kamar-1.jpg`, `kamar-2.jpg`, dll
3. Update database:
   ```sql
   UPDATE rooms SET image = 'kamar-1.jpg' WHERE room_number = 'A101';
   ```

---

## 📊 Database Schema untuk Gambar

```sql
-- Kolom image di tabel rooms
image VARCHAR(255) DEFAULT NULL

-- Contoh data:
-- NULL = akan pakai gambar default dari Asset/kamar-1.jpg
-- 'abc123.jpg' = akan pakai gambar dari uploads/abc123.jpg
```

---

## 🎨 Format Gambar yang Direkomendasikan

### Ukuran Optimal:
- **Tampak Depan Kos**: 1200x800px (landscape)
- **Foto Kamar**: 800x600px (landscape)
- **Thumbnail**: 400x300px (landscape)

### Format File:
- ✅ JPG/JPEG (recommended untuk foto)
- ✅ PNG (untuk gambar dengan transparansi)
- ✅ WebP (untuk performa terbaik)
- ❌ GIF (tidak recommended untuk foto)

### Ukuran File:
- Maximum: 5MB (sudah diset di security)
- Recommended: 200KB - 1MB (setelah kompresi)

---

## 🔒 Security Features

### Upload Validation:
```php
✅ MIME type verification
✅ File extension whitelist (jpg, jpeg, png, gif)
✅ Image verification (getimagesize)
✅ File size limit (5MB max)
✅ Random filename generation
✅ Script execution prevention
```

### Directory Protection:
```
uploads/.htaccess    → Prevent PHP execution
Asset/               → Protected from direct listing
```

---

## 🖼️ Contoh Penggunaan

### Di Index.php:
```php
<!-- Gallery dengan foto asli -->
<img src="Asset/kos-depan.jpg" alt="Tampak Depan Kos">
<img src="Asset/kamar-1.jpg" alt="Kamar Single">
<img src="Asset/kamar-2.jpg" alt="Interior Kamar">
```

### Di Rooms.php:
```php
<!-- Prioritas: DB → Asset → Placeholder -->
<?php if ($room['image'] && file_exists('uploads/' . $room['image'])): ?>
    <img src="uploads/<?= cleanOutput($room['image']) ?>">
<?php elseif (file_exists('Asset/kamar-1.jpg')): ?>
    <img src="Asset/kamar-1.jpg">
<?php else: ?>
    <img src="https://via.placeholder.com/400x300">
<?php endif; ?>
```

---

## 📝 Checklist Upload Gambar

- [x] Folder Asset/ sudah ada
- [x] Gambar kos-depan.jpg, kamar-1.jpg, kamar-2.jpg sudah diupload
- [x] Gallery section di index.php sudah menampilkan gambar
- [x] Rooms.php sudah fallback ke gambar Asset/
- [x] Security validation aktif
- [x] Lazy loading enabled
- [ ] Admin panel untuk upload (coming soon)
- [ ] Image compression/optimization (coming soon)
- [ ] Multiple images per room (coming soon)

---

## 🚀 Next Steps

### Untuk Menambah Gambar Lebih Banyak:

1. **Upload ke Asset/**
   ```
   Asset/
   ├── kos-depan.jpg
   ├── kos-belakang.jpg
   ├── parkiran.jpg
   ├── kamar-1.jpg
   ├── kamar-2.jpg
   ├── kamar-3.jpg
   ├── kamar-bathroom.jpg
   └── kamar-living.jpg
   ```

2. **Update Database per Kamar**
   ```sql
   UPDATE rooms SET image = 'kamar-1.jpg' WHERE room_number = 'A101';
   UPDATE rooms SET image = 'kamar-2.jpg' WHERE room_number = 'A102';
   UPDATE rooms SET image = 'kamar-3.jpg' WHERE room_number = 'A103';
   ```

3. **Atau Biarkan NULL** (akan otomatis pakai Asset/kamar-1.jpg)

---

## 🛠️ Troubleshooting

### Gambar Tidak Muncul?

1. **Cek File Exists**
   ```php
   if (file_exists('Asset/kos-depan.jpg')) {
       echo "File ada!";
   } else {
       echo "File tidak ditemukan!";
   }
   ```

2. **Cek Permission**
   ```bash
   # Di terminal:
   ls -la Asset/
   # Pastikan readable (644 atau 755)
   ```

3. **Cek Path**
   - Pastikan path relatif benar
   - Index.php: `Asset/kos-depan.jpg`
   - Rooms.php: `Asset/kamar-1.jpg`

4. **Cek Extension**
   - Pastikan ekstensi lowercase: `.jpg` bukan `.JPG`
   - Atau rename: `kos-depan.JPG` → `kos-depan.jpg`

---

## 📞 Support

Jika ada masalah dengan gambar:
1. Pastikan file ada di folder yang benar
2. Cek size file tidak lebih dari 5MB
3. Cek format file (JPG/PNG/GIF)
4. Refresh browser (Ctrl+F5)

---

**Last Updated**: October 17, 2025
**Status**: ✅ Ready to Use
