<?php
class RegistrarController
{
    public const GRADES = ['', '1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '2.75', '3.00', '5.00', 'INC', 'DRP'];

    private static function remark(string $g): ?string
    {
        if ($g === '') return null;
        return match ($g) { 'INC' => 'Incomplete', 'DRP' => 'Dropped', '5.00' => 'Failed', default => 'Passed' };
    }

    public function dashboard(): void
    {
        $t = need_term();
        $c = [
            'submitted'  => DB::val("SELECT COUNT(*) FROM enrollments WHERE term_id=? AND status='submitted'", [$t['id']]),
            'paid'       => DB::val("SELECT COUNT(*) FROM document_requests WHERE status='paid'"),
            'processing' => DB::val("SELECT COUNT(*) FROM document_requests WHERE status='processing'"),
            'ready'      => DB::val("SELECT COUNT(*) FROM document_requests WHERE status='ready'"),
        ];
        view('registrar/dashboard', ['title' => 'Registrar', 'term' => $t, 'c' => $c]);
    }

    /* ---------- Enrollment review ---------- */
    public function enrollments(): void
    {
        $t = need_term();
        $st = $_GET['status'] ?? 'submitted';
        if (!in_array($st, ['submitted', 'approved', 'draft', 'all'], true)) $st = 'submitted';
        $rows = DB::all("SELECT e.id,e.status,u.user_no,u.first_name,u.last_name,
              (SELECT COALESCE(SUM(sj.units),0) FROM enrollment_items i JOIN offerings o ON o.id=i.offering_id JOIN subjects sj ON sj.id=o.subject_id WHERE i.enrollment_id=e.id) AS units
              FROM enrollments e JOIN users u ON u.id=e.student_id
              WHERE e.term_id=? AND (?='all' OR e.status=?) ORDER BY e.id DESC LIMIT 200", [$t['id'], $st, $st]);
        view('registrar/enrollments', ['title' => 'Enrollments', 'term' => $t, 'rows' => $rows, 'st' => $st]);
    }

    public function enrollmentShow($id): void
    {
        $e = DB::one('SELECT e.*,t.name AS term,u.user_no,u.first_name,u.last_name,s.program,s.year_level
                      FROM enrollments e JOIN terms t ON t.id=e.term_id JOIN users u ON u.id=e.student_id
                      JOIN students s ON s.user_id=u.id WHERE e.id=?', [(int)$id]);
        if (!$e) abort(404);
        $items = DB::all('SELECT sj.code,sj.title,sj.units,o.section,o.schedule,o.room FROM enrollment_items i
                          JOIN offerings o ON o.id=i.offering_id JOIN subjects sj ON sj.id=o.subject_id
                          WHERE i.enrollment_id=? ORDER BY sj.code', [$e['id']]);
        $units = array_sum(array_column($items, 'units'));
        view('registrar/enrollment_show', ['title' => 'Review enrollment', 'e' => $e, 'items' => $items, 'units' => $units,
              'fee' => $units * config('tuition_per_unit') + config('misc_fee')]);
    }

    public function approve($id): void
    {
        $pdo = DB::pdo(); $pdo->beginTransaction();
        $e = DB::one('SELECT e.*,t.name AS term FROM enrollments e JOIN terms t ON t.id=e.term_id WHERE e.id=? FOR UPDATE', [(int)$id]);
        if (!$e || $e['status'] !== 'submitted') { $pdo->rollBack(); flash('error', 'Only submitted enrollments can be approved.'); redirect('/registrar/enrollments'); }
        $units = (int)DB::val('SELECT COALESCE(SUM(sj.units),0) FROM enrollment_items i JOIN offerings o ON o.id=i.offering_id JOIN subjects sj ON sj.id=o.subject_id WHERE i.enrollment_id=?', [$e['id']]);
        $amount = $units * config('tuition_per_unit') + config('misc_fee');
        DB::run("UPDATE enrollments SET status='approved', remarks=NULL, reviewed_by=?, reviewed_at=NOW() WHERE id=?", [Auth::user()['id'], $e['id']]);
        DB::run("INSERT INTO payments(student_id,payment_type,enrollment_id,description,amount) VALUES(?, 'tuition', ?, ?, ?)",
                [$e['student_id'], $e['id'], "Tuition & fees — {$e['term']} ($units units)", $amount]);
        $pdo->commit();
        audit('enroll_approve', 'enrollment', (int)$e['id']);
        flash('success', 'Enrollment approved. A tuition assessment was sent to the student.');
        redirect('/registrar/enrollments');
    }

    public function returnEnrollment($id): void
    {
        $remarks = post('remarks');
        $e = DB::one('SELECT * FROM enrollments WHERE id=?', [(int)$id]);
        if (!$e || $e['status'] !== 'submitted' || $remarks === '') { flash('error', 'Provide a reason; only submitted enrollments can be returned.'); redirect('/registrar/enrollments'); }
        DB::run("UPDATE enrollments SET status='draft', remarks=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?", [$remarks, Auth::user()['id'], $e['id']]);
        audit('enroll_return', 'enrollment', (int)$e['id'], $remarks);
        flash('success', 'Enrollment returned to the student for revision.');
        redirect('/registrar/enrollments');
    }

    /* ---------- Grades encoding ---------- */
    public function grades(): void
    {
        $t = need_term();
        $rows = DB::all("SELECT o.id,o.section,sj.code,sj.title,
            (SELECT COUNT(*) FROM enrollment_items i JOIN enrollments e ON e.id=i.enrollment_id WHERE i.offering_id=o.id AND e.status='approved') AS enrolled,
            (SELECT COUNT(*) FROM enrollment_items i JOIN enrollments e ON e.id=i.enrollment_id WHERE i.offering_id=o.id AND e.status='approved' AND i.grade IS NOT NULL) AS graded
            FROM offerings o JOIN subjects sj ON sj.id=o.subject_id WHERE o.term_id=? ORDER BY sj.code,o.section", [$t['id']]);
        view('registrar/grades', ['title' => 'Grades encoding', 'term' => $t, 'rows' => $rows]);
    }

    public function gradeSheet($id): void
    {
        $o = DB::one('SELECT o.*,sj.code,sj.title FROM offerings o JOIN subjects sj ON sj.id=o.subject_id WHERE o.id=?', [(int)$id]);
        if (!$o) abort(404);
        $rows = DB::all("SELECT i.id,i.grade,i.remarks,u.user_no,u.first_name,u.last_name FROM enrollment_items i
            JOIN enrollments e ON e.id=i.enrollment_id JOIN users u ON u.id=e.student_id
            WHERE i.offering_id=? AND e.status='approved' ORDER BY u.last_name,u.first_name", [$o['id']]);
        view('registrar/grade_sheet', ['title' => 'Grade sheet', 'o' => $o, 'rows' => $rows, 'scale' => self::GRADES]);
    }

    public function saveGrades($id): void
    {
        $oid = (int)$id;
        $g = $_POST['grade'] ?? [];
        if (!is_array($g)) $g = [];
        $n = 0;
        foreach ($g as $itemId => $val) {
            $val = trim((string)$val);
            if (!in_array($val, self::GRADES, true)) continue;
            $n += DB::run("UPDATE enrollment_items i JOIN enrollments e ON e.id=i.enrollment_id
                           SET i.grade=?, i.remarks=? WHERE i.id=? AND i.offering_id=? AND e.status='approved'",
                           [$val === '' ? null : $val, self::remark($val), (int)$itemId, $oid])->rowCount();
        }
        audit('grades_saved', 'offering', $oid, "$n rows updated");
        flash('success', 'Grades saved.');
        redirect("/registrar/grades/$oid");
    }

    /* ---------- Document processing ---------- */
    public function documents(): void
    {
        $st = $_GET['status'] ?? 'active';
        $valid = ['active', 'all', 'pending_payment', 'paid', 'processing', 'ready', 'released', 'rejected', 'cancelled'];
        if (!in_array($st, $valid, true)) $st = 'active';
        $where = $st === 'all' ? '1=1' : ($st === 'active' ? "r.status IN ('paid','processing','ready')" : 'r.status=?');
        $rows = DB::all("SELECT r.*,dt.name AS doc_name,u.user_no,u.first_name,u.last_name FROM document_requests r
            JOIN document_types dt ON dt.id=r.document_type_id JOIN users u ON u.id=r.student_id
            WHERE $where ORDER BY r.id DESC LIMIT 200", in_array($st, ['all', 'active'], true) ? [] : [$st]);
        view('registrar/documents', ['title' => 'Document requests', 'rows' => $rows, 'st' => $st, 'valid' => $valid]);
    }

    public function advance($id): void
    {
        $r = DB::one('SELECT * FROM document_requests WHERE id=?', [(int)$id]);
        $next = ['paid' => 'processing', 'processing' => 'ready', 'ready' => 'released'];
        if (!$r || !isset($next[$r['status']])) { flash('error', 'This request cannot be advanced.'); redirect('/registrar/documents'); }
        DB::run('UPDATE document_requests SET status=?, processed_by=? WHERE id=?', [$next[$r['status']], Auth::user()['id'], $r['id']]);
        audit('document_' . $next[$r['status']], 'document_request', (int)$r['id']);
        flash('success', "{$r['request_no']} is now " . str_replace('_', ' ', $next[$r['status']]) . '.');
        redirect('/registrar/documents');
    }

    public function reject($id): void
    {
        $r = DB::one('SELECT * FROM document_requests WHERE id=?', [(int)$id]);
        $remarks = post('remarks');
        if (!$r || !in_array($r['status'], ['paid', 'processing'], true) || $remarks === '') {
            flash('error', 'Provide a reason; only paid/processing requests can be rejected.'); redirect('/registrar/documents');
        }
        DB::run("UPDATE document_requests SET status='rejected', remarks=?, processed_by=? WHERE id=?", [$remarks, Auth::user()['id'], $r['id']]);
        audit('document_rejected', 'document_request', (int)$r['id'], $remarks);
        flash('success', 'Request rejected. Coordinate any refund with the Cashier.');
        redirect('/registrar/documents');
    }

    /* ---------- Subjects & offerings ---------- */
    public function offerings(): void
    {
        $t = need_term();
        view('registrar/offerings', ['title' => 'Offerings', 'term' => $t,
            'rows' => DB::all('SELECT o.*,sj.code,sj.title,sj.units,(SELECT COUNT(*) FROM enrollment_items i WHERE i.offering_id=o.id) AS taken
                               FROM offerings o JOIN subjects sj ON sj.id=o.subject_id WHERE o.term_id=? ORDER BY sj.code,o.section', [$t['id']]),
            'subjects' => DB::all('SELECT * FROM subjects ORDER BY code'),
            'depts' => DB::all("SELECT * FROM departments WHERE kind='college' ORDER BY name")]);
    }

    public function subjectStore(): void
    {
        $code = strtoupper(post('code')); $title = post('title'); $units = (int)post('units'); $dept = (int)post('department_id');
        if (!preg_match('/^[A-Z0-9\-]{3,15}$/', $code) || $title === '' || $units < 1 || $units > 6) { flash('error', 'Check the subject code, title and units (1–6).'); redirect('/registrar/offerings'); }
        if (DB::val('SELECT COUNT(*) FROM subjects WHERE code=?', [$code])) { flash('error', 'Subject code already exists.'); redirect('/registrar/offerings'); }
        DB::run('INSERT INTO subjects(code,title,units,department_id) VALUES(?,?,?,?)', [$code, mb_substr($title, 0, 150), $units, $dept ?: null]);
        audit('subject_add', 'subject', DB::id(), $code);
        flash('success', "Subject $code created.");
        redirect('/registrar/offerings');
    }

    public function offeringStore(): void
    {
        $t = need_term();
        $sid = (int)post('subject_id'); $sec = strtoupper(post('section')); $sch = post('schedule'); $room = post('room'); $slots = (int)post('slots');
        if (!DB::val('SELECT COUNT(*) FROM subjects WHERE id=?', [$sid]) || !preg_match('/^[A-Z0-9]{1,10}$/', $sec) || $sch === '' || $room === '' || $slots < 0 || $slots > 500) {
            flash('error', 'Check all offering fields.'); redirect('/registrar/offerings');
        }
        if (DB::val('SELECT COUNT(*) FROM offerings WHERE term_id=? AND subject_id=? AND section=?', [$t['id'], $sid, $sec])) { flash('error', 'That section already exists.'); redirect('/registrar/offerings'); }
        DB::run('INSERT INTO offerings(term_id,subject_id,section,schedule,room,slots) VALUES(?,?,?,?,?,?)', [$t['id'], $sid, $sec, mb_substr($sch, 0, 60), mb_substr($room, 0, 30), $slots]);
        audit('offering_add', 'offering', DB::id());
        flash('success', 'Offering added.');
        redirect('/registrar/offerings');
    }

    public function offeringDelete($id): void
    {
        if (DB::val('SELECT COUNT(*) FROM enrollment_items WHERE offering_id=?', [(int)$id])) { flash('error', 'Cannot remove: students are already enrolled.'); redirect('/registrar/offerings'); }
        DB::run('DELETE FROM offerings WHERE id=?', [(int)$id]);
        audit('offering_delete', 'offering', (int)$id);
        flash('success', 'Offering removed.');
        redirect('/registrar/offerings');
    }
}