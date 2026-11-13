<?php
require __DIR__ . '/includes/init.php';

$today = date('Y-m-d');
$titles    = run($db, "SELECT COUNT(*) c FROM books")->get_result()->fetch_assoc()['c'];
$copies    = run($db, "SELECT COALESCE(SUM(total_copies),0) c FROM books")->get_result()->fetch_assoc()['c'];
$available = run($db, "SELECT COALESCE(SUM(available_copies),0) c FROM books")->get_result()->fetch_assoc()['c'];
$students  = run($db, "SELECT COUNT(*) c FROM students")->get_result()->fetch_assoc()['c'];
$issued    = run($db, "SELECT COUNT(*) c FROM issues WHERE status='Issued'")->get_result()->fetch_assoc()['c'];
$overdue   = run($db, "SELECT COUNT(*) c FROM issues WHERE status='Issued' AND due_date < ?", 's', [$today])->get_result()->fetch_assoc()['c'];

$recent = run($db, "SELECT i.id, i.due_date, i.status, b.title, s.full_name
                    FROM issues i
                    JOIN books b ON b.id = i.book_id
                    JOIN students s ON s.id = i.student_id
                    ORDER BY i.id DESC LIMIT 5")->get_result();

$pageTitle = 'Home';
$active = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Library Management System</h1>
    <p>A web application for managing the college library: keep the book catalogue up to date,
       register students, issue and return books, and calculate fines for late returns automatically.</p>
    <a class="btn btn-amber" href="issue.php">Issue a Book</a>
    <a class="btn btn-light" href="books.php">Browse Books</a>
</section>

<section class="stats" aria-label="Library summary">
    <div class="stat"><strong><?= (int)$titles ?></strong><span>Book titles (<?= (int)$copies ?> copies)</span></div>
    <div class="stat"><strong><?= (int)$available ?></strong><span>Copies available</span></div>
    <div class="stat"><strong><?= (int)$students ?></strong><span>Registered students</span></div>
    <div class="stat warn"><strong><?= (int)$issued ?></strong><span>Books currently issued</span></div>
    <div class="stat bad"><strong><?= (int)$overdue ?></strong><span>Overdue books</span></div>
</section>

<div class="two-col">
    <section class="card card-flush">
        <div style="padding:18px 20px 6px"><h2>Latest issue activity</h2></div>
        <div class="table-scroll">
        <table class="data">
            <thead><tr><th>Book</th><th>Student</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($recent->num_rows === 0): ?>
                <tr class="empty-row"><td colspan="4">No books have been issued yet.</td></tr>
            <?php endif; ?>
            <?php while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= h($r['title']) ?></td>
                    <td><?= h($r['full_name']) ?></td>
                    <td><?= fdate($r['due_date']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'Returned'): ?><span class="badge b-ok">Returned</span>
                        <?php elseif ($r['due_date'] < $today): ?><span class="badge b-bad">Overdue</span>
                        <?php else: ?><span class="badge b-warn">Issued</span><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </section>

    <aside class="card">
        <h2>Library rules</h2>
        <ol class="steps">
            <li>Each student can hold up to <?= MAX_ISSUE_LIMIT ?> books.</li>
            <li>Standard loan period is <?= DEFAULT_LOAN_DAYS ?> days.</li>
            <li>Late fine is Rs. <?= FINE_PER_DAY ?> per day after the due date.</li>
            <li>Copies are updated automatically on issue and return.</li>
        </ol>
        <div class="btn-row" style="margin-top:16px">
            <a class="btn btn-sm" href="book_form.php">+ Add Book</a>
            <a class="btn btn-sm btn-grey" href="student_form.php">+ Add Student</a>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
