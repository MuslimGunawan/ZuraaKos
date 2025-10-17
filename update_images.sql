-- Update database dengan path gambar yang benar
-- Pastikan gambar ada di folder Asset/

-- Update rooms dengan gambar default
UPDATE rooms SET image = NULL WHERE id IN (1,2,3,4,5,6);

-- Jika ingin set gambar spesifik per kamar (sesuaikan dengan file yang ada):
-- UPDATE rooms SET image = 'kamar-1.jpg' WHERE id = 1;
-- UPDATE rooms SET image = 'kamar-2.jpg' WHERE id = 2;
-- dst...

-- Cek hasil
SELECT id, room_number, type, image FROM rooms;
