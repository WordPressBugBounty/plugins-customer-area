<?php /**
 * Template version: 2.0.0
 *
 * -= 2.0.0 =-
 * - Code cleanup
 *
 * -= 1.0.0 =-
 * - Initial version
 */ ?>

<?php /** @var $page_title string */ ?>
<?php /** @var $hero_message_primary string */ ?>
<?php /** @var $tabs array */ ?>
<?php /** @var $current_tab string */ ?>
<?php /** @var $tab_content_template string */ ?>

<div class="wrap cuar-dashboard-screen">

	<!-- HERO BLOCK -->

	<div class="cuar-hero">
		<h1><?php echo esc_html($page_title); ?></h1>

		<div class="cuar-messages">
			<p class="cuar-primary-message"><?php echo $hero_message_primary; ?></p>
		</div>

		<div class="cuar-badge">
			<img src="<?php echo esc_url($this->plugin->get_admin_theme_url() . '/assets/img/logo-badge.png'); ?>" />
			<p><?php printf(esc_html__('version %s', 'cuar'), esc_html($this->plugin->get_version())); ?></p>
		</div>
	</div>

	<div class="clear"></div>

	<!-- BUTTON BAR -->

	<ul class="cuar-related-actions">
		<li>
			<a href="<?php echo esc_url(admin_url('admin.php?page=wpca-settings')); ?>" class="button
            button-primary"><?php esc_html_e('Settings', 'cuar'); ?></a>
		</li>
		<li>
			<a href="<?php cuar_site_url('/documentation/introduction'); ?>" class="button button-primary"
			   target="_blank"><?php esc_html_e('Documentation', 'cuar'); ?></a>
		</li>
		<li>
			<a href="<?php echo cuar_site_url('/support'); ?>" class="button button-primary"
			   target="_blank"><?php esc_html_e('Support', 'cuar'); ?></a>
		</li>
		<li>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo cuar_site_url(); ?>"
			   data-text="<?php echo esc_attr__('An open-source (free) plugin for #WordPress to share private content, easily.', 'cuar'); ?>"
			   data-via="WPCustomerArea" data-size="large" data-hashtags="WPCA">Tweet</a>
			<script>!function (d, s, id)
				{
					var js, fjs = d.getElementsByTagName(s)[0];
					if (!d.getElementById(id)) {
						js = d.createElement(s);
						js.id = id;
						js.src = "//platform.twitter.com/widgets.js";
						fjs.parentNode.insertBefore(js, fjs);
					}
				}(document, "script", "twitter-wjs");</script>
		</li>
		<li class="clear"></li>
	</ul>

	<div class="clear"></div>

	<!-- TAB BAR -->

	<h2 class="nav-tab-wrapper cuar-dashboard-tabs">
		<?php foreach ($tabs as $tab_key => $tab_desc) :
			$classes = 'nav-tab';
			if ($current_tab === $tab_key)
			{
				$classes .= ' nav-tab-active';
			}
			?>
			<a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($tab_desc['url']); ?>">
				<?php echo esc_html($tab_desc['label']); ?></a>
		<?php endforeach; ?>
	</h2>

	<div class="clear"></div>

	<!-- CURRENT TAB CONTENT -->

	<div class="cuar-content">
		<div class="cuar-dashboard-section cuar-<?php echo esc_attr($current_tab); ?>-section">
			<?php require($tab_content_template); ?>
		</div>
	</div>

	<div class="clear"></div>

	<!-- NEWSLETTER -->

	<!-- Begin MailChimp Signup Form -->
	<!-- Temporarily comment out the Mail signup form since we're not using it anymore for now -->
	<!--
	<div class="cuar-divider"></div>

	<div class="cuar-dashboard-section cuar-newsletter-section">
		<h2 class="cuar-section-title"><?php esc_html_e('Stay informed', 'cuar'); ?></h2>
		<div class="clear"></div>

		<p class="cuar-instructions">
			<?php esc_html_e("You can also get notified when we've got something exciting to say (plugin updates, news, etc.). Simply subscribe to our newsletter, we won't spam, we send at most one email per month!", 'cuar'); ?>
		</p>

		<div class="cuar-actions">
			<div id="mc_embed_signup">
				<form
					action="http://wp-customerarea.us9.list-manage.com/subscribe/post?u=eb8f58238080f8e12a1bd20ca&amp;id=4f311a0114"
					method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate"
					target="_blank" novalidate>
					<p class="mc-field-group">
						<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL"
							   placeholder="<?php esc_attr_e('Email Address', 'cuar'); ?>" /><br />
					</p>
					<div id="mce-responses" class="clear">
						<div class="response" id="mce-error-response" style="display:none"></div>
						<div class="response" id="mce-success-response" style="display:none"></div>
					</div>
					<div class="clear">
						<input type="submit" value="<?php esc_attr_e('Subscribe', 'cuar'); ?>"
							   name="subscribe" id="mc-embedded-subscribe" class="button button-primary">
					</div>
				</form>
			</div>
		</div>

	</div>
	-->
	<!--End mc_embed_signup-->

	<div class="clear"></div>

</div>
