<?php

require_once dirname(__FILE__) . '/../inc/cli.php';

// For unified posts table
query('ALTER TABLE ``posts`` MODIFY `password` varchar(64) DEFAULT NULL;') or error(db_error());
$query = query("SELECT DISTINCT `password` FROM ``posts`` WHERE `password` IS NOT NULL AND `password` != ''");
while($entry = $query->fetch(PDO::FETCH_ASSOC)) {
    $update_query = prepare("UPDATE ``posts`` SET `password` = :password WHERE `password` = :password_org");
    $update_query->bindValue(':password', hashPassword($entry['password']));
    $update_query->bindValue(':password_org', $entry['password']);
    $update_query->execute() or error(db_error($update_query));
}