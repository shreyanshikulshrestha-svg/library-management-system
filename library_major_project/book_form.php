<?php
require __DIR__ . '/includes/init.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$book = ['title' => '', 'author' => '', 'isbn' => '', 'category' => '', 'total_copies' => 1];
$onLoan = 0;          // copies currently issued (only relevant when editing)
$errors = [];

/* ---------- Load existing book when editing ---------- */
if ($id > 0) {
    $row = run($db, "SELECT * FROM books WHERE id = ?", 'i', [$id])->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Book not found.');
        redirect('books.php');
    }
    $book   = $row;
    $onLoan = (int)$row['total_copies'] - (int)$row['available_copies'];
}

/* ---------- Save (insert or update) ---------- */
if (is_post()) {
    if (!csrf_ok()) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    }
    $book['title']        = trim($_POST['title'] ?? '');
    $book['author']       = trim($_POST['author'] ?? '');
    $book['isbn']         = trim($_POST['isbn'] ?? '');
    $book['category']     = trim($_POST['category'] ?? '');
    $book['total_copies'] = trim($_POST['total_copies'] ?? '');

    if (!ok_text($book['title'], 2, 150))    $errors[] = 'Title must be 2 to 150 characters.';
    if (!ok_name($book['author']))           $errors[] = 'Author name must contain letters only (2-100 characters).';
    if (!ok_isbn($book['isbn']))             $errors[] = 'ISBN must be 10 or 13 digits.';
    if (!ok_text($book['category'], 2, 50))  $errors[] = 'Category must be 2 to 50 characters.';
    if (!ctype_digit($book['total_copies']) || (int)$book['total_copies'] < 1 || (int)$book['total_copies'] > 999) {
        $errors[] = 'Total copies must be a whole number from 1 to 999.';
    } elseif ((int)$book['total_copies'] < $onLoan) {
        $errors[] = "Total copies cannot be less than the $onLoan copies currently issued.";
    }

    if (!$errors) {
        $total = (int)$book['total_copies'];
        try {
            if ($id > 0) {
                $available = $total - $onLoan;
                run($db, "UPDATE books SET title=?, author=?, isbn=?, category=?, total_copies=?, available_copies=? WHERE id=?",
                    'ssssiii', [$book['title'], $book['author'], $book['isbn'], $book['category'], $total, $available, $id]);
                flash('success', 'Book details updated.');
            } else {
                run($db, "INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES (?,?,?,?,?,?)",
                    'ssssii', [$book['title'], $book['author'], $book['isbn'], $book['category'], $total, $total]);
                flash('success', 'Book added to the catalogue.');
            }
            redirect('books.php');
        } catch (mysqli_sql_exception $e) {
            $errors[] = ((int)$e->getCode() === 1062)
                ? 'A book with this ISBN already exists.'
                : 'Could not save the book because of a database error.';
        }
    }
}

$pageTitle = $id ? 'Edit Book' : 'Add Book';
$active = 'books';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1><?= $id ? 'Edit Book' : 'Add New Book' ?></h1>
        <p class="subtle">Fields marked * are required.</p>
    </div>
    <a class="btn btn-grey" href="books.php">&larr; Back to books</a>
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
            <label for="title">Title <span class="req">*</span></label>
            <input type="text" id="title" name="title" data-rule="text" maxlength="150" value="<?= h($book['title']) ?>">
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="author">Author <span class="req">*</span></label>
            <input type="text" id="author" name="author" data-rule="name" maxlength="100" value="<?= h($book['author']) ?>">
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="isbn">ISBN <span class="req">*</span></label>
            <input type="text" id="isbn" name="isbn" data-rule="isbn" data-digits maxlength="13" inputmode="numeric" value="<?= h($book['isbn']) ?>">
            <div class="field-hint">10 or 13 digits, without hyphens.</div>
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="category">Category <span class="req">*</span></label>
            <input type="text" id="category" name="category" data-rule="text" data-max="50" maxlength="50" list="catList" value="<?= h($book['category']) ?>">
            <datalist id="catList">
                <option value="Computer Science"><option value="Programming"><option value="Web Technologies">
                <option value="Databases"><option value="Management"><option value="Fiction"><option value="Biography">
            </datalist>
            <div class="field-error"></div>
        </div>
        <div class="field">
            <label for="total_copies">Total copies <span class="req">*</span></label>
            <input type="text" id="total_copies" name="total_copies" data-rule="copies" data-digits maxlength="3" inputmode="numeric" value="<?= h($book['total_copies']) ?>">
            <?php if ($onLoan > 0): ?><div class="field-hint"><?= $onLoan ?> copy/copies currently issued.</div><?php endif; ?>
            <div class="field-error"></div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><?= $id ? 'Update Book' : 'Save Book' ?></button>
        <a class="btn btn-grey" href="books.php">Cancel</a>
    </div>
</form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
