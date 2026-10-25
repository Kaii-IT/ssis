<?php
final class Csrf
{
    public static function token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
    public static function field(): string { return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">'; }
    public static function check(): void
    {
        if (!hash_equals(self::token(), (string)($_POST['_csrf'] ?? ''))) {
            abort(419, 'Your form expired. Go back, refresh the page and try again.');
        }
    }
}