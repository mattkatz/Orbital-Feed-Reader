<div id="subscribe-window" class="modal-window invisible">
<script type="text/x-handlebars">
  {{#view Wprss.FeedsForm}}
    {{view Em.TextField 
      placeholder="Drag or copy paste a feed here" 
      viewName="urlField"}}
    <a class='button' {{action submit}} >Add Feed</a>
    <div class="clickable dismiss" {{action "dismiss"}}>
      X
    </div>
    <div class="horizontal-form">
      {{#if view.possibleFeeds }}
        <div>
          We found {{view.possibleFeeds.length }} feeds:
        </div>
        {{#each view.possibleFeeds}}
              <div class="possibleFeed clickable" {{action findFeed "url" target="parentView" }} >
                {{url}} 
              </div>
        {{/each}}
      {{/if}}
      {{#if view.feedCandidate}}
        {{#with view.feedCandidate}}
              {{view Em.TextField valueBinding="feed_name" class="heading" }}
              <label>Feed Url
              {{view Em.TextField valueBinding="feed_url" }}
              </label>
              <label>Site Url
                {{view Em.TextField valueBinding="site_url" }}
              </label>
              <label>
                {{view Em.Checkbox valueBinding="is_private" title=""}}
                This Feed is Private! Don't show it to other people.
              </label>
              {{#if  feed_id}}
                <label>
                  Get rid of this feed! Seriously! 
                  {{#view Em.Button target="Wprss.selectedFeedController" action="unsubscribe"}} Unsubscribe {{/view}}
                </label>
              {{/if}}
              <div class="clickable button" {{action "saveFeed" }}>
              Save {{feed_name}}
              </div>
        {{/with }}
      {{/if}}
    </div>
  {{/view}}

</script>
</div>
