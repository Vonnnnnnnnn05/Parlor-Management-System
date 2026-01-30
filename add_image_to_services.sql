-- Add image_path column to services table
-- Run this SQL to update your existing database

USE parlor_system;

ALTER TABLE services 
ADD COLUMN image_path VARCHAR(255) NULL AFTER duration_minutes;

-- Note: After running this SQL, you can start uploading images for your services!
