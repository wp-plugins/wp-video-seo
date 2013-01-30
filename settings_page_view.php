<div class="wrap">
    <div id="poststuff"><div id="post-body">
	<h2>WP Video SEO - v<?php echo $this->plugin_version;?></h2>

	<?php if ( $message ): ?>
	<div class="updated below-h2"><p><?php echo $message; ?></p></div>
	<?php endif; ?>
    <div class="postbox">
    <h3><label for="title">General Settings</label></h3>
    <div class="inside">
    <p>Click on the following button to generate a video sitemap for your site. The sitemap (sitemap-video.xml) will be generated in the root directory of your site. You just need to generate it once.</p>
	<form method="post" action="">
		<input type="hidden" name="token" value="wp-video-seo-generate-sitemap" />
		<input type="submit" name="submit" value="Generate Video Sitemap" />
	</form>
    </div></div>

    </div></div>
</div>