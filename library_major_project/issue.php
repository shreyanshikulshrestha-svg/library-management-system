<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$sel = ['student_id' => '', 'book_id' => '', 'days' => DEFAULT_LOAN_DAYS];

if (is_post()) {
    if (!csrf_ok()) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    }
    $sel['student_id'] = (int)($_POST['student_id'] ?? 0);
    $sel['book_id']    = (int)($_POST['book_id'] ?? 0);
    $sel['days']       = (int)($_POST['days'] ?? 0);

    if ($sel['student_id'] <= 0)                $errors[] = 'Please select a student.';
    if ($sel['book_id'] <= 0)                   $errors[] = 'Please select a book.';
    if ($sel['days'] < 1 || $sel['days'] > 30)  $errors[] = 'Loan period must be between 1 and 30 days.';

    if (!$errors) {
        $db->begin_transaction();
        try {
            // lock the book row so the last copy cannot be issued twice
            $book = run($db, "SELECT title, available_copies FROM books WHERE id = ? FOR UPDATE",
                        'i', [$sel['book_id']])->get_result()->fetch_assoc();
            $stu  = run($db, "SELECT full_name FROM students WHERE id = ?",
                        'i', [$sel['student_id']])->get_result()->fetch_assoc();

            if (!$book || !$stu) {
                $errors[] = 'The selected book or student does not exist.';
            } elseif ((int)$book['available_copies'] < 1) {
                $errors[] = '"' . $book['title'] . '" has no copies available right now.';
            } else {
                $holding = (int)run($db, "SELECT COUNT(*) c FROM issues WHERE student_id = ? AND status = 'Issued'",
                                    'i', [$sel['student_id']])->get_result()->fetch_assoc()['c'];
                $same    = (int)run($db, "SELECT COUNT(*) c FROM issues WHERE student_id = ? AND book_id = ? AND status = 'Issued'",
                                    'ii', [$sel['student_id'], $sel['book_id']])->get_result()->fetch_assoc()['c'];
                if ($holding >= MAX_ISSUE_LIMIT) {
                    $errors[] = $stu['full_name'] . ' already holds the maximum of ' . MAX_ISSUE_LIMIT . ' books.';
                } elseif ($same > 0) {
                    $errors[] = $stu['full_name'] . ' already has a copy of this book.';
                }
            }

            if (!$errors) {
                $issueDate = date('Y-m-d');
                $dueDate   = date('Y-m-d', strtotime('+' . $sel['days'] . ' days'));
                run($db, "INSERT INTO issues (book_id, student_id, issue_date, due_date, status) VALUES (?,?,?,?, 'Issued')",
                    'iiss', [$sel['book_id'], $sel['student_id'], $issueDate, $dueDate]);
                run($db, "UPDATE books SET available_copies = available_copies - 1 WHERE id = ?", 'i', [$sel['book_id']]);
                $db->commit();
                flash('success', 'Issued "' . $book['title'] . '" to ' . $stu['full_name'] . '. Due on ' . fdate($dueDate) . '.');
                redirect('issues.php');
            }
            $db->rollback();
        } catch (mysqli_sql_exception $e) {
            $db->rollback();
            $errors[] = 'Could not issue the book because of a database error.';
        }
    }
}

$studentList = run($db, "SELECT s.id, s.full_name, s.enrollment_no,
                                (SELECT COUNT(*) FROM issues i WHERE i.student_id = s.id AND i.status='Issued') AS holding
                         FROM students s ORDER BY s.full_name")->get_result();
$bookList = run($db, "SELECT id, title, available_copies FROM books WHERE available_copies > 0 ORDER BY title")->get_result();

$pageTitle = 'Issue Book';
$active = 'issue';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Issue a Book</h1>
        <p class="subtle">Choose a student and an available book. Stock is updated automatically.</p>
    </div>
    <a class="btn btn-grey" href="issues.php">View issue records</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= h($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card">
<form method="post" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field">
            <label for="student_id">Student <span class="req">*</span></label>
            <select id="student_id" name="student_id" data-rule="select">
                <option value="">-- Select student --</option>
                <?php while ($s = $studentList->fetch_assoc()): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (int)$sel['student_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= h($s['full_name']) ?> (<?= h($s['enrollment_no']) ?>) - holding <?= (int)$s['holding'] ?>/<?= MAX_ISSUE_LIMIT ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="book_id">Book <span class="req">*</span></label>
            <select id="book_id" name="book_id" data-rule="select">
                <option value="">-- Select book --</option>
                <?php while ($b = $bookList->fetch_assoc()): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= (int)$sel['book_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                        <?= h($b['title']) ?> (<?= (int)$b['available_copies'] ?> available)
                    </option>
                <?php endwhile; ?>
            </select>
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="days">Loan period (days) <span class="req">*</span></label>
            <input type="text" id="days" name="days" data-rule="days" data-digits maxlength="2" inputmode="numeric" value="<?= (int)$sel['days'] ?>">
            <div class="field-hint">1 to 30 days. Fine: Rs. <?= FINE_PER_DAY ?> per day after the due date.</div>
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label>Due date</label>
            <div class="due-preview" id="duePreview">-</div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-ok" type="submit">Issue Book</button>
        <a class="btn btn-grey" href="issues.php">Cancel</a>
    </div>
</form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
