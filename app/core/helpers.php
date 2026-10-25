<?php
function config(string $k) { return $GLOBALS['config'][$k] ?? null; }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function base_path(): string { return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'); }
function url(string $p = ''): string { return base_path() . '/' . ltrim($p, '/'); }
function redirect(string $p): never { header('Location: ' . url($p)); exit; }
function current_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $b = base_path();
    if ($b !== '' && str_starts_with($p, $b)) $p = substr($p, strlen($b));
    return '/' . trim($p, '/');
}
function flash(string $t, string $m): void { $_SESSION['flash'][] = [$t, $m]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function post(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }
function with_old(array $a): void { $_SESSION['old'] = $a; }
function old(string $k, $d = '') { return $_SESSION['old'][$k] ?? $d; }
function csrf_field(): string { return Csrf::field(); }
function money($n): string { return '₱' . number_format((float)$n, 2); }
function need_term(): array
{
    $t = DB::one('SELECT * FROM terms WHERE is_current=1 LIMIT 1');
    if (!$t) abort(404, 'No current academic term is set. Contact the administrator.');
    return $t;
}

function view(string $name, array $data = [], string $layout = 'main'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP . "/views/$name.php";
    $content = ob_get_clean();
    require APP . "/views/layouts/$layout.php";
    unset($_SESSION['old']);
}
function abort(int $code, string $msg = ''): never
{
    http_response_code($code);
    $titles = [403 => 'Access denied', 404 => 'Page not found', 419 => 'Session expired'];
    view('errors/error', ['code' => $code, 'title' => $titles[$code] ?? 'Error', 'msg' => $msg]);
    exit;
}
function audit(string $action, string $entity = '', ?int $id = null, string $details = ''): void
{
    $u = Auth::user();
    DB::run('INSERT INTO audit_logs(user_id,action,entity,entity_id,details,ip) VALUES(?,?,?,?,?,?)',
        [$u['id'] ?? null, $action, $entity ?: null, $id, mb_substr($details, 0, 255) ?: null, $_SERVER['REMOTE_ADDR'] ?? null]);
}

function badge(string $s): string
{
    $m = ['pending_payment' => 'warn', 'unpaid' => 'warn', 'pending' => 'warn', 'for_verification' => 'info',
          'submitted' => 'info', 'processing' => 'info', 'paid' => 'ok', 'approved' => 'ok', 'cleared' => 'ok',
          'ready' => 'ok', 'released' => 'done', 'passed' => 'ok', 'hold' => 'bad', 'rejected' => 'bad', 'failed' => 'bad'];
    $c = $m[strtolower($s)] ?? 'mute';
    return '<span class="badge ' . $c . '">' . e(ucwords(str_replace('_', ' ', $s))) . '</span>';
}
function pipeline(string $status): string
{
    $steps = ['pending_payment' => 'Pending', 'paid' => 'Paid', 'processing' => 'Processing', 'ready' => 'Ready', 'released' => 'Released'];
    $keys = array_keys($steps);
    $idx = array_search($status, $keys, true);
    if ($idx === false) return badge($status);
    $h = '<div class="pipe">';
    foreach ($keys as $i => $k) $h .= '<span class="step' . ($i <= $idx ? ' on' : '') . '">' . $steps[$k] . '</span>';
    return $h . '</div>';
}
function post_btn(string $path, string $label, string $class = 'btn sm', array $fields = [], string $confirm = ''): string
{
    $h = '<form method="post" action="' . e(url($path)) . '" class="inline"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field();
    foreach ($fields as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return $h . '<button class="' . e($class) . '">' . e($label) . '</button></form>';
}
function remark_form(string $path, string $label, string $class = 'btn sm danger'): string
{
    return '<form method="post" action="' . e(url($path)) . '" class="inline remark">' . csrf_field()
        . '<input name="remarks" required maxlength="255" placeholder="Reason / remarks"><button class="' . e($class) . '">' . e($label) . '</button></form>';
}
function nav_for(string $role): array
{
    return match ($role) {
        'student'    => ['/student' => 'Dashboard', '/student/enrollment' => 'Enrollment', '/student/subjects' => 'Subjects',
                         '/student/grades' => 'Grades', '/student/clearance' => 'Clearance', '/student/documents' => 'Documents',
                         '/student/payments' => 'Payments'],
        'registrar'  => ['/registrar' => 'Dashboard', '/registrar/enrollments' => 'Enrollments', '/registrar/grades' => 'Grades',
                         '/registrar/documents' => 'Documents', '/registrar/offerings' => 'Offerings'],
        'cashier'    => ['/cashier' => 'Dashboard', '/cashier/payments' => 'Payments', '/cashier/report' => 'Daily Report'],
        'department' => ['/department' => 'Dashboard', '/department/clearance' => 'Clearance'],
        'admin'      => ['/admin' => 'Dashboard', '/admin/users' => 'Users', '/admin/settings' => 'Settings', '/admin/audit' => 'Audit Log'],
        default      => [],
    };
}