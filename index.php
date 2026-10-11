<?php
declare(strict_types=1);
session_start();

/*
 * School ERP prototype: Student Management + Fees/Finance.
 * For coursework demonstration only. Configure credentials below for local XAMPP.
 */
$dbHost = '127.0.0.1';
$dbName = 'school_erp';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Import schema.sql in phpMyAdmin and check the database settings at the top of index.php.');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function go(string $msg, string $type='success'): void {
    $_SESSION['flash'] = [$msg, $type];
    header('Location: index.php');
    exit;
}
function post(string $key): string { return trim((string)($_POST[$key] ?? '')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        go('Request validation failed. Refresh and try again.', 'error');
    }
    $action = post('action');
    try {
        if ($action === 'add_student' || $action === 'update_student') {
            $id = (int)post('id');
            $studentNo = post('student_no');
            $name = post('full_name');
            $class = post('class_name');
            $phone = post('guardian_phone');
            if ($studentNo === '' || $name === '' || $class === '') {
                go('Student number, full name and class are required.', 'error');
            }
            if ($action === 'add_student') {
                $q = $pdo->prepare('INSERT INTO students (student_no, full_name, class_name, guardian_phone) VALUES (?, ?, ?, ?)');
                $q->execute([$studentNo, $name, $class, $phone ?: null]);
                go('Student registered successfully.');
            } else {
                $q = $pdo->prepare('UPDATE students SET student_no=?, full_name=?, class_name=?, guardian_phone=? WHERE id=?');
                $q->execute([$studentNo, $name, $class, $phone ?: null, $id]);
                go('Student details updated.');
            }
        }

        if ($action === 'delete_student') {
            $q = $pdo->prepare('DELETE FROM students WHERE id=?');
            $q->execute([(int)post('id')]);
            go('Student deleted.');
        }

        if ($action === 'add_charge') {
            $studentId = (int)post('student_id');
            $term = post('term_name');
            $year = (int)post('academic_year');
            $amount = filter_var(post('amount_due'), FILTER_VALIDATE_FLOAT);
            if ($studentId < 1 || $term === '' || $year < 2000 || $year > 2100 || $amount === false || $amount < 0) {
                go('Enter a valid student, term, year and fee amount.', 'error');
            }
            $q = $pdo->prepare('INSERT INTO fee_charges (student_id, term_name, academic_year, amount_due) VALUES (?, ?, ?, ?)');
            $q->execute([$studentId, $term, $year, $amount]);
            go('Fee charge saved.');
        }

        if ($action === 'add_payment') {
            $studentId = (int)post('student_id');
            $receipt = post('receipt_no');
            $amount = filter_var(post('amount'), FILTER_VALIDATE_FLOAT);
            $date = post('payment_date');
            $method = post('method');
            $note = post('note');
            $methods = ['Cash', 'MTN Mobile Money', 'Airtel Money', 'Bank'];
            if ($studentId < 1 || $receipt === '' || $amount === false || $amount <= 0 || $date === '' || !in_array($method, $methods, true)) {
                go('Enter valid payment details. Amount must be greater than zero.', 'error');
            }
            $q = $pdo->prepare('INSERT INTO payments (receipt_no, student_id, amount, payment_date, method, note) VALUES (?, ?, ?, ?, ?, ?)');
            $q->execute([$receipt, $studentId, $amount, $date, $method, $note ?: null]);
            go('Payment recorded. Student balance has been recalculated automatically.');
        }

        if ($action === 'delete_payment') {
            $q = $pdo->prepare('DELETE FROM payments WHERE id=?');
            $q->execute([(int)post('id')]);
            go('Payment deleted.');
        }
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), '1062')) go('Duplicate student number, receipt number, or student-term-year charge.', 'error');
        if (str_contains($e->getMessage(), '1451')) go('This student has financial records and cannot be deleted. Delete the related financial records first.', 'error');
        go('The operation could not be completed. Check the values and try again.', 'error');
    }
}

