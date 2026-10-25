<?php
final class Auth
{
    private const HOME = ['student' => '/student', 'registrar' => '/registrar', 'cashier' => '/cashier',
                          'department' => '/department', 'admin' => '/admin'];
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1');
        session_name('ssis_sid');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true,
                                   'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
        session_start();
        if (isset($_SESSION['last']) && time() - $_SESSION['last'] > config('session_timeout')) {
            session_unset(); session_destroy(); session_start();
            flash('error', 'Session timed out. Please sign in again.');
        }
        $_SESSION['last'] = time();
    }

    public static function home(string $role): string { return self::HOME[$role] ?? '/login'; }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            if (!empty($_SESSION['uid'])) {
                $u = DB::one('SELECT id,user_no,role,first_name,last_name,email,department_id,must_change_password,is_active
                              FROM users WHERE id=?', [$_SESSION['uid']]);
                if ($u && $u['is_active']) self::$user = $u; else unset($_SESSION['uid']);
            }
        }
        return self::$user;
    }

    /** @return array [bool ok, string|array result] */
    public static function attempt(string $no, string $pw): array
    {
        $fail = 'Invalid ID number or password.';
        $u = DB::one('SELECT *, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM users WHERE user_no=?', [$no]);
        if (!$u) return [false, $fail];
        if ($u['is_locked']) return [false, 'Too many failed attempts. Try again in 15 minutes.'];
        if (!password_verify($pw, $u['password_hash'])) {
            $n = $u['failed_attempts'] + 1;
            if ($n >= 5) DB::run('UPDATE users SET failed_attempts=0, locked_until=DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id=?', [$u['id']]);
            else DB::run('UPDATE users SET failed_attempts=? WHERE id=?', [$n, $u['id']]);
            return [false, $fail];
        }
        if (!$u['is_active']) return [false, 'This account is disabled. Contact the administrator.'];
        DB::run('UPDATE users SET failed_attempts=0, locked_until=NULL, last_login_at=NOW() WHERE id=?', [$u['id']]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        self::$loaded = false;
        return [true, $u];
    }
}