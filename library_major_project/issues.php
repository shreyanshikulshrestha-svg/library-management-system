<?php
require __DIR__ . '/includes/init.php';

$today = date('Y-m-d');

/* ---------- RETURN a book (update status, stock and fine) ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'return') {
    if (!csrf_ok()) {
        flash('danger', 'Security check failed. Please try again.');
        redirect('issues.php');
    }
    $db->begin_transaction();
    try {
        $rec = run($db, "SELECT book_id, due_date FROM issues WHERE id = ? AND status = 'Issued' FOR UPDATE",
                   'i', [(int)($_POST['id'] ?? 0)])->get_result()->fetch_assoc();
        if (!$rec) {
            $db->rollback();
            flash('danger', 'Record not found or the book is already returned.');
        } else {
            $late = days_late($rec['due_date'], $today);
            $fine = $late * FINE_PER_DAY;
            run($db, "UPDATE issues SET status = 'Returned', return_date = ?, fine_amount = ? WHERE id = ?",
                'sii', [$today, $fine, (int)$_POST['id']]);
            run($db, "UPDATE books SET available_copies = available_copies + 1 WHERE id = ?", 'i', [(int)$rec['book_id']]);
            $db->commit();
            flash('success', $late > 0
                ? "Book returned $late day(s) late. Fine to collect: Rs. $fine."
                : 'Book returned on time. No fine.');
        }
    } catch (mysqli_sql_exception $e) {
        $db->rollback();
        flash('danger', 'Could not process the return because of a database error.');
    }
    redirect('issues.php');
}

/* ---------- DELETE a returned record ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_ok()) {
        flash('danger', 'Security check failed. Please try again.');
        redirect('issues.php');
    }
    $st = run($db, "DELETE FROM issues WHERE id = ? AND status = 'Returned'", 'i', [(int)($_POST['id'] ?? 0)]);
    if ($st->affected_rows > 0) { flash('success', 'Issue record deleted.'); }
    else                        { flash('danger', 'Only returned records can be deleted.'); }
    redirect('issues.php');
}

/* ---------- SEARCH / FILTER ---------- */
$q      = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? '';

$sql = "SELECT i.*, b.title, s.full_name, s.enrollment_no
        FROM issues i
        JOIN books b ON b.id = i.book_id
        JOIN students s ON s.id = i.student_id
        WHERE 1=1";
$types = '';
$args  = [];
if ($q !== '') {
    $sql .= " AND (b.title LIKE ? OR s.full_name LIKE ? OR s.enrollment_no LIKE ?)";
    $like = '%' . $q . '%';
    $types .= 'sss';
    array_push($args, $like, $like, $like);
}
if ($filter === 'issued')   { $sql .= " AND i.status = 'Issued'"; }
if ($filter === 'returned') { $sql .= " AND i.status = 'Returned'"; }
if ($filter === 'overdue')  { $sql .= " AND i.status = 'Issued' AND i.due_date < ?"; $types .= 's'; $args[] = $today; }
$sql .= " ORDER BY i.id DESC";
$rows = run($db, $sql, $types, $args)->get_result();

$pageTitle = 'Issue Records';
$active = 'issues';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Issue Records</h1>
        <p class="subtle">Track issued books, process returns and see fines.</p>
    </div>
    <a class="btn" href="issue.php">+ Issue Book</a>
</div>

<form class="toolbar" method="get" action="issues.php">
    <input class="grow" type="text" name="q" placeholder="Search by book, student or enrollment number" value="<?= h($q) ?>">
    <select name="status" aria-label="Status">
        <option value="">All records</option>
        <option value="issued"   <?= $filter === 'issued'   ? 'selected' : '' ?>>Currently issued</option>
        <option value="overdue"  <?= $filter === 'overdue'  ? 'selected' : '' ?>>Overdue</option>
        <option value="returned" <?= $filter === 'returned' ? 'selected' : '' ?>>Returned</option>
    </select>
    <button class="btn" type="submit">Search</button>
    <?php if ($q !== '' || $filter !== ''): ?><a class="btn btn-grey" href="issues.php">Clear</a><?php endif; ?>
</form>

<p class="result-count"><?= $rows->num_rows ?> record(s) found</p>
<div class="card card-flush">
<div class="table-scroll">
<table class="data">
    <thead>
        <tr><th>#</th><th>Book</th><th>Student</th><th>Issued</th><th>Due</th><th>Returned</th><th>Status</th><th>Fine</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if ($rows->num_rows === 0): ?>
        <tr class="empty-row"><td colspan="9">No records found.</td></tr>
    <?php endif; ?>
    <?php while ($r = $rows->fetch_assoc()):
        $isReturned = $r['status'] === 'Returned';
        $isOverdue  = !$isReturned && $r['due_date'] < $today;
        $fine = $isReturned ? (int)$r['fine_amount'] : fine_for($r['due_date'], $today);
    ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><strong><?= h($r['title']) ?></strong></td>
            <td><?= h($r['full_name']) ?><span class="cell-sub"><?= h($r['enrollment_no']) ?></span></td>
            <td><?= fdate($r['issue_date']) ?></td>
            <td><?= fdate($r['due_date']) ?></td>
            <td><?= fdate($r['return_date']) ?></td>
            <td>
                <?php if ($isReturned): ?><span class="badge b-ok">Returned</span>
                <?php elseif ($isOverdue): ?><span class="badge b-bad">Overdue</span>
                <?php else: ?><span class="badge b-warn">Issued</span><?php endif; ?>
            </td>
            <td><?= $fine > 0 ? 'Rs. ' . $fine : '-' ?></td>
            <td>
                <?php if (!$isReturned): ?>
                    <form class="inline-form" method="post" data-confirm="Mark this book as returned?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-ok" type="submit">Return</button>
                    </form>
                <?php else: ?>
                    <form class="inline-form" method="post" data-confirm="Delete this record permanently?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
