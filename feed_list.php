<div id="wprss-feedlist">
      <div id="loadmoreajaxloader" style="display:none;">
        <center>
          <img src="<?php
            echo plugins_url("ajax-loader.gif", __FILE__);

          ?>">
          loading, just a sec...
        </center>
      </div>
    <div id='feed-head'>
      <h2>The Feeds</h2>
    <script type="text/x-handlebars" >
      <a href="#" 
      {{action showFeed className="button"
        tagName="span"
        target="Wprss.feedsController"
        }} title="Add and Subscribe to a Feed" >
         +
       </a>
    </script>
    </div>
    <ul id="feeds" >
    <script type="text/x-handlebars" >
    {{#each Wprss.feedsController}}
      {{#view Wprss.FeedsView contentBinding="this"}}
      {{#with view.content}}
        <li class="feed" {{bindAttr id="feed_id" }}>
          {{#if is_loading}}
            <img src="<?php echo plugins_url("ajax-loader.gif", __FILE__); ?>">
          {{/if}}
          
          {{feed_name}} <span class="feedcounter">{{unread_count}}</span></li>
      {{/with }}
      {{/view}}
    {{/each}}
    </script>

    </ul>
  </div>
<?php
require_once('subscription_window.php');
?>
<script type="text/javascript">
  var feeds= <?php
    require_once('backend.php');
    wprss_list_feeds();
  ?>;
  //Set everything up after page load
  jQuery(document).ready(function($){
    Wprss.feedsController.createFeeds(feeds);
    if(Wprss.feedsController.onInit){
      Wprss.feedsController.onInit();
    }
  });
</script>
