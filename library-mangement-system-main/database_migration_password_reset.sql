-- Migration: Add password reset columns to users table
-- Run this SQL to add password reset functionality

USE library_system;

ALTER TABLE users 
ADD COLUMN password_reset_token VARCHAR(64) NULL AFTER password_hash,
ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token;

-- Add index for faster token lookups
CREATE INDEX idx_password_reset_token ON users(password_reset_token);

