<?php
// poll.php — Handles poll voting requests

require_once 'inc/bootstrap.php';
require_once 'inc/functions.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['vote_poll'], $_POST['poll_id'], $_POST['option_id'], $_POST['board'])
) {
    if (!openBoard($_POST['board'])) {
        error('Invalid board.');
    }

    $poll_id   = (int) $_POST['poll_id'];
    $option_id = (int) $_POST['option_id'];

    $success = vote_poll($poll_id, $option_id);

    // Check if the request is an AJAX request
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Vote recorded.' : 'You have already voted.'
        ]);
    } else {
        if (!$success) {
            error('You have already voted.');
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
    exit;
} else {
    error('Invalid request.');
}
