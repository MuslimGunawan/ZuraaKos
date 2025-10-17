-- Update booking status ENUM to include 'terminated'
ALTER TABLE bookings 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'active', 'completed', 'terminated') DEFAULT 'pending';
