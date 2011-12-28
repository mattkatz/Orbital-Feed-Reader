<?php

function wprss_list_feeds(){
  global $wpdb;
  $sql = "Select * from wp_wprss_feeds";
  $myrows = $wpdb->get_results($sql);
  
  
  echo json_encode($myrows);
  

}

?>
