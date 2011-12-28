<?php
//Wordpress deprecated

ini_set('display_errors','off');

//Global WordPress

global $wpdb;

if(!isset($wpdb))
{
    require_once('../../../../wp-config.php');
    require_once('../../../../wp-load.php');
    require_once('../../../../wp-includes/wp-db.php');
}

require 'Slim/Slim.php';
$app = new Slim( array(
    'log.enable' => true,
    'log.path' => './logs',
    'log.level' => 4,
    'debug' =>true
  )

);

//GET a list of feeds
$app->get('/feeds',function(){
  echo 'A LIST OF FEEDS';

});

function wprss_list_feeds(){
  global $wpdb;
  $sql = "Select * from wp_wprss_feeds";
  $myrows = $wpdb->get_results($sql);
  
  
  echo json_encode($myrows);
  

}

$app->run();
?>
