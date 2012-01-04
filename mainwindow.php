<style type="text/css">
  #wprss-feedlist{
    float:right;
    border-left: 1px solid #dddddd;
    padding: 10px;
  }
    
</style>

<div id='wprss-container'>
  <div id="wprss-feedlist">
  <div>CURRENT USER: <?php 
  $curusr = wp_get_current_user();
  echo $curusr->ID; 

 ?></div>
  <h2>The Feeds</h2>
    <script type="text/x-handlebars">
    <ul class="feeds">
    {{#each Wprss.feedsController}}
      <li class="feed"><a {{bindAttr href="site_url"}}>{{feed_name}}</a></li>
    {{/each}}

    </ul>


    </script>
  </div>
  <div id="wprss-content">
  No feeds displayed
    <script type="text/x-handlebars">
    <ul class="entries">
      {{#each Wprss.entriesController}}
        <li class="entry">
          <a {{bindAttr href="link"}}><h2>{{title}}</h2></a>
          {{description}}
        </li>
      {{/each}}
    </ul>
    </script>
  </div>
</div>
<?php

?>
