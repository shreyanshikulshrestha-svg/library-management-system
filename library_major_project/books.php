<?php
require __DIR__ . '/includes/init.php';

/* ---------- DELETE a book ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_ok()) {
        flash('danger', 'Security check failed. Please try again.');
        redirect('books.php');
    }
    try {
        $st = run($db, "DELETE FROM books WHERE id = ?", 'i', [(int)($_POST['id'] ?? 0)]);
        if ($st->affected_rows > 0) { flash('success', 'Book deleted successfully.'); }
        else                        { flash('danger', 'Book not found.'); }
    } catch (mysqli_sql_exception $e) {
        flash('danger', 'This book has issue records, so it cannot be deleted.');
    }
    redirect('books.php');
}

/* ---------- SEARCH / FILTER ---------- */
$q   = trim($_GET['q'] ?? '');
$cat = trim($_GET['category'] ?? '');

$sql = "SELECT * FROM books WHERE 1=1";
$types = '';
$args  = [];
if ($q !== '') {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $like = '%' . $q . '%';
    $types .= 'sss';
    array_push($args, $like, $like, $like);
}
if ($cat !== '') {
    $sql .= " AND category = ?";
    $types .= 's';
    $args[] = $cat;
}
$sql .= " ORDER BY title ASC";
$books = run($db, $sql, $types, $args)->get_result();
$cats  = run($db, "SELECT DISTINCT category FROM books ORDER BY category")->get_result();

$pageTitle = 'Books';
$active = 'books';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Books</h1>
        <p class="subtle">Search, edit or remove books from the catalogue.</p>
    </div>
    <a class="btn" href="book_form.php">+ Add Book</a>
</div>

<form class="toolbar" method="get" action="books.php">
    <input class="grow" type="text" name="q" placeholder="Search by title, author or ISBN" value="<?= h($q) ?>">
    <select name="category" aria-label="Category">
        <option value="">All categories</option>
        <?php while ($c = $cats->fetch_assoc()): ?>
            <option value="<?= h($c['category']) ?>" <?= $cat === $c['category'] ? 'selected' : '' ?>><?= h($c['category']) ?></option>
        <?php endwhile; ?>
    </select>
    <button class="btn" type="submit">Search</button>
    <?php if ($q !== '' || $cat !== ''): ?><a class="btn btn-grey" href="books.php">Clear</a><?php endif; ?>
</form>

<p class="result-count"><?= $books->num_rows ?> book(s) found</p>
<div class="card card-flush">
<div class="table-scroll">
<table class="data" id="booksTable">
    <thead>
        <tr><th>#</th><th>Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Copies</th><th>Availability</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if ($books->num_rows === 0): ?>
        <tr class="empty-row"><td colspan="8">No books match your search.</td></tr>
    <?php endif; ?>
    <?php while ($b = $books->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$b['id'] ?></td>
            <td><strong><?= h($b['title']) ?></strong></td>
            <td><?= h($b['author']) ?></td>
            <td><?= h($b['isbn']) ?></td>
            <td><span class="badge b-info"><?= h($b['category']) ?></span></td>
            <td><?= (int)$b['available_copies'] ?> / <?= (int)$b['total_copies'] ?></td>
            <td>
                <?php if ($b['available_copies'] > 0): ?><span class="badge b-ok">Available</span>
                <?php else: ?><span class="badge b-bad">All issued</span><?php endif; ?>
            </td>
            <td>
                <div class="btn-row">
                    <a class="btn btn-sm btn-grey" href="book_form.php?id=<?= (int)$b['id'] ?>">Edit</a>
                    <form class="inline-form" method="post" data-confirm="Delete this book permanently?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
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
