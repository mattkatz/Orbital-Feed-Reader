<div id="subscribe-window" class="modal-window invisible">
<script type="text/x-handlebars">
  {{#view Wprss.FeedsForm}}
    {{view Em.TextField 
      placeholder="Drag or copy paste a feed here" 
      viewName="urlField"}}
    <button type='submit'>Add Feed</button>
    <div class="horizontal-form">
      {{#if feedCandidate}}
        {{#with feedCandidate}}
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
                  {{#view Em.Button target="Wprss.selectedFeedController" action="unsubscribe"}} Unsubscribe {{/view}}
                </label>
              {{/if}}
              <div class="clickable" {{action "saveFeed" }}>
              Save {{feed_name}}
              </div>
        {{/with }}
      {{/if}}
      {{#if possibleFeeds }}
        <div>
          We found {{possibleFeeds.length }} feeds:
        </div>
        {{#each possibleFeeds}}
              <div class="possibleFeed" {{action "findFeed" target="parentView.parentView.parentView" context="url" }} >
                {{url}} 
              </div>
        {{/each}}
      {{/if}}
    </div>
  {{/view}}

</script>
</div>
