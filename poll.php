<?php
require_once 'inc/bootstrap.php';
require_once 'inc/functions.php';

function json_error($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['vote_poll'], $_POST['poll_id'], $_POST['option_id'], $_POST['board'])
    ) {
        if (!openBoard($_POST['board'])) {
            if (
                isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                json_error('Invalid board.');
            } else {
                error('Invalid board.');
            }
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
            } elseif ($result === 'duplicate') {
                $message = 'You have already voted.';
            } else {
                $message = 'Invalid vote';
            }

            echo json_encode([
                'success' => $result === true,
                'message' => $message
            ]);
            exit;
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
                case 'duplicate':
                    error('You have already voted.');
                    break;
                default:
                    error('The vote is invalid.');
            }
        }
        exit;
    } else {
        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            json_error('Invalid request.');
        } else {
            error('Invalid request.');
        }
    }
} catch (Exception $e) {
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        json_error('Server error: ' . $e->getMessage());
    } else {
        error('Server error: ' . $e->getMessage());
    }
}