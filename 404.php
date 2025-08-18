<?php
  require_once 'inc/bootstrap.php';

  die(
    Element('page.html', array(
      'title' => _('NOT FOUND!'),
      'config' => $config,
      'nojavascript' => true,
      'body' => Element('404.html', array(
        'config' => $config // <-- pass config here
      ))
    ))
  ); 
?>