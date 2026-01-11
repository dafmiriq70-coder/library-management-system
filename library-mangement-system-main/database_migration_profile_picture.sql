-- Migration: Add profile_picture column to users table
-- Run this SQL to add profile picture functionality

USE library_system;

ALTER TABLE users 
ADD COLUMN profile_picture VARCHAR(255) NULL AFTER email;

