<?php
class DepartmentController
{
    private function dept(): array
    {
        $id = (int)Auth::user()['department_id'];
        $d = $id ? DB::one('SELECT * FROM departments WHERE id=?', [$id]) : null;
        if (!$d) abort(403, 'Your account is not assigned to a department. Contact the administrator.');
        return $d;
    }

    public function dashboard(): void
    {
        $d = $this->dept(); $t = need_term();
        $c = DB::all('SELECT status,COUNT(*) n FROM clearance_items WHERE department_id=? AND term_id=? GROUP BY status', [$d['id'], $t['id']]);
        view('department/dashboard', ['title' => 'Department', 'd' => $d, 'term' => $t, 'c' => array_column($c, 'n', 'status')]);
    }

    public function clearance(): void
    {
        $d = $this->dept(); $t = need_term();
        $st = $_GET['status'] ?? 'pending';
        if (!in_array($st, ['pending', 'cleared', 'hold', 'all'], true)) $st = 'pending';
        $rows = DB::all('SELECT c.*,u.user_no,u.first_name,u.last_name,s.program,s.year_level FROM clearance_items c
            JOIN users u ON u.id=c.student_id JOIN students s ON s.user_id=u.id
            WHERE c.department_id=? AND c.term_id=? AND (?=\'all\' OR c.status=?) ORDER BY c.id', [$d['id'], $t['id'], $st, $st]);
        view('department/clearance', ['title' => 'Clearance', 'd' => $d, 'rows' => $rows, 'st' => $st]);
    }

    private function mutate($id, string $status, ?string $remarks): void
    {
        $d = $this->dept();
        $c = DB::one('SELECT * FROM clearance_items WHERE id=?', [(int)$id]);
        if (!$c || (int)$c['department_id'] !== (int)$d['id']) abort(403, 'This clearance belongs to another department.');
        DB::run('UPDATE clearance_items SET status=?, remarks=?, processed_by=?, processed_at=NOW() WHERE id=?', [$status, $remarks, Auth::user()['id'], $c['id']]);
        audit('clearance_' . $status, 'clearance_item', (int)$c['id'], (string)$remarks);
        flash('success', 'Clearance updated.');
        redirect('/department/clearance');
    }

    public function clear($id): void { $this->mutate($id, 'cleared', null); }

    public function hold($id): void
    {
        $r = post('remarks');
        if ($r === '') { flash('error', 'A reason is required to place a hold.'); redirect('/department/clearance'); }
        $this->mutate($id, 'hold', $r);
    }
}