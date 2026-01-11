<?php
// Global configuration settings

// Update these values to match your XAMPP/MySQL setup
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Default fine per late day (in your local currency)
define('FINE_PER_DAY', 5);

// Start session for the whole application
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple role constants
define('ROLE_ADMIN', 'admin');
define('ROLE_LIBRARIAN', 'librarian');
define('ROLE_MEMBER', 'member');


