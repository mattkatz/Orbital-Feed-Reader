<div id="orbital_settings">
    <div class="wrap">
        <h2>Orbital Settings</h2>
   <p>If you are looking for a way to edit the names or members of your feeds, click the pencil icon (✎) on the feed list.</p>
        <form action="options.php" method="POST">
            <?php settings_fields( 'orbital-settings-group' ); ?>
            <?php do_settings_sections( 'orbital-plugin-settings' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>

</div>