$search = trim((string)($_GET['q'] ?? ''));
if ($search !== '') {
    $q = $pdo->prepare('SELECT * FROM student_balances WHERE student_no LIKE ? OR full_name LIKE ? OR class_name LIKE ? ORDER BY full_name');
    $termSearch = "%$search%";
    $q->execute([$termSearch, $termSearch, $termSearch]);
    $students = $q->fetchAll();
} else {
    $students = $pdo->query('SELECT * FROM student_balances ORDER BY full_name')->fetchAll();
}
$allStudents = $pdo->query('SELECT id, student_no, full_name FROM students ORDER BY full_name')->fetchAll();
$payments = $pdo->query('SELECT p.*, s.student_no, s.full_name FROM payments p JOIN students s ON s.id=p.student_id ORDER BY p.payment_date DESC, p.id DESC LIMIT 100')->fetchAll();
$totals = $pdo->query('SELECT COUNT(*) AS students, COALESCE((SELECT SUM(amount) FROM payments),0) AS paid, COALESCE((SELECT SUM(amount_due) FROM fee_charges),0) AS due FROM students')->fetch();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$editStudent = null;
if (isset($_GET['edit'])) {
    $q = $pdo->prepare('SELECT * FROM students WHERE id=?');
    $q->execute([(int)$_GET['edit']]);
    $editStudent = $q->fetch() ?: null;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>School ERP | Dashboard</title>
<style>
:root{--navy:#152a43;--blue:#2563eb;--bg:#f3f6fb;--ink:#1f2937;--muted:#64748b;--line:#e2e8f0;--green:#0f9f6e}
*{box-sizing:border-box}body{margin:0;background:var(--bg);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink)}
header{background:linear-gradient(120deg,#14283f,#245c72);color:white;padding:24px clamp(16px,4vw,42px)}
header h1{margin:0;font-size:clamp(22px,3vw,30px)}header p{margin:5px 0 0;color:#dbeafe}
main{max-width:1300px;margin:24px auto;padding:0 16px}.cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}
.card,.panel{background:white;border:1px solid var(--line);border-radius:15px;padding:18px;box-shadow:0 5px 18px #152a4308}
.stat{color:var(--muted);font-size:13px}.number{font-size:26px;font-weight:750;margin-top:5px}
.columns{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr);gap:18px}.panel{margin-bottom:18px}.panel h2{margin:0 0 14px;font-size:18px}
label{display:block;font-size:13px;font-weight:650;margin:10px 0 5px}input,select{width:100%;padding:10px 11px;border:1px solid #cbd5e1;border-radius:9px;background:white;font:inherit}
button,.btn{display:inline-block;border:0;border-radius:9px;background:var(--blue);color:white;padding:10px 14px;font:inherit;font-weight:650;cursor:pointer;text-decoration:none;margin-top:12px}
.btn.secondary,button.secondary{background:#eaf0f8;color:#26364a}.btn.danger,button.danger{background:#fee2e2;color:#991b1b}.btn.small,button.small{padding:6px 9px;font-size:12px;margin:0}
.formgrid{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}.span2{grid-column:span 2}
.tablewrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:620px}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid var(--line);font-size:13px}th{color:var(--muted);font-weight:700;background:#f8fafc}
.actions{display:flex;gap:6px;align-items:center}.inline{display:inline}.flash{padding:12px 14px;border-radius:10px;margin-bottom:16px}.success{background:#dcfce7;color:#166534}.error{background:#fee2e2;color:#991b1b}.muted{color:var(--muted);font-size:13px}.balance{font-weight:750}.negative{color:#b91c1c}.positive{color:var(--green)}
@media(max-width:850px){.columns{grid-template-columns:1fr}.cards{grid-template-columns:1fr}.formgrid{grid-template-columns:1fr}.span2{grid-column:auto}header{padding:20px 16px}}
</style>
</head>
<body>
<header><h1>School ERP Management System</h1><p>Student Management · Fees & Finance · One integrated database</p></header>
<main>
<?php if ($flash): ?><div class="flash <?= h($flash[1]) ?>"><?= h($flash[0]) ?></div><?php endif; ?>
<section class="cards">
 <div class="card"><div class="stat">Registered students</div><div class="number"><?= (int)$totals['students'] ?></div></div>
 <div class="card"><div class="stat">Fees charged (UGX)</div><div class="number"><?= number_format((float)$totals['due']) ?></div></div>
 <div class="card"><div class="stat">Payments received (UGX)</div><div class="number"><?= number_format((float)$totals['paid']) ?></div></div>
</section>
<div class="columns">
<section>
 <div class="panel">
  <h2><?= $editStudent ? 'Edit student' : 'Register a student' ?></h2>
  <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="<?= $editStudent ? 'update_student' : 'add_student' ?>"><input type="hidden" name="id" value="<?= (int)($editStudent['id'] ?? 0) ?>">
   <div class="formgrid">
    <div><label>Student number *</label><input name="student_no" required maxlength="30" value="<?= h($editStudent['student_no'] ?? '') ?>" placeholder="e.g. KPC/2026/001"></div>
    <div><label>Full name *</label><input name="full_name" required maxlength="120" value="<?= h($editStudent['full_name'] ?? '') ?>" placeholder="Student full name"></div>
    <div><label>Class / stream *</label><input name="class_name" required maxlength="30" value="<?= h($editStudent['class_name'] ?? '') ?>" placeholder="e.g. S.2 East"></div>
    <div><label>Guardian phone</label><input name="guardian_phone" maxlength="30" value="<?= h($editStudent['guardian_phone'] ?? '') ?>" placeholder="e.g. 07xx xxx xxx"></div>
   </div><button type="submit"><?= $editStudent ? 'Save changes' : 'Add student' ?></button>
   <?php if ($editStudent): ?> <a class="btn secondary" href="index.php">Cancel</a><?php endif; ?>
  </form>
 </div>
 <div class="panel">
  <h2>Student records and balances</h2>
  <form method="get" class="formgrid"><div class="span2"><label>Search by name, student number or class</label><input name="q" value="<?= h($search) ?>" placeholder="Search students..."></div><div class="span2"><button>Search</button> <a class="btn secondary" href="index.php">Clear</a></div></form>
  <div class="tablewrap"><table><thead><tr><th>Student</th><th>Class</th><th>Due (UGX)</th><th>Paid (UGX)</th><th>Balance (UGX)</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($students as $s): ?><tr><td><strong><?= h($s['full_name']) ?></strong><br><span class="muted"><?= h($s['student_no']) ?></span></td><td><?= h($s['class_name']) ?></td><td><?= number_format((float)$s['total_due']) ?></td><td><?= number_format((float)$s['total_paid']) ?></td><td class="balance <?= (float)$s['balance'] > 0 ? 'negative' : 'positive' ?>"><?= number_format((float)$s['balance']) ?></td><td><div class="actions"><a class="btn small secondary" href="?edit=<?= (int)$s['id'] ?>">Edit</a><form method="post" class="inline" onsubmit="return confirm('Delete this student? Students with fee records cannot be deleted.');"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete_student"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="small danger">Delete</button></form></div></td></tr><?php endforeach; ?>
  <?php if (!$students): ?><tr><td colspan="6">No student records found.</td></tr><?php endif; ?>
  </tbody></table></div>
 </div>
</section>
<section>
 <div class="panel"><h2>Set a fee charge</h2><p class="muted">A unique charge is allowed for each student, term and academic year.</p>
  <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="add_charge">
   <label>Student *</label><select name="student_id" required><option value="">Choose student</option><?php foreach ($allStudents as $s): ?><option value="<?= (int)$s['id'] ?>"><?= h($s['student_no'].' — '.$s['full_name']) ?></option><?php endforeach; ?></select>
   <div class="formgrid"><div><label>Term *</label><select name="term_name" required><option value="Term 1">Term 1</option><option value="Term 2">Term 2</option><option value="Term 3">Term 3</option></select></div><div><label>Academic year *</label><input type="number" name="academic_year" min="2000" max="2100" value="<?= date('Y') ?>" required></div></div>
   <label>Amount due (UGX) *</label><input type="number" name="amount_due" min="0" step="100" required><button type="submit">Save fee charge</button>
  </form>
 </div>
 <div class="panel"><h2>Record a payment</h2>
  <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="add_payment">
   <label>Student *</label><select name="student_id" required><option value="">Choose student</option><?php foreach ($allStudents as $s): ?><option value="<?= (int)$s['id'] ?>"><?= h($s['student_no'].' — '.$s['full_name']) ?></option><?php endforeach; ?></select>
   <div class="formgrid"><div><label>Receipt number *</label><input name="receipt_no" required maxlength="40" placeholder="e.g. RCPT-001"></div><div><label>Amount (UGX) *</label><input type="number" name="amount" min="1" step="100" required></div>
   <div><label>Payment date *</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div><div><label>Method *</label><select name="method"><option>Cash</option><option>MTN Mobile Money</option><option>Airtel Money</option><option>Bank</option></select></div></div>
   <label>Note</label><input name="note" maxlength="255" placeholder="Optional reference"><button type="submit">Record payment</button>
  </form>
 </div>
 <div class="panel"><h2>Recent payments</h2><div class="tablewrap"><table><thead><tr><th>Receipt / Student</th><th>Date</th><th>Amount (UGX)</th><th>Action</th></tr></thead><tbody>
 <?php foreach ($payments as $p): ?><tr><td><strong><?= h($p['receipt_no']) ?></strong><br><span class="muted"><?= h($p['student_no'].' — '.$p['full_name']) ?></span></td><td><?= h($p['payment_date']) ?></td><td><?= number_format((float)$p['amount']) ?></td><td><form method="post" onsubmit="return confirm('Delete this payment? The balance will update automatically.');"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete_payment"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="small danger">Delete</button></form></td></tr><?php endforeach; ?>
 <?php if (!$payments): ?><tr><td colspan="4">No payments recorded yet.</td></tr><?php endif; ?>
 </tbody></table></div></div>
</section>
</div>
<p class="muted">Coursework prototype. Use sample data only. Add authentication, authorization, backups and audit logging before any real deployment.</p>
</main>
</body></html>
