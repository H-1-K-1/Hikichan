<?php
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

    $result = vote_poll($poll_id, $option_id);

    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        $message = 'Unknown error.';

        if ($result === true) {
            $message = 'Vote recorded.';
        } elseif ($result === 'closed') {
            $message = 'Poll has ended.';
        } elseif ($result === 'limit') {
            $message = 'Vote limit reached.';
        } else {
            $message = 'Invalid vote or duplicate.';
        }

        echo json_encode([
            'success' => $result === true,
            'message' => $message
        ]);
    } else {
        switch ($result) {
            case true:
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                break;
            case 'closed':
                error('Poll has ended.');
                break;
            case 'limit':
                error('You have reached the total vote limit.');
                break;
            default:
                error('You have already voted or the vote is invalid.');
        }
    }
    exit;
} else {
    error('Invalid request.');
}
