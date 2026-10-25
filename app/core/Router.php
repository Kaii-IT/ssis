<?php
final class Router
{
    private array $routes = [];

    /** $roles: null = public, '*' = any signed-in user, array = allowed roles */
    public function add(string $method, string $pattern, string $handler, $roles = null): void
    {
        $this->routes[] = [$method, $pattern, $handler, $roles];
    }

    public function dispatch(): void
    {
        $path = current_path();
        $method = $_SERVER['REQUEST_METHOD'];
        foreach ($this->routes as [$m, $p, $h, $roles]) {
            if ($m !== $method) continue;
            $re = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $p) . '$#';
            if (!preg_match($re, $path, $mt)) continue;
            $args = array_values(array_filter($mt, 'is_string', ARRAY_FILTER_USE_KEY));
            $this->guard($roles, $path);
            if ($method === 'POST') Csrf::check();
            [$cls, $fn] = explode('@', $h);
            (new $cls())->$fn(...$args);
            return;
        }
        abort(404, 'The page you requested does not exist.');
    }

    private function guard($roles, string $path): void
    {
        if ($roles === null) return;
        $u = Auth::user();
        if (!$u) { flash('error', 'Please sign in to continue.'); redirect('/login'); }
        if ($u['must_change_password'] && !in_array($path, ['/password', '/logout'], true)) {
            flash('error', 'You must change your temporary password first.');
            redirect('/password');
        }
        if ($roles !== '*' && !in_array($u['role'], $roles, true)) {
            abort(403, 'Your role does not have access to this page.');
        }
    }
}