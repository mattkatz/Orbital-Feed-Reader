<div id="orbital_settings">
    <div class="wrap">
        <h2>Orbital Settings</h2>
    <p>If you are looking for a way to edit the names or members of your feeds, click the pencil icon (✎) on the feed list.</p>
    <p>You can <a href="<?php echo site_url("?export_opml=".get_current_user_id())  ?>">export your feed list in OPML format</a> at any time. If you are logged in, you'll get your <b>private</b> feeds in there. If you aren't logged in, the same link will filter out private feeds. This is useful if you want to move your list to another feed reader. </p>
    <p><small>If they don't give you this same option, maybe think twice about using that kind of software.</small></p>
      <form action="options.php" method="POST">
        <?php settings_fields( 'orbital-settings-group' ); ?>
        <?php do_settings_sections( 'orbital-plugin-settings' ); ?>
        <?php submit_button(); ?>
      </form>
    </div>

</div>
