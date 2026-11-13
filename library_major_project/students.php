<?php
require __DIR__ . '/includes/init.php';

/* ---------- DELETE a student ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_ok()) {
        flash('danger', 'Security check failed. Please try again.');
        redirect('students.php');
    }
    try {
        $st = run($db, "DELETE FROM students WHERE id = ?", 'i', [(int)($_POST['id'] ?? 0)]);
        if ($st->affected_rows > 0) { flash('success', 'Student deleted successfully.'); }
        else                        { flash('danger', 'Student not found.'); }
    } catch (mysqli_sql_exception $e) {
        flash('danger', 'This student has issue records, so they cannot be deleted.');
    }
    redirect('students.php');
}

/* ---------- SEARCH ---------- */
$q = trim($_GET['q'] ?? '');
$base = "SELECT s.*,
                (SELECT COUNT(*) FROM issues i WHERE i.student_id = s.id AND i.status = 'Issued') AS holding
         FROM students s";
if ($q !== '') {
    $like = '%' . $q . '%';
    $students = run($db, $base . " WHERE s.full_name LIKE ? OR s.enrollment_no LIKE ? OR s.email LIKE ? ORDER BY s.full_name",
                    'sss', [$like, $like, $like])->get_result();
} else {
    $students = run($db, $base . " ORDER BY s.full_name")->get_result();
}

$pageTitle = 'Students';
$active = 'students';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Students</h1>
        <p class="subtle">Registered library members.</p>
    </div>
    <a class="btn" href="student_form.php">+ Add Student</a>
</div>

<form class="toolbar" method="get" action="students.php">
    <input class="grow" type="text" name="q" placeholder="Search by name, enrollment number or email" value="<?= h($q) ?>">
    <button class="btn" type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="btn btn-grey" href="students.php">Clear</a><?php endif; ?>
</form>

<p class="result-count"><?= $students->num_rows ?> student(s) found</p>
<div class="card card-flush">
<div class="table-scroll">
<table class="data">
    <thead>
        <tr><th>#</th><th>Name</th><th>Enrollment No.</th><th>Email</th><th>Phone</th><th>Books held</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if ($students->num_rows === 0): ?>
        <tr class="empty-row"><td colspan="7">No students found.</td></tr>
    <?php endif; ?>
    <?php while ($s = $students->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$s['id'] ?></td>
            <td><strong><?= h($s['full_name']) ?></strong></td>
            <td><?= h($s['enrollment_no']) ?></td>
            <td><?= h($s['email']) ?></td>
            <td><?= h($s['phone']) ?></td>
            <td><?= (int)$s['holding'] ?> / <?= MAX_ISSUE_LIMIT ?></td>
            <td>
                <div class="btn-row">
                    <a class="btn btn-sm btn-grey" href="student_form.php?id=<?= (int)$s['id'] ?>">Edit</a>
                    <form class="inline-form" method="post" data-confirm="Delete this student permanently?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
