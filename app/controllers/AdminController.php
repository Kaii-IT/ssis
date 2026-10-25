<?php
class AdminController
{
    private const ROLES = ['student', 'registrar', 'cashier', 'department', 'admin'];

    public function dashboard(): void
    {
        view('admin/dashboard', ['title' => 'Admin',
            'byRole' => array_column(DB::all('SELECT role,COUNT(*) n FROM users GROUP BY role'), 'n', 'role'),
            'active' => DB::val('SELECT COUNT(*) FROM users WHERE is_active=1'),
            'locked' => DB::val('SELECT COUNT(*) FROM users WHERE locked_until > NOW()'),
            'logs'   => DB::all('SELECT a.*,u.user_no FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 8')]);
    }

    /* ---------- Users ---------- */
    public function users(): void
    {
        $q = trim($_GET['q'] ?? ''); $role = $_GET['role'] ?? '';
        $sql = 'SELECT u.*,d.code AS dcode FROM users u LEFT JOIN students s ON s.user_id=u.id
                LEFT JOIN departments d ON d.id=COALESCE(u.department_id,s.department_id) WHERE 1=1';
        $a = [];
        if ($q !== '') { $sql .= ' AND (u.user_no LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)'; array_push($a, "%$q%", "%$q%", "%$q%"); }
        if (in_array($role, self::ROLES, true)) { $sql .= ' AND u.role=?'; $a[] = $role; }
        view('admin/users', ['title' => 'Users', 'rows' => DB::all($sql . ' ORDER BY u.role,u.user_no LIMIT 300', $a), 'q' => $q, 'role' => $role, 'roles' => self::ROLES]);
    }

    private function formData(?array $user): array
    {
        return ['title' => $user ? 'Edit user' : 'New user', 'user' => $user, 'roles' => self::ROLES, 'depts' => DB::all('SELECT * FROM departments ORDER BY kind,name')];
    }

    public function create(): void { view('admin/user_form', $this->formData(null)); }

    public function edit($id): void
    {
        $u = DB::one('SELECT u.*,s.department_id AS s_dept,s.program,s.year_level,s.status AS s_status FROM users u LEFT JOIN students s ON s.user_id=u.id WHERE u.id=?', [(int)$id]);
        if (!$u) abort(404);
        view('admin/user_form', $this->formData($u));
    }

    private function input(): array
    {
        return ['user_no' => post('user_no'), 'role' => post('role'), 'first_name' => post('first_name'), 'last_name' => post('last_name'),
                'email' => post('email'), 'department_id' => (int)post('department_id'), 'program' => post('program'), 'year_level' => (int)post('year_level', '1')];
    }

