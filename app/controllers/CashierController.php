<?php
class CashierController
{
    public function dashboard(): void
    {
        view('cashier/dashboard', ['title' => 'Cashier', 'c' => [
            'verify' => DB::val("SELECT COUNT(*) FROM payments WHERE status='for_verification'"),
            'unpaid' => DB::val("SELECT COUNT(*) FROM payments WHERE status IN ('unpaid','rejected')"),
            'today'  => DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND DATE(paid_at)=CURDATE()"),
            'count'  => DB::val("SELECT COUNT(*) FROM payments WHERE status='paid' AND DATE(paid_at)=CURDATE()"),
        ]]);
    }

    public function payments(): void
    {
        $st = $_GET['status'] ?? 'for_verification';
        if (!in_array($st, ['for_verification', 'unpaid', 'rejected', 'paid', 'void', 'all'], true)) $st = 'for_verification';
        $q = trim($_GET['q'] ?? '');
        $sql = 'SELECT p.*,u.user_no,u.first_name,u.last_name FROM payments p JOIN users u ON u.id=p.student_id WHERE 1=1';
        $args = [];
        if ($st !== 'all') { $sql .= ' AND p.status=?'; $args[] = $st; }
        if ($q !== '') { $sql .= ' AND (u.user_no LIKE ? OR u.last_name LIKE ? OR p.reference_no LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
        view('cashier/payments', ['title' => 'Payments', 'rows' => DB::all($sql . ' ORDER BY p.id DESC LIMIT 200', $args), 'st' => $st, 'q' => $q]);
    }

    private function settle(array $p, string $method): void
    {
        $or = 'OR-' . date('Ymd') . '-' . str_pad((string)$p['id'], 6, '0', STR_PAD_LEFT);
        DB::run("UPDATE payments SET status='paid', method=?, or_number=?, verified_by=?, paid_at=NOW(), remarks=NULL WHERE id=?", [$method, $or, Auth::user()['id'], $p['id']]);
        if ($p['document_request_id']) DB::run("UPDATE document_requests SET status='paid' WHERE id=? AND status='pending_payment'", [$p['document_request_id']]);
        audit('payment_paid', 'payment', (int)$p['id'], "$or via $method");
    }

    private function lock($id, array $allowed): array
    {
        DB::pdo()->beginTransaction();
        $p = DB::one('SELECT * FROM payments WHERE id=? FOR UPDATE', [(int)$id]);
        if (!$p || !in_array($p['status'], $allowed, true)) {
            DB::pdo()->rollBack();
            flash('error', 'This payment cannot be processed in its current state.');
            redirect('/cashier/payments');
        }
        return $p;
    }

    public function verify($id): void
    {
        $p = $this->lock($id, ['for_verification']);
        $this->settle($p, $p['method'] ?: 'e-wallet');
        DB::pdo()->commit();
        flash('success', 'Payment verified and official receipt issued.');
        redirect('/cashier/payments');
    }

    public function cash($id): void
    {
        $p = $this->lock($id, ['unpaid', 'rejected']);
        $this->settle($p, 'cash');
        DB::pdo()->commit();
        flash('success', 'Cash payment recorded and official receipt issued.');
        redirect('/cashier/payments?status=unpaid');
    }

    public function reject($id): void
    {
        $remarks = post('remarks');
        if ($remarks === '') { flash('error', 'A reason is required.'); redirect('/cashier/payments'); }
        $p = $this->lock($id, ['for_verification']);
        DB::run("UPDATE payments SET status='rejected', remarks=?, verified_by=? WHERE id=?", [$remarks, Auth::user()['id'], $p['id']]);
        DB::pdo()->commit();
        audit('payment_rejected', 'payment', (int)$p['id'], $remarks);
        flash('success', 'Payment rejected; the student can resubmit.');
        redirect('/cashier/payments');
    }

    public function receipt($id): void
    {
        $u = Auth::user();
        $p = DB::one("SELECT p.*,s.user_no,s.first_name,s.last_name,c.first_name AS c_first,c.last_name AS c_last
                      FROM payments p JOIN users s ON s.id=p.student_id LEFT JOIN users c ON c.id=p.verified_by
                      WHERE p.id=? AND p.status='paid'", [(int)$id]);
        if (!$p || ($u['role'] === 'student' && (int)$p['student_id'] !== (int)$u['id'])) abort(404, 'Receipt not found.');
        view('cashier/receipt', ['title' => 'Receipt ' . $p['or_number'], 'p' => $p], 'print');
    }

    public function report(): void
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
        $rows = DB::all("SELECT p.*,u.user_no,u.first_name,u.last_name FROM payments p JOIN users u ON u.id=p.student_id
                         WHERE p.status='paid' AND DATE(p.paid_at)=? ORDER BY p.paid_at", [$date]);
        $by = [];
        foreach ($rows as $r) $by[$r['method']] = ($by[$r['method']] ?? 0) + $r['amount'];
        view('cashier/report', ['title' => 'Daily report', 'rows' => $rows, 'date' => $date, 'by' => $by, 'total' => array_sum($by)]);
    }
}