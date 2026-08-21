<?php
/**
 * Shared helper functions
 * Smart Belonging System for Campus
 */

if (!function_exists('base_url')) {
    /**
     * Builds the app's own base URL (protocol + host + install path) from the
     * current request, so links in emails work regardless of whether the app
     * is running on localhost, XAMPP, or a real domain.
     *
     * Only safe to call from a script one level deep (e.g. process/foo.php)
     * or from the project root -- pass $depth = 1 when calling from a
     * subfolder like process/ so the install path is resolved correctly.
     */
    function base_url(int $depth = 0): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        for ($i = 0; $i < $depth; $i++) {
            $scriptDir = dirname($scriptDir);
        }
        $scriptDir = rtrim($scriptDir, '/');

        return $protocol . '://' . $host . $scriptDir;
    }
}
