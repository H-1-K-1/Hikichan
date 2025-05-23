<?php
// A script to recount bumps to recover from a last-page-bump attack
// or to be run after the KusabaX Migration. (Unified posts table version)

require dirname(__FILE__) . '/inc/cli.php';

if (!isset($argv[1])) {
    die("Usage: tools/recount-bumps.php board_uri\n");
}
$board = $argv[1];

// Select all threads for this board
$q = query("SELECT `id`, `bump`, `time` FROM ``posts`` WHERE `board` = " . $pdo->quote($board) . " AND `thread` IS NULL");
while ($val = $q->fetch()) {
    // Find the latest post time in the thread that is not sage, or the OP itself
    $lc = prepare("SELECT MAX(`time`) AS `aq` FROM ``posts`` WHERE `board` = :board AND ((`thread` = :thread AND (`email` IS NULL OR `email` != 'sage')) OR `id` = :thread)");
    $lc->bindValue(":board", $board);
    $lc->bindValue(":thread", $val['id']);
    $lc->execute();

    $f = $lc->fetch();
    if ($val['bump'] != $f['aq']) {
        $query = prepare("UPDATE ``posts`` SET `bump` = :bump WHERE `id` = :id");
        $query->bindValue(":bump", $f['aq']);
        $query->bindValue(":id", $val['id']);
        $query->execute();
        echo("Thread {$val['id']} - to be {$val['bump']} -> {$f['aq']}\n");
    } else {
        echo("Thread {$val['id']} ok\n");
    }
}

echo("done\n");