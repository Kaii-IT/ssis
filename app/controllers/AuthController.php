<?php
class AuthController
{
    public function root(): void
    {
        $u = Auth::user();
        redirect($u ? Auth::home($u['role']) : '/login');
    }

    public function showLogin(): void
    {
        if ($u = Auth::user()) redirect(Auth::home($u['role']));
        view('auth/login', ['title' => 'Sign in'], 'guest');
    }

    public function login(): void
    {
        $no = post('user_no');
        $pw = (string)($_POST['password'] ?? '');
        if ($no === '' || $pw === '') {
            flash('error', 'Enter your ID number and password.');
            redirect('/login');
        }
        [$ok, $res] = Auth::attempt($no, $pw);
        if (!$ok) {
            audit('login_failed', 'user', null, $no);
            flash('error', $res);
            with_old(['user_no' => $no]);
            redirect('/login');
        }
        $_SESSION['uid'] = (int)$res['id'];
        audit('login', 'user', (int)$res['id']);
        // ROLE GATING: send the user to the portal that matches their role
        redirect($res['must_change_password'] ? '/password' : Auth::home($res['role']));
    }

    public function logout(): void
    {
        audit('logout', 'user', Auth::user()['id']);
        $_SESSION = [];
        session_regenerate_id(true);
        flash('success', 'You have been signed out.');
        redirect('/login');
    }

    public function showPassword(): void
    {
        view('auth/password', ['title' => 'Change password', 'forced' => (bool)Auth::user()['must_change_password']]);
    }

    public function changePassword(): void
    {
        $u = Auth::user();
        $cur = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        $cf  = (string)($_POST['confirm'] ?? '');
        $row = DB::one('SELECT password_hash FROM users WHERE id=?', [$u['id']]);
        $err = null;
        if (!password_verify($cur, $row['password_hash'])) $err = 'Current password is incorrect.';
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $err = 'New password needs 8+ characters with letters and numbers.';
        elseif ($new !== $cf) $err = 'Passwords do not match.';
        elseif ($new === $cur) $err = 'New password must be different from the current one.';
        if ($err) { flash('error', $err); redirect('/password'); }
        DB::run('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        audit('password_changed', 'user', (int)$u['id']);
        flash('success', 'Password updated.');
        redirect(Auth::home($u['role']));
    }
}