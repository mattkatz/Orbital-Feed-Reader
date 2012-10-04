<div id='wprss-container'>
  <div id="commandbar" class="quicklinks">
  <ul>
    <li class="command">
    <a href="<?echo site_url();?>?export_opml=<?php echo wp_get_current_user()->ID;?>">Export OPML</a>
    </li>
    <li class="command">
    <script type="text/x-handlebars" >
      {{#view Em.Button classBinding="isActive"
        tagName="span"
        target="Wprss.feedsController"
        action="showOpmlImport" }}
        Import OPML
      {{/view}}
    </script>
    </li>
  </ul>
  </div>
  <div id="wprss-content" class="horizontal-form">
    <script type="text/x-handlebars" >
    {{#if Wprss.selectedFeedController.content}}
      {{#view Wprss.FeedView }}
      {{#with Wprss.selectedFeedController.content}}
          {{view Em.TextField valueBinding="feed_name" class="heading" }}
          <label>Feed Url
          {{view Em.TextField valueBinding="feed_url" }}
          </label>
          <label>Site Url
            {{view Em.TextField valueBinding="site_url" }}
          </label>
          <label>
            {{view Em.Checkbox valueBinding="is_private" title="This Feed is Private! Don't show it to other people."}}
          </label>
          {{#if  feed_id}}
            <label>
              Get rid of this feed! Seriously! 
              {{#view Em.Button target="parentView" action="unsubscribe"}} Unsubscribe {{/view}}
            </label>
          {{/if}}
          <div>
          {{#view Em.Button target="Wprss.selectedFeedController" action="saveFeed" }}Save{{/view}}
          </div>
      {{/with }}
      {{/view}}
      
    {{else}}
      <div class="no-feed-displayed">Choose a feed on the right to edit</div>
    {{/if}}
    </script>
  </div>
<?php
require_once('feed_list.php');
require_once('import_opml.php');
?>

</div>
