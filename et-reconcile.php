<?php

/**
 * @file
 * Script to collect Drupal IDs that are missing in ExactTarget.
 */

function parse_file($work_dir, $file) {
  $in = fopen($work_dir . '/in/' . $file, 'r');
  $out = fopen($work_dir . '/out/' . $file, 'w');
  $err = fopen($work_dir . '/err/' . $file, 'w');
  fputcsv($err, array('row', 'message'));

  drush_log('Finding uids for ' . $file);

  $count = 1;
  while ($row = fgetcsv($in)) {
    $uid = FALSE;
    $out_row = $row;
    $errors = array();

    $res_count = 0;
    $result = db_query_slave("SELECT uid FROM {users} WHERE mail='%s'", $row[0]);
    while ($account = db_fetch_object($result)) {
      if (!$uid) {
        $uid = $account->uid;
      }
      else {
        $uid .= '|' . $account->uid;
      }
      $res_count++;
    }
    if ($res_count > 1) {
      $errors[] = array($count, 'multiple drupal users found for ' . $row[0]);
    }

    if ($uid) {
      $out_row[] = $uid;
    }
    else {
      $errors[] = array($count, 'no uid found for ' . $row[0]);
    }
    $count++;

    if (count($out_row) > count($row)) {
      fputcsv($out, $out_row);
    }

    if (count($errors)) {
      foreach ($errors as $error) {
        fputcsv($err, $error);
      }
    }
  }

  fclose($in);
  fclose($out);
  fclose($err);

  drush_log('Finished finding uids for ' . $file);
}

$work_dir = dirname(__FILE__);
$in = glob($work_dir . '/in/*.csv');
foreach ($in as $in_file) {
  parse_file($work_dir, basename($in_file));
}

