<?php
require __DIR__ . '/includes/init.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stu = ['full_name' => '', 'email' => '', 'enrollment_no' => '', 'phone' => ''];
$errors = [];

if ($id > 0) {
    $row = run($db, "SELECT * FROM students WHERE id = ?", 'i', [$id])->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Student not found.');
        redirect('students.php');
    }
    $stu = $row;
}

if (is_post()) {
    if (!csrf_ok()) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    }
    $stu['full_name']     = trim($_POST['full_name'] ?? '');
    $stu['email']         = trim($_POST['email'] ?? '');
    $stu['enrollment_no'] = trim($_POST['enrollment_no'] ?? '');
    $stu['phone']         = trim($_POST['phone'] ?? '');

    if (!ok_name($stu['full_name']))        $errors[] = 'Name must contain letters only (2-100 characters).';
    if (!ok_email($stu['email']))          $errors[] = 'Enter a valid email address.';
    if (!ok_enroll($stu['enrollment_no'])) $errors[] = 'Enrollment number must be 5-20 letters or digits.';
    if (!ok_phone($stu['phone']))          $errors[] = 'Phone must be a 10-digit mobile number starting with 6-9.';

    if (!$errors) {
        try {
            if ($id > 0) {
                run($db, "UPDATE students SET full_name=?, email=?, enrollment_no=?, phone=? WHERE id=?",
                    'ssssi', [$stu['full_name'], $stu['email'], $stu['enrollment_no'], $stu['phone'], $id]);
                flash('success', 'Student details updated.');
            } else {
                run($db, "INSERT INTO students (full_name, email, enrollment_no, phone) VALUES (?,?,?,?)",
                    'ssss', [$stu['full_name'], $stu['email'], $stu['enrollment_no'], $stu['phone']]);
                flash('success', 'Student registered successfully.');
            }
            redirect('students.php');
        } catch (mysqli_sql_exception $e) {
            $errors[] = ((int)$e->getCode() === 1062)
                ? 'A student with this email or enrollment number already exists.'
                : 'Could not save the student because of a database error.';
        }
    }
}

$pageTitle = $id ? 'Edit Student' : 'Add Student';
$active = 'students';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1><?= $id ? 'Edit Student' : 'Register New Student' ?></h1>
        <p class="subtle">Fields marked * are required.</p>
    </div>
    <a class="btn btn-grey" href="students.php">&larr; Back to students</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= h($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card">
<form method="post" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="form-grid">
        <div class="field">
            <label for="full_name">Full name <span class="req">*</span></label>
            <input type="text" id="full_name" name="full_name" data-rule="name" maxlength="100" value="<?= h($stu['full_name']) ?>">
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="enrollment_no">Enrollment number <span class="req">*</span></label>
            <input type="text" id="enrollment_no" name="enrollment_no" data-rule="enroll" maxlength="20" value="<?= h($stu['enrollment_no']) ?>">
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" data-rule="email" maxlength="120" value="<?= h($stu['email']) ?>">
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="phone">Mobile number <span class="req">*</span></label>
            <input type="text" id="phone" name="phone" data-rule="phone" data-digits maxlength="10" inputmode="numeric" value="<?= h($stu['phone']) ?>">
            <div class="field-error"></div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><?= $id ? 'Update Student' : 'Register Student' ?></button>
        <a class="btn btn-grey" href="students.php">Cancel</a>
    </div>
</form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
