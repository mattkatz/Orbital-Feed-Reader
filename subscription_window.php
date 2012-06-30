
  <div id="subscribe-window" class="modal-window invisible">
  <script type="text/x-handlebars">
    {{#view Wprss.FeedsForm}}
      {{view Em.TextField 
        placeholder="Drag or copy paste a feed here" 
        viewName="textField"}}
      <button type='submit'>Add Feed</button>
    {{/view}}
    {{view Wprss.AddFeedView 
      name="addFeedView" 
      placeholder="Drag or copy paste a feed here" 
      valueBinding="Wprss.feedFinder.url" }}

      {{#view Em.Button classBinding="isActive"
        target="Wprss.feedFinder"
        action="findFeed" }}
        Add Feed
      {{/view}}
      <div class="horizontal-form">
        
        {{#if Wprss.feedFinder.feedCandidate}}
          {{#view Wprss.FeedView }}
          {{#with Wprss.feedFinder.feedCandidate}}
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
              <div>
              {{#view Em.Button target="Wprss.feedFinder" action="saveFeed" }}Save{{/view}}
              </div>
          {{/with }}
          {{/view}}
        {{/if}}
        {{#if Wprss.feedFinder.possibleFeeds }}
          <div>
            We found {{Wprss.feedFinder.possibleFeeds.length }} feeds:
          </div>
          {{#each Wprss.feedFinder.possibleFeeds}}
            {{#view Wprss.PossibleFeedView contentBinding="this"}}
              {{#with content}}
                <div class="possibleFeed">
                {{url}}
                </div>
              {{/with}}
            {{/view}}
          {{/each}}
        {{/if}}
      </div>
  </script>

  </div>
