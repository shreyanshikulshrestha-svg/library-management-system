<?php
// Expects: $pageTitle (string) and $active (nav key) set by the page.
$pageTitle = $pageTitle ?? 'Library Management System';
$active    = $active ?? '';
$nav = [
    'home'     => ['index.php',     'Home'],
    'books'    => ['books.php',     'Books'],
    'students' => ['students.php',  'Students'],
    'issue'    => ['issue.php',     'Issue Book'],
    'issues'   => ['issues.php',    'Issue Records'],
];
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Library Management System - PHP and MySQL web application">
    <title><?= h($pageTitle) ?> | Library Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/validate.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="wrap header-row">
        <a class="brand" href="index.php">
            <span class="brand-mark">LMS</span>
            <span class="brand-text">Central Library<small>Management System</small></span>
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <?php foreach ($nav as $key => [$url, $label]): ?>
                <a href="<?= $url ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main class="wrap page">
<?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type']) ?>" data-autohide role="status"><?= h($flash['message']) ?></div>
<?php endif; ?>
