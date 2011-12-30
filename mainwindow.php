<style type="text/css">
  #wprss-feedlist{
    float:right;
    border-left: 1px solid #dddddd;
    padding: 10px;
  }
    
</style>

<div id='wprss-container'>
  <div id="wprss-feedlist">
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
  </div>
</div>
<?php

?>
