-- Update payment status ENUM to include 'rejected'
ALTER TABLE payments 
MODIFY COLUMN status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending';

-- Check current payments
SELECT id, user_id, amount, status FROM payments;