    private function check(array $d, string $role, ?int $ignore, bool $create): ?string
    {
        if (!in_array($role, self::ROLES, true)) return 'Select a valid role.';
        if ($create) {
            if (!preg_match('/^[A-Za-z0-9\-]{3,30}$/', $d['user_no'])) return 'ID number must be 3–30 letters, numbers or hyphens.';
            if (DB::val('SELECT COUNT(*) FROM users WHERE user_no=?', [$d['user_no']])) return 'That ID number already exists.';
        }
        if ($d['first_name'] === '' || $d['last_name'] === '') return 'First and last name are required.';
        if ($d['email'] !== '') {
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) return 'Invalid email address.';
            if (DB::val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [$d['email'], $ignore ?? 0])) return 'Email already in use.';
        }
        if (in_array($role, ['student', 'department'], true) && !DB::val('SELECT COUNT(*) FROM departments WHERE id=?', [$d['department_id']])) return 'Select a college / department.';
        if ($role === 'student' && ($d['program'] === '' || $d['year_level'] < 1 || $d['year_level'] > 6)) return 'Program and a year level of 1–6 are required for students.';
        return null;
    }

    private function temp(): string { return ucfirst(bin2hex(random_bytes(4))) . '!'; }

    public function store(): void
    {
        $d = $this->input();
        if ($err = $this->check($d, $d['role'], null, true)) { flash('error', $err); with_old($d); redirect('/admin/users/new'); }
        $pw = $this->temp();
        $pdo = DB::pdo(); $pdo->beginTransaction();
        DB::run('INSERT INTO users(user_no,role,password_hash,first_name,last_name,email,department_id,must_change_password) VALUES(?,?,?,?,?,?,?,1)',
            [$d['user_no'], $d['role'], password_hash($pw, PASSWORD_DEFAULT), $d['first_name'], $d['last_name'], $d['email'] ?: null,
             $d['role'] === 'department' ? $d['department_id'] : null]);
        $uid = DB::id();
        if ($d['role'] === 'student') DB::run('INSERT INTO students(user_id,department_id,program,year_level) VALUES(?,?,?,?)', [$uid, $d['department_id'], $d['program'], $d['year_level']]);
        $pdo->commit();
        audit('user_create', 'user', $uid, $d['user_no'] . ' / ' . $d['role']);
        flash('success', "Account created. Temporary password (shown once): $pw");
        redirect('/admin/users');
    }

    public function update($id): void
    {
        $u = DB::one('SELECT * FROM users WHERE id=?', [(int)$id]);
        if (!$u) abort(404);
        $d = $this->input();
        if ($err = $this->check($d, $u['role'], (int)$u['id'], false)) { flash('error', $err); redirect("/admin/users/{$u['id']}/edit"); }
        DB::run('UPDATE users SET first_name=?,last_name=?,email=?,department_id=? WHERE id=?',
            [$d['first_name'], $d['last_name'], $d['email'] ?: null, $u['role'] === 'department' ? $d['department_id'] : null, $u['id']]);
        if ($u['role'] === 'student') DB::run('UPDATE students SET department_id=?,program=?,year_level=? WHERE user_id=?', [$d['department_id'], $d['program'], $d['year_level'], $u['id']]);
        audit('user_update', 'user', (int)$u['id']);
        flash('success', 'User updated.');
        redirect('/admin/users');
    }

    public function toggle($id): void
    {
        if ((int)$id === (int)Auth::user()['id']) { flash('error', 'You cannot deactivate your own account.'); redirect('/admin/users'); }
        DB::run('UPDATE users SET is_active = 1 - is_active WHERE id=?', [(int)$id]);
        audit('user_toggle', 'user', (int)$id);
        flash('success', 'Account status changed.');
        redirect('/admin/users');
    }

    public function reset($id): void
    {
        $pw = $this->temp();
        DB::run('UPDATE users SET password_hash=?, must_change_password=1, failed_attempts=0, locked_until=NULL WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), (int)$id]);
        audit('user_reset', 'user', (int)$id);
        flash('success', "Password reset. Temporary password (shown once): $pw");
        redirect('/admin/users');
    }

    /* ---------- Settings ---------- */
    public function settings(): void
    {
        view('admin/settings', ['title' => 'Settings', 'depts' => DB::all('SELECT * FROM departments ORDER BY kind,name'),
            'terms' => DB::all('SELECT * FROM terms ORDER BY id DESC'), 'types' => DB::all('SELECT * FROM document_types ORDER BY name')]);
    }

    public function departmentStore(): void
    {
        $code = strtoupper(post('code')); $name = post('name'); $kind = post('kind');
        if (!preg_match('/^[A-Z0-9\-]{2,15}$/', $code) || $name === '' || !in_array($kind, ['college', 'office'], true)) { flash('error', 'Check the department fields.'); redirect('/admin/settings'); }
        if (DB::val('SELECT COUNT(*) FROM departments WHERE code=?', [$code])) { flash('error', 'Code already exists.'); redirect('/admin/settings'); }
        DB::run('INSERT INTO departments(code,name,kind,is_clearing) VALUES(?,?,?,?)', [$code, mb_substr($name, 0, 120), $kind, isset($_POST['is_clearing']) ? 1 : 0]);
        audit('department_add', 'department', DB::id(), $code);
        flash('success', 'Department added.');
        redirect('/admin/settings');
    }

    public function termStore(): void
    {
        $name = post('name');
        if ($name === '' || DB::val('SELECT COUNT(*) FROM terms WHERE name=?', [$name])) { flash('error', 'Enter a unique term name.'); redirect('/admin/settings'); }
        DB::run('INSERT INTO terms(name) VALUES(?)', [mb_substr($name, 0, 60)]);
        audit('term_add', 'term', DB::id(), $name);
        flash('success', 'Term added.');
        redirect('/admin/settings');
    }

    public function termCurrent($id): void
    {
        $pdo = DB::pdo(); $pdo->beginTransaction();
        DB::run('UPDATE terms SET is_current=0');
        DB::run('UPDATE terms SET is_current=1 WHERE id=?', [(int)$id]);
        $pdo->commit();
        audit('term_current', 'term', (int)$id);
        flash('success', 'Current term changed.');
        redirect('/admin/settings');
    }

    public function doctypeUpdate($id): void
    {
        $fee = (float)post('fee'); $req = post('requirement');
        if ($fee < 0 || $fee > 100000 || !in_array($req, ['none', 'enrollment', 'clearance'], true)) { flash('error', 'Invalid fee or requirement.'); redirect('/admin/settings'); }
        DB::run('UPDATE document_types SET fee=?, requirement=?, is_active=? WHERE id=?', [$fee, $req, isset($_POST['is_active']) ? 1 : 0, (int)$id]);
        audit('doctype_update', 'document_type', (int)$id);
        flash('success', 'Document type updated.');
        redirect('/admin/settings');
    }

    /* ---------- Audit ---------- */
    public function audit(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1)); $q = trim($_GET['q'] ?? '');
        $w = $q !== '' ? 'WHERE a.action LIKE ? OR u.user_no LIKE ?' : ''; $a = $q !== '' ? ["%$q%", "%$q%"] : [];
        $total = (int)DB::val("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id $w", $a);
        $rows = DB::all("SELECT a.*,u.user_no FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id $w ORDER BY a.id DESC LIMIT 50 OFFSET " . (($page - 1) * 50), $a);
        view('admin/audit', ['title' => 'Audit log', 'rows' => $rows, 'page' => $page, 'pages' => max(1, (int)ceil($total / 50)), 'q' => $q]);
    }
}