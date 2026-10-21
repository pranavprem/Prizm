<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_session();

$pid = prizm_id($_POST['pid'] ?? $_GET['pid'] ?? null);
$stars = (int) ($_POST['stars'] ?? $_GET['stars'] ?? 0);
$review = (string) ($_POST['review'] ?? $_GET['review'] ?? '');
$redirect = isset($_POST['redirect']) || isset($_GET['redirect']);

try {
    $reviewer = prizm_current_user();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'The catalog is unavailable.';
    return;
}

if ($reviewer === null) {
    echo 'Please log in to add reviews';
    return;
}

$requested = $_POST['uid'] ?? $_GET['uid'] ?? null;
if ($requested !== null && prizm_id($requested) !== (int) $_SESSION['uid']) {
    http_response_code(403);
    echo 'Please log in to add reviews';
    return;
}

if (!prizm_csrf_ok()) {
    http_response_code(400);
    echo 'The request was rejected.';
    return;
}

if ($pid === null) {
    http_response_code(400);
    echo 'Review was not saved.';
    return;
}

try {
    $saved = prizm_add_review($pid, (int) $_SESSION['uid'], $stars, $review);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $saved = false;
}

if (!$saved) {
    http_response_code(400);
    echo 'Review was not saved.';
    return;
}

if ($redirect) {
    header('Location: displayproduct.php?pid=' . $pid, true, 303);
    return;
}

echo 'New review added ';
