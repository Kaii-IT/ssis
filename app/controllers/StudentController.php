<?php
class StudentController
{
    private function sid(): int { return (int)Auth::user()['id']; }

    private function student(): array
    {
        return DB::one('SELECT u.id,u.user_no,u.first_name,u.last_name,u.email,s.*,d.name AS dept_name
                        FROM users u JOIN students s ON s.user_id=u.id JOIN departments d ON d.id=s.department_id
                        WHERE u.id=?', [$this->sid()]);
    }

    public function dashboard(): void
    {
        $id = $this->sid(); $t = need_term();
        $units = (int)DB::val("SELECT COALESCE(SUM(sj.units),0) FROM enrollment_items i
            JOIN enrollments e ON e.id=i.enrollment_id JOIN offerings o ON o.id=i.offering_id JOIN subjects sj ON sj.id=o.subject_id
            WHERE e.student_id=? AND e.term_id=? AND e.status='approved'", [$id, $t['id']]);
        view('student/dashboard', [
            'title'   => 'Dashboard', 's' => $this->student(), 'term' => $t, 'units' => $units,
            'enr'     => DB::one('SELECT status FROM enrollments WHERE student_id=? AND term_id=?', [$id, $t['id']]),
            'active'  => (int)DB::val("SELECT COUNT(*) FROM document_requests WHERE student_id=? AND status NOT IN ('released','rejected','cancelled')", [$id]),
            'unpaid'  => (int)DB::val("SELECT COUNT(*) FROM payments WHERE student_id=? AND status IN ('unpaid','rejected')", [$id]),
            'ctotal'  => (int)DB::val('SELECT COUNT(*) FROM clearance_items WHERE student_id=? AND term_id=?', [$id, $t['id']]),
            'cleared' => (int)DB::val("SELECT COUNT(*) FROM clearance_items WHERE student_id=? AND term_id=? AND status='cleared'", [$id, $t['id']]),
            'recent'  => DB::all('SELECT r.request_no,r.copies,r.purpose,r.status,dt.name FROM document_requests r
                                  JOIN document_types dt ON dt.id=r.document_type_id WHERE r.student_id=? ORDER BY r.id DESC LIMIT 5', [$id]),
        ]);
    }

    /* ---------- Profile ---------- */
    public function profile(): void { view('student/profile', ['title' => 'My profile', 's' => $this->student()]); }

    public function profileUpdate(): void
    {
        $email = post('email'); $contact = post('contact_no'); $addr = post('address');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Invalid email address.'); redirect('/student/profile'); }
        if ($email !== '' && DB::val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [$email, $this->sid()])) { flash('error', 'Email already in use.'); redirect('/student/profile'); }
        if ($contact !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $contact)) { flash('error', 'Invalid contact number.'); redirect('/student/profile'); }
        DB::run('UPDATE users SET email=? WHERE id=?', [$email ?: null, $this->sid()]);
        DB::run('UPDATE students SET contact_no=?, address=? WHERE user_id=?', [$contact ?: null, mb_substr($addr, 0, 255) ?: null, $this->sid()]);
        audit('profile_updated', 'user', $this->sid());
        flash('success', 'Profile updated.');
        redirect('/student/profile');
    }

    /* ---------- Enrollment ---------- */
    public function enrollment(): void
    {
        $id = $this->sid(); $t = need_term(); $s = $this->student();
        $enr = DB::one('SELECT * FROM enrollments WHERE student_id=? AND term_id=?', [$id, $t['id']]);
        $items = $enr ? DB::all('SELECT i.id,sj.code,sj.title,sj.units,o.section,o.schedule,o.room FROM enrollment_items i
                                 JOIN offerings o ON o.id=i.offering_id JOIN subjects sj ON sj.id=o.subject_id
                                 WHERE i.enrollment_id=? ORDER BY sj.code', [$enr['id']]) : [];
        $offers = DB::all('SELECT o.id,o.section,o.schedule,o.room,o.slots,sj.code,sj.title,sj.units,
              (SELECT COUNT(*) FROM enrollment_items x WHERE x.offering_id=o.id) AS taken,
              (SELECT COUNT(*) FROM enrollment_items y WHERE y.enrollment_id=? AND y.offering_id=o.id) AS mine
              FROM offerings o JOIN subjects sj ON sj.id=o.subject_id
              WHERE o.term_id=? AND (sj.department_id IS NULL OR sj.department_id=?) ORDER BY sj.code,o.section',
              [$enr['id'] ?? 0, $t['id'], $s['department_id']]);
        view('student/enrollment', ['title' => 'Enrollment', 'term' => $t, 'enr' => $enr, 'items' => $items, 'offers' => $offers,
                                    'units' => array_sum(array_column($items, 'units')), 'max' => config('max_units')]);
    }

    public function enrollAdd($offeringId): void
    {
        $id = $this->sid(); $t = need_term(); $s = $this->student();
        $pdo = DB::pdo(); $pdo->beginTransaction();
        try {
            $enr = DB::one('SELECT * FROM enrollments WHERE student_id=? AND term_id=? FOR UPDATE', [$id, $t['id']]);
            if (!$enr) {
                DB::run('INSERT INTO enrollments(student_id,term_id) VALUES(?,?)', [$id, $t['id']]);
                $enr = ['id' => DB::id(), 'status' => 'draft'];
            }
            if ($enr['status'] !== 'draft') throw new RuntimeException('Your enrollment is already submitted and locked.');
            $o = DB::one('SELECT o.*,sj.units,sj.code FROM offerings o JOIN subjects sj ON sj.id=o.subject_id
                          WHERE o.id=? AND o.term_id=? AND (sj.department_id IS NULL OR sj.department_id=?) FOR UPDATE',
                          [(int)$offeringId, $t['id'], $s['department_id']]);
            if (!$o) throw new RuntimeException('That subject is not available for you this term.');
            if (DB::val('SELECT COUNT(*) FROM enrollment_items i JOIN offerings x ON x.id=i.offering_id WHERE i.enrollment_id=? AND x.subject_id=?', [$enr['id'], $o['subject_id']]))
                throw new RuntimeException("You already selected {$o['code']} (duplicate enrollment refused).");
            if (DB::val("SELECT COUNT(*) FROM enrollment_items i JOIN enrollments e ON e.id=i.enrollment_id JOIN offerings x ON x.id=i.offering_id
                         WHERE e.student_id=? AND x.subject_id=? AND i.remarks='Passed'", [$id, $o['subject_id']]))
                throw new RuntimeException("You already passed {$o['code']}.");
            $taken = (int)DB::val('SELECT COUNT(*) FROM enrollment_items WHERE offering_id=?', [$o['id']]);
            if ($taken >= $o['slots']) throw new RuntimeException("{$o['code']} is full (no slots left).");
            $units = (int)DB::val('SELECT COALESCE(SUM(sj.units),0) FROM enrollment_items i JOIN offerings x ON x.id=i.offering_id JOIN subjects sj ON sj.id=x.subject_id WHERE i.enrollment_id=?', [$enr['id']]);
            if ($units + $o['units'] > config('max_units')) throw new RuntimeException('Unit limit of ' . config('max_units') . ' exceeded.');
            DB::run('INSERT INTO enrollment_items(enrollment_id,offering_id) VALUES(?,?)', [$enr['id'], $o['id']]);
            $pdo->commit();
            audit('enroll_add', 'offering', (int)$o['id']);
            flash('success', "{$o['code']} added to your enrollment.");
        } catch (Throwable $ex) {
            $pdo->rollBack();
            if (!$ex instanceof RuntimeException) throw $ex;
            flash('error', $ex->getMessage());
        }
        redirect('/student/enrollment');
    }

    public function enrollDrop($itemId): void
    {
        $row = DB::one('SELECT i.id,e.status FROM enrollment_items i JOIN enrollments e ON e.id=i.enrollment_id WHERE i.id=? AND e.student_id=?', [(int)$itemId, $this->sid()]);
        if (!$row) abort(404);
        if ($row['status'] !== 'draft') { flash('error', 'Enrollment is locked; ask the Registrar to return it for changes.'); redirect('/student/enrollment'); }
        DB::run('DELETE FROM enrollment_items WHERE id=?', [$row['id']]);
        audit('enroll_drop', 'enrollment_item', (int)$row['id']);
        flash('success', 'Subject dropped.');
        redirect('/student/enrollment');
    }

    public function enrollSubmit(): void
    {
        $t = need_term();
        $enr = DB::one('SELECT * FROM enrollments WHERE student_id=? AND term_id=?', [$this->sid(), $t['id']]);
        if (!$enr || $enr['status'] !== 'draft') { flash('error', 'Nothing to submit.'); redirect('/student/enrollment'); }
        if (!DB::val('SELECT COUNT(*) FROM enrollment_items WHERE enrollment_id=?', [$enr['id']])) { flash('error', 'Select at least one subject first.'); redirect('/student/enrollment'); }
        DB::run("UPDATE enrollments SET status='submitted', remarks=NULL WHERE id=?", [$enr['id']]);
        audit('enroll_submit', 'enrollment', (int)$enr['id']);
        flash('success', 'Enrollment submitted to the Registrar for approval.');
        redirect('/student/enrollment');
    }

    /* ---------- Subjects & Grades ---------- */
    public function subjects(): void
    {
        $t = need_term();
        $enr = DB::one('SELECT status FROM enrollments WHERE student_id=? AND term_id=?', [$this->sid(), $t['id']]);
        $rows = DB::all("SELECT sj.code,sj.title,sj.units,o.section,o.schedule,o.room FROM enrollment_items i
            JOIN enrollments e ON e.id=i.enrollment_id JOIN offerings o ON o.id=i.offering_id JOIN subjects sj ON sj.id=o.subject_id
            WHERE e.student_id=? AND e.term_id=? AND e.status='approved' ORDER BY sj.code", [$this->sid(), $t['id']]);
        view('student/subjects', ['title' => 'My subjects', 'term' => $t, 'enr' => $enr, 'rows' => $rows]);
    }

    public function grades(): void
    {
        $rows = DB::all("SELECT t.id AS term_id,t.name AS term,sj.code,sj.title,sj.units,i.grade,i.remarks FROM enrollment_items i
            JOIN enrollments e ON e.id=i.enrollment_id JOIN offerings o ON o.id=i.offering_id
            JOIN subjects sj ON sj.id=o.subject_id JOIN terms t ON t.id=e.term_id
            WHERE e.student_id=? AND e.status='approved' ORDER BY t.id DESC, sj.code", [$this->sid()]);
        $groups = [];
        foreach ($rows as $r) { $g = &$groups[$r['term_id']]; $g['term'] = $r['term']; $g['rows'][] = $r; unset($g); }
        foreach ($groups as &$g) {
            $u = 0; $p = 0;
            foreach ($g['rows'] as $r) if (is_numeric($r['grade'])) { $u += $r['units']; $p += $r['units'] * $r['grade']; }
            $g['gwa'] = $u ? round($p / $u, 2) : null;
        }
        unset($g);
        view('student/grades', ['title' => 'My grades', 'groups' => $groups]);
    }

    /* ---------- Clearance ---------- */
    public function clearance(): void
    {
        $t = need_term();
        $items = DB::all('SELECT c.*,d.name AS dept FROM clearance_items c JOIN departments d ON d.id=c.department_id
                          WHERE c.student_id=? AND c.term_id=? ORDER BY d.kind DESC, d.name', [$this->sid(), $t['id']]);
        view('student/clearance', ['title' => 'Clearance', 'term' => $t, 'items' => $items]);
    }

    public function clearanceStart(): void
    {
        $t = need_term(); $s = $this->student();
        if (DB::val('SELECT COUNT(*) FROM clearance_items WHERE student_id=? AND term_id=?', [$this->sid(), $t['id']])) {
            flash('error', 'Clearance already started for this term.'); redirect('/student/clearance');
        }
        foreach (DB::all("SELECT id FROM departments WHERE is_clearing=1 AND (kind='office' OR id=?)", [$s['department_id']]) as $d) {
            DB::run('INSERT INTO clearance_items(student_id,term_id,department_id) VALUES(?,?,?)', [$this->sid(), $t['id'], $d['id']]);
        }
        audit('clearance_start', 'term', (int)$t['id']);
        flash('success', 'Clearance started. Each office will review your record.');
        redirect('/student/clearance');
    }

    /* ---------- Document requests ---------- */
    public function documents(): void
    {
        $rows = DB::all('SELECT r.*,dt.name AS doc_name,p.id AS payment_id,p.status AS pay_status
                         FROM document_requests r JOIN document_types dt ON dt.id=r.document_type_id
                         LEFT JOIN payments p ON p.document_request_id=r.id WHERE r.student_id=? ORDER BY r.id DESC', [$this->sid()]);
        view('student/documents', ['title' => 'My document requests', 'rows' => $rows]);
    }

    public function documentNew(): void
    {
        view('student/document_new', ['title' => 'Request a document', 'types' => DB::all('SELECT * FROM document_types WHERE is_active=1 ORDER BY name')]);
    }

    public function documentStore(): void
    {
        $id = $this->sid(); $t = need_term();
        $type = DB::one('SELECT * FROM document_types WHERE id=? AND is_active=1', [(int)post('document_type_id')]);
        $copies = (int)post('copies', '1'); $purpose = post('purpose');
        $err = null;
        if (!$type) $err = 'Select a valid document type.';
        elseif ($copies < 1 || $copies > 10) $err = 'Copies must be between 1 and 10.';
        elseif (mb_strlen($purpose) < 3 || mb_strlen($purpose) > 255) $err = 'Enter the purpose (3–255 characters).';
        elseif (DB::val("SELECT COUNT(*) FROM document_requests WHERE student_id=? AND document_type_id=? AND status='pending_payment'", [$id, $type['id']]))
            $err = 'You already have an unpaid request for this document. Pay or cancel it first.';
        elseif ($type['requirement'] === 'enrollment' && !DB::val("SELECT COUNT(*) FROM enrollments WHERE student_id=? AND term_id=? AND status='approved'", [$id, $t['id']]))
            $err = 'This document requires an approved enrollment for the current term.';
        elseif ($type['requirement'] === 'clearance') {
            $tot = (int)DB::val('SELECT COUNT(*) FROM clearance_items WHERE student_id=? AND term_id=?', [$id, $t['id']]);
            $ok  = (int)DB::val("SELECT COUNT(*) FROM clearance_items WHERE student_id=? AND term_id=? AND status='cleared'", [$id, $t['id']]);
            if ($tot === 0 || $tot !== $ok) $err = 'This document requires a fully cleared clearance for the current term.';
        }
        if ($err) { flash('error', $err); with_old($_POST); redirect('/student/documents/new'); }

        $amount = $type['fee'] * $copies;
        $pdo = DB::pdo(); $pdo->beginTransaction();
        DB::run('INSERT INTO document_requests(student_id,document_type_id,copies,purpose,amount) VALUES(?,?,?,?,?)', [$id, $type['id'], $copies, $purpose, $amount]);
        $rid = DB::id();
        DB::run('UPDATE document_requests SET request_no=? WHERE id=?', ['DR-' . date('Y') . '-' . str_pad((string)$rid, 5, '0', STR_PAD_LEFT), $rid]);
        DB::run("INSERT INTO payments(student_id,payment_type,document_request_id,description,amount) VALUES(?, 'document', ?, ?, ?)",
                [$id, $rid, "{$type['name']} × $copies", $amount]);
        $pid = DB::id();
        $pdo->commit();
        audit('document_request', 'document_request', $rid, $type['code']);
        flash('success', 'Request submitted. Pay the fee to start processing.');
        redirect("/student/payments/$pid/pay");
    }

    public function documentCancel($id): void
    {
        $r = DB::one('SELECT * FROM document_requests WHERE id=? AND student_id=?', [(int)$id, $this->sid()]);
        if (!$r) abort(404);
        if ($r['status'] !== 'pending_payment') { flash('error', 'Only unpaid requests can be cancelled.'); redirect('/student/documents'); }
        DB::run("UPDATE document_requests SET status='cancelled' WHERE id=?", [$r['id']]);
        DB::run("UPDATE payments SET status='void' WHERE document_request_id=? AND status IN ('unpaid','rejected')", [$r['id']]);
        audit('document_cancel', 'document_request', (int)$r['id']);
        flash('success', 'Request cancelled.');
        redirect('/student/documents');
    }

    /* ---------- Payments ---------- */
    private function ownPayment($id): array
    {
        $p = DB::one('SELECT * FROM payments WHERE id=? AND student_id=?', [(int)$id, $this->sid()]);
        if (!$p) abort(404);
        return $p;
    }

    public function payments(): void
    {
        view('student/payments', ['title' => 'My payments',
            'rows' => DB::all('SELECT * FROM payments WHERE student_id=? ORDER BY id DESC', [$this->sid()])]);
    }

    public function payForm($id): void
    {
        $p = $this->ownPayment($id);
        if (!in_array($p['status'], ['unpaid', 'rejected'], true)) { flash('error', 'This payment is not awaiting submission.'); redirect('/student/payments'); }
        view('student/pay', ['title' => 'Payment', 'p' => $p]);
    }

    public function payStore($id): void
    {
        $p = $this->ownPayment($id);
        if (!in_array($p['status'], ['unpaid', 'rejected'], true)) { flash('error', 'This payment can no longer be submitted.'); redirect('/student/payments'); }
        $m = post('method'); $ref = strtoupper(post('reference_no'));
        if (!in_array($m, ['e-wallet', 'bank'], true) || !preg_match('/^[A-Z0-9\-]{6,30}$/', $ref)) {
            flash('error', 'Choose a method and enter a valid reference number (6–30 letters/numbers).');
            redirect("/student/payments/{$p['id']}/pay");
        }
        if (DB::val('SELECT COUNT(*) FROM payments WHERE method=? AND reference_no=? AND id<>?', [$m, $ref, $p['id']])) {
            flash('error', 'That reference number was already used.');
            redirect("/student/payments/{$p['id']}/pay");
        }
        DB::run("UPDATE payments SET method=?, reference_no=?, status='for_verification', remarks=NULL WHERE id=?", [$m, $ref, $p['id']]);
        audit('payment_submit', 'payment', (int)$p['id'], $m);
        flash('success', 'Payment submitted. The Cashier will verify it and issue your receipt.');
        redirect('/student/payments');
    }
}