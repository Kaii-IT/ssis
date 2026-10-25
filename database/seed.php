<?php
if (PHP_SAPI !== 'cli') { exit('Run from CLI only.'); }
require __DIR__ . '/../app/bootstrap.php';

$pdo = DB::pdo();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['audit_logs','payments','document_requests','document_types','clearance_items','enrollment_items',
          'enrollments','offerings','subjects','terms','students','users','departments'] as $t) {
    $pdo->exec("TRUNCATE TABLE $t");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// Departments
foreach ([['CCS','College of Computer Studies','college'],['CBA','College of Business','college'],
          ['LIB','Library','office'],['GUID','Guidance Office','office'],['ACCT','Accounting Office','office']] as $d) {
    DB::run('INSERT INTO departments(code,name,kind) VALUES(?,?,?)', $d);
}
$dep = fn($c) => (int)DB::val('SELECT id FROM departments WHERE code=?', [$c]);

// Terms
DB::run("INSERT INTO terms(name,is_current) VALUES('2nd Semester 2025-2026',0),('1st Semester 2026-2027',1)");
$prev = (int)DB::val("SELECT id FROM terms WHERE is_current=0");
$cur  = (int)DB::val("SELECT id FROM terms WHERE is_current=1");

// Subjects
$subjects = [
  ['CS101','Introduction to Computing',3,'CCS'],['CS102','Programming Fundamentals',3,'CCS'],
  ['CS201','Data Structures',3,'CCS'],['IT210','Database Systems',3,'CCS'],
  ['BA110','Principles of Management',3,'CBA'],['GE101','Understanding the Self',3,null],['GE102','Readings in Philippine History',3,null],
];
foreach ($subjects as [$c,$t,$u,$d]) {
    DB::run('INSERT INTO subjects(code,title,units,department_id) VALUES(?,?,?,?)', [$c,$t,$u,$d ? $dep($d) : null]);
}
$sub = fn($c) => (int)DB::val('SELECT id FROM subjects WHERE code=?', [$c]);

// Offerings (current term). CS201 has 0 slots on purpose (full-class test).
$off = [['CS101','A','MWF 8:00-9:00','Lab 1',3],['CS102','A','TTh 9:00-10:30','Lab 2',1],['CS201','A','MWF 10:00-11:00','Rm 204',0],
        ['IT210','A','TTh 1:00-2:30','Lab 3',5],['BA110','A','MWF 1:00-2:00','Rm 110',8],['GE101','A','Sat 8:00-11:00','Rm 301',40]];
foreach ($off as [$c,$s,$sch,$r,$sl]) {
    DB::run('INSERT INTO offerings(term_id,subject_id,section,schedule,room,slots) VALUES(?,?,?,?,?,?)', [$cur,$sub($c),$s,$sch,$r,$sl]);
}
DB::run('INSERT INTO offerings(term_id,subject_id,section,schedule,room,slots) VALUES(?,?,?,?,?,?)', [$prev,$sub('GE102'),'A','Sat 8:00-11:00','Rm 301',40]);

// Document types
foreach ([['TOR','Transcript of Records',150,'clearance'],['COR','Certificate of Registration',50,'enrollment'],
          ['CERT_ENR','Certificate of Enrollment',50,'enrollment'],['CERT_GMC','Certificate of Good Moral Character',75,'none'],
          ['CERT_GRD','Certification of Grades',60,'enrollment']] as $d) {
    DB::run('INSERT INTO document_types(code,name,fee,requirement) VALUES(?,?,?,?)', $d);
}

// Users (demo password for everyone: Password@123)
$hash = password_hash('Password@123', PASSWORD_DEFAULT);
$mk = function ($no,$role,$fn,$ln,$dept=null) use ($hash) {
    DB::run('INSERT INTO users(user_no,role,password_hash,first_name,last_name,department_id) VALUES(?,?,?,?,?,?)',
        [$no,$role,$hash,$fn,$ln,$dept ? $GLOBALS['depId']($dept) : null]);
    return DB::id();
};
$GLOBALS['depId'] = $dep;
$mk('ADMIN-001','admin','System','Admin');
$mk('REG-001','registrar','Rina','Registrar');
$mk('CSH-001','cashier','Carlo','Cashier');
$mk('DEPT-CCS','department','Cathy','Dean','CCS');
$mk('LIB-001','department','Liza','Librarian','LIB');
$mk('GUID-001','department','Gina','Counselor','GUID');
$mk('ACCT-001','department','Alex','Accountant','ACCT');
$s1 = $mk('2026-00123','student','Kai','Dela Cruz');
$s2 = $mk('2026-00124','student','Mia','Santos');
foreach ([$s1,$s2] as $sid) {
    DB::run('INSERT INTO students(user_id,department_id,program,year_level) VALUES(?,?,?,3)', [$sid,$dep('CCS'),'BS Information Technology']);
}
// Previous-term history for student 1 (shows grades page)
DB::run("INSERT INTO enrollments(student_id,term_id,status) VALUES(?,?, 'approved')", [$s1,$prev]);
$eid = DB::id();
$oid = (int)DB::val('SELECT id FROM offerings WHERE term_id=?', [$prev]);
DB::run("INSERT INTO enrollment_items(enrollment_id,offering_id,grade,remarks) VALUES(?,?, '1.75','Passed')", [$eid,$oid]);

echo "Seed complete. Demo logins (password Password@123):\n";
echo "ADMIN-001 | REG-001 | CSH-001 | DEPT-CCS | LIB-001 | GUID-001 | ACCT-001 | 2026-00123 | 2026-00124\n";