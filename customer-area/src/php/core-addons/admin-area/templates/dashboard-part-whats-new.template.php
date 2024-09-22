<?php /** Template version: 1.0.0 */ ?>

<?php
$whats_new = array(
	'8.2' => array(
		'blog_post' => cuar_site_url('/whats-new-in-wp-customer-area-8-2'),
		'codename'  => 'Stevie Wonder',
		'tagline'   => __('New Bulk Import add-on', 'cuar'),
		'blocks'    => array(
			array(
				'title' => __('Bulk import private content from JSON file', 'cuar'),
				'text'  => sprintf(__('We are happy to announce a new %1$s that will help you to import private contents from a JSON file. This is a developer-oriented add-on, but, %2$s, it can be used to import private pages, tasks lists and its sub-tasks, or conversations and its replies.',
					'cuar'),
					'<a href="' . cuar_site_url('/product/wpca-bulk-import') .'">' . __('a new Bulk Import add-on','cuar') . '</a>',
					'<a href="' . cuar_site_url('/product/wpca-ftp-mass-import') .'">' . __('unlike the FTP Mass Import add-on','cuar') . '</a>'
				),
			),
			array(
				'title' => __('Improved the UI', 'cuar'),
				'text'  => sprintf(__('We focused on improving the UI in various area of the main plugin and its add-ons. For instance, we created %1$s, %2$s is now compatible with %3$s. We also improved the layout and the compatibility with some themes.','cuar'),
					'<a href="' . cuar_site_url('/product/wpca-compatibility-elementor') .'">' . __('a new Elementor Compatibility add-on','cuar') . '</a>',
					'<a href="' . cuar_site_url('/product/wpca-content-expiry') .'">' . __('the Content Expiry add-on','cuar') . '</a>',
					'<a href="' . cuar_site_url('/product/wpca-front-office-publishing') .'">' . __('the Front Office Publishing add-on','cuar') . '</a>'),
			),
		),
		array(
			'title' => __('Improvements and bug fixes', 'cuar'),
			'text'  => __('Since our 8.1.0 version, there have been 22 new features and over 60 bug fixes and tweaks. For example, we fixed some performances issues, some problems related the login forms, some queries, and many small issues that may have hindered your user experience.',
				'cuar'),
		),
	),
	'8.1' => array(
        'blog_post' => cuar_site_url('/whats-new-in-wp-customer-area-8-1'),
        'codename'  => 'Django Reinhardt',
        'tagline'   => __('New FTP Mass Import add-on', 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Bulk import files from your FTP', 'cuar'),
                'text'  => __('We are happy to announce a new utility add-on that will help you to import files from your FTP. You\'ll get a simple form allowing you to select files located on your server and the associated owners, in order to automatically create the posts that belongs to them.',
                    'cuar'),
            ),
            array(
                'title' => __('New translations', 'cuar'),
                'text'  => sprintf(__('We\'ll now be maintaining translations for the main plugin from %s. Please use your WordPress account to commit translations in your own language directly to this platform.', 'cuar'),
                    '<a href="https://translate.wordpress.org/projects/wp-plugins/customer-area/stable/">' . __('the official WP translation system', 'cuar') . '</a>'
                ),
            ),
            array(
                'title' => __('Improvements and bug fixes', 'cuar'),
                'text'  => __('Since our 8.0 version, you probably had troubles validating your licences. This is due to a root certificate update from LetsEncrypt. And openSSL on your server now needs to be updated to 1.1.x. The plugin will now check if you have the correct version.',
                    'cuar'),
            ),
        )
    ),
    '8.0' => array(
        'blog_post' => cuar_site_url('/whats-new-in-wp-customer-area-8-0'),
        'codename'  => 'Joey Ramone',
        'tagline'   => __('New backend for our add-on licensing system', 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Add-on licensing', 'cuar'),
                'text'  => __('Our licensing system has changed completely and thus we had to adapt the plugin to that new backend. This is why we considered this version a breaking change. All add-ons will need to be updated too. If you have any add-on and need to renew your licence keys, please read our blog post and send us an email with all the details we require.',
                    'cuar'),
            ),
            array(
                'title' => __('Improvements and bug fixes', 'cuar'),
                'text'  => __('Since our 7.10.0 version, focus has mainly been on restoring our website, licensing backend and shop. However, we have included in this release a few bug fixes and a couple of new minor features.',
                    'cuar'),
            ),
        )
    ),
    '7.10' => array(
        'blog_post' => '',
        'codename'  => 'Ray Charles',
        'tagline'   => __('Tweaking the User Experience', 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Design enhancements', 'cuar'),
                'text'  => __('We improved the design to make it more elegant on large screens and added the possibility to set max-width through a filter for single content posts and forms. We also added a filter to display post titles, added a "year archives" link in date archives widgets, improved the way our columns system work, and many other cool things.',
                    'cuar'),
            ),
            array(
                'title' => __('Third-party plugins integration', 'cuar'),
                'text'  => __('We created a new filter cuar/private-content/view/disable-css-resets to disable CSS overrides into the area that will allow integration of third party plugins (like page builders) into your private area. Read our blog post to get some more details about that. Be warned that using this class allows plugin integration, but may introduce graphical issues depending on your theme.',
                    'cuar'),
            ),
            array(
                'title' => __('Improvements and bug fixes', 'cuar'),
                'text'  => __('Since our 7.9.0 version, there have been 21 new features and over 30 bug fixes and tweaks. For example, you can now search for attachments inside private files using the search add-on. There are some new custom fields placeholders in notifications. We fixed many issues related to PHP sessions, 404 errors, permission errors according to your feedback.',
                    'cuar'),
            ),
        )
    ),
    '7.9' => array(
        'blog_post' => '',
        'codename'  => 'Leonard Cohen',
        'tagline'   => __('Enhanced display control', 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Frontend editor enhancements', 'cuar'),
                'text'  => __('We decided to make some changes in the advanced frontend editor. It will not generate paragraph tags itself anymore, WP will (see wpautop function). It will now allow shortcode to be used, like the embed shortcode (so that you can insert videos and more). It will now remove any HTML data when pasting content to make the use of it simpler (HTML pasting is still allowed in code view).',
                    'cuar'),
            ),
            array(
                'title' => __('Content queries', 'cuar'),
                'text'  => __('Many of you have let us know that it is embarrassing to have on pages of content, only the posts assigned to the connected user. We listened to you, and we added on the pages "My files", "My pages" (etc.) the possibility to add the contents created by the current connected user: Uncheck the checkbox in Settings -> WP Customer Area -> {private content type} -> Frontend Integration -> Content queries.',
                    'cuar'),
            ),
            array(
                'title' => __('Design enhancements and bug fixes', 'cuar'),
                'text'  => __('Since our 7.7.0 version, there have been many improvements on the design, but also many patches: 23 new features and over 49 issues fixes and tweaks. For instance, we made security updates and improvements regarding our dependencies. We also made some minor design adjustments.',
                    'cuar'),
            ),
        )
    ),
    '7.7' => array(
        'blog_post' => cuar_site_url('/whats-new-in-wp-customer-area-7-7-0'),
        'codename'  => 'Janis Joplin',
        'tagline'   => __('Create powerful forms using ACF and our ACF Integration add-on', 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('ACF Integration', 'cuar'),
                'text'  => __('We made some huge updates on the ACF Integration add-on, its design and its compatibility with the popular ACF plugin from Elliot Condon. We made almost all the fields compatibles, except 4 of them (see our readme file for more information), and we re-wrote all the styles from ACF to make the fields compatibles with our design-extras add-on.',
                    'cuar'),
            ),
            array(
                'title' => __('User experience', 'cuar'),
                'text'  => __('Many of you have let us know that it is embarrassing to have on pages of content, only the posts assigned to the connected user. We listened to you, and we added on the pages "My files", "My pages" (etc.) the possibility to add the contents created by the current connected user (see our blog post for more information).',
                    'cuar'),
            ),
            array(
                'title' => __('Design enhancements and bug fixes', 'cuar'),
                'text'  => __('Since our 7.6.0 version, there have been many improvements on the design, but also many patches: 11 new features and over 21 issues fixes and tweaks. For instance, we optimized our code to allow a faster page load speed (check our readme file for more information).',
                    'cuar'),
            ),
        )
    ),
    '7.5' => array(
        'blog_post' => cuar_site_url('/whats-new-in-wp-customer-area-7-5-0'),
        'codename'  => 'Jacques Higelin',
        'tagline'   => __('Force your users to read and agree your terms and conditions', 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('New add-on: Terms Of Service', 'cuar'),
                'text'  => __('We introduced a new add-on that will allow you to force your users to accept your terms of service. Your users will need to check a box when registering or logging in.',
                    'cuar'),
            ),
            array(
                'title' => __('Big users database: Now using Ajax inside select list', 'cuar'),
                'text'  => __('All our select lists are now fetching content through ajax, so you can search into a list of users/groups/projects/etc, without loading the whole list every time.',
                    'cuar'),
            ),
            array(
                'title' => __('Improvements and bug fixes', 'cuar'),
                'text'  => __('Main plugin and existing add-ons have been much improved: 11 new features and over 12 issues fixes and tweaks. For instance, we added some filters that will allow you to customize how users names are displayed in select boxes.',
                    'cuar'),
            ),
        )
    ),
	'7.4' => array(
		'blog_post' => cuar_site_url('/whats-new-in-wp-customer-area-7-4'),
		'codename'  => 'Bob Marley',
		'tagline'   => __("Online payment of invoices with PayPal and Stripe Gateways", 'cuar'),
		'blocks'    => array(
			array(
				'title' => __('Online invoice payments', 'cuar'),
				'text'  => __('You will find a new payments menu item in your WP Customer Area menu to manage your invoices payments. By default, you can accept manual payments (cheque, wired transfers), but we have 2 brand new gateways available to accept payments via PayPal accounts and from credit cards via Stripe.',
					'cuar'),
			),
			array(
				'title' => __('Conversations: Close conversation', 'cuar'),
				'text'  => __('The conversation author can now decide to close the conversation to new replies.',
					'cuar'),
			),
			array(
				'title' => __('Improvements and bug fixes', 'cuar'),
				'text'  => __('Main plugin and existing add-ons have been much improved: 5 big new features and over 20 issues fixes. For instance, we updated supported version of ACF to 4.4.12.1. Also, you can now view any notification that get sent by WP Customer Area into Customer Area menu -> Logs.',
					'cuar'),
			),
		)
	),
	'7.3' => array(
		'blog_post' => '',
		'codename'  => 'Eric Clapton',
		'tagline'   => __("Welcome to the Design Extras add-on", 'cuar'),
		'blocks'    => array(
			array(
				'title' => __('New add-on: Design Extras', 'cuar'),
				'text'  => __('We introduced a new add-on that will add new design elements to your private area. New skins, notifications and invoicing PDF templates have been designed for you!',
					'cuar'),
			),
			array(
				'title' => __('Notifications and PDF Invoices templates options', 'cuar'),
				'text'  => __('Invoicing and notification templates are coming with a bunch of options in the admin area that allow you to customize colors, background and more. It will be easy for you to use them as a base and get your own customized design in a few minutes!',
					'cuar'),
			),
			array(
				'title' => __('Improvements and bug fixes', 'cuar'),
				'text'  => __('Main plugin and existing add-ons have been much improved: many new features and more than 30 issues fixes. For instance, notifications are now sent when a task lists gets completed.',
					'cuar'),
			),
		)
	),
	'7.2' => array(
		'blog_post' => '',
		'codename'  => 'Sid Vicious',
		'tagline'   => __("Welcome to the Unread Documents add-on", 'cuar'),
		'blocks'    => array(
			array(
				'title' => __('New add-on: Unread Documents', 'cuar'),
				'text'  => __('It will allow you to see if your documents have been updated since the last time you read them using a visual indicator. It is compatible with all others private content add-ons. You can also mark private contents as unread using a dedicated button.',
					'cuar'),
			),
			array(
				'title' => __('Notifications: New tasks reminders', 'cuar'),
				'text'  => __('It comes very useful to get some emails when your tasks are overdue, about to expire, or about to be overdue. You wont get any chance left to miss tasks you are assigned to!',
					'cuar'),
			),
			array(
				'title' => __('Improvements and bug fixes', 'cuar'),
				'text'  => __('Main plugin and existing add-ons have been much improved: this is 17 issues fixed. For instance, a lot of improvements and bug fixes can be seen on the frontend interface especially on the collection views and sidebars.',
					'cuar'),
			),
		)
	),
    '7.1' => array(
        'blog_post' => '',
        'codename'  => 'Kurt Cobain',
        'tagline'   => __("A small post-summer-break update", 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Better add-on version detection', 'cuar'),
                'text'  => __('WP Customer Area will let you know after each update if you have the latest add-on release. Never forget to update them anymore!',
                    'cuar'),
            ),
            array(
                'title' => __('PDF Invoices', 'cuar'),
                'text'  => __('Invoices can now be downloaded as PDF files. A simple template is provided but you can make your own very easily too!',
                    'cuar'),
            ),
            array(
                'title' => __('Download all attachments', 'cuar'),
                'text'  => __('For those who have the Enhanced Files add-on, you can now download all attached files with a single click.',
                    'cuar'),
            ),
        )
    ),
    '7.0' => array(
        'blog_post' => '',
        'codename'  => 'Lemmy Kilmister',
        'tagline'   => __("WP Customer Area 7.0 has got a new dress.", 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('New skin', 'cuar'),
                'text'  => __('The appearance of the private area has been completely rewritten to enhance compatibility with all themes and usability. ',
                    'cuar'),
            ),
            array(
                'title' => __('Better ownership control', 'cuar'),
                'text'  => __('Our add-ons allow assigning content to groups, roles, etc. However, these owner types could not be mixed. For example, you can now assign private content at the same time to a group and to a few users.',
                    'cuar'),
            ),
            array(
                'title' => __('Improvements and bug fixes', 'cuar'),
                'text'  => __('Existing add-ons have been much improved: 17 new features and over 14 issues fixes. For instance, notifications are now sent in HTML format using a clean professional template.',
                    'cuar'),
            ),
        )
    ),
    '6.3' => array(
        'blog_post' => '',
        'codename'  => 'Bon Scott',
        'tagline'   => __("WP Customer Area 6.3 has been aimed at improving customer relationship management and improving existing add-ons.", 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Contact information', 'cuar'),
                'text'  => __('User profiles can now store a home and a billing address. This is an essential information when managing your customers and will help building more CRM features into the plugin later on.',
                    'cuar'),
            ),
            array(
                'title' => __('Invoicing', 'cuar'),
                'text'  => __('We are introducing a new add-on to publish invoices for your customers. No more attaching files, there is a proper content type for invoices and we will soon add a lot around that (PDF generation, online payment, etc.).',
                    'cuar'),
                'more'  => cuar_site_url('/product/wpca-invoicing')
            ),
            array(
                'title' => __('Improvements and bug fixes', 'cuar'),
                'text'  => __('While this time we do not have other big news than the new invoicing add-on, there have been lots of additions and fixes to the base plugin and the existing add-ons: 15 new features and 19 issues have been implemented/fixed.',
                    'cuar'),
            ),
        )
    ),
    '6.2' => array(
        'blog_post' => '',
        'codename'  => 'Jim Morrison',
        'tagline'   => __("WP Customer Area 6.2 has been aimed at improving the attachments to private files and everything around that.", 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('New upload system', 'cuar'),
                'text'  => __('The file upload system has been completely rewritten. You can now drag and drop files and watch the upload progress. Additionally, we have also added some more settings and provide a security helper to make sure your setup is as secure as it can.',
                    'cuar'),
            ),
            array(
                'title' => __('Enhanced Files', 'cuar'),
                'text'  => __('We are introducing a new add-on which improves the file attachments: upload multiple attachments on the same private file, change the attachment caption and automatically add file type icons in front of the download links.',
                    'cuar'),
                'more'  => cuar_site_url('/product/wpca-enhanced-files')
            ),
            array(
                'title' => __('Smart Groups', 'cuar'),
                'text'  => __('Smart Groups will change the way to build your user groups. No more adding or removing users by hand, you will simply make dynamic groups based on criteria such as name, domain of the email address, and any other meta information linked to your user profiles.',
                    'cuar'),
                'more'  => cuar_site_url('/product/wpca-smart-groups')
            ),
        )
    ),
    '6.1' => array(
        'blog_post' => '',
        'codename'  => 'Jimi Hendrix',
        'tagline'   => __("WP Customer Area 6.1 focuses mainly on making administrator's life easier, introducing event logging and a better administration panel.", 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Protect post types', 'cuar'),
                'text'  => __('We are introducing a new add-on which will allow protecting any kind of external post types. Posts created from third party plugins can be made private and assigned just like private pages or files.',
                    'cuar'),
                'more'  => cuar_site_url('/product/wpca-protect-post-types')
            ),
            array(
                'title' => __('Shortcodes', 'cuar'),
                'text'  => __('we have added shortcodes to display the navigation menu, list protected content, etc. You will now be able to easily build custom pages with a list of the content you want to pull for the connected user.',
                    'cuar'),
                'more'  => cuar_site_url('/documentation/user-guides/shortcodes')
            ),
            array(
                'title' => __('Better admin panel', 'cuar'),
                'text'  => __('The main plugin menu has been improved a lot, it is now less cluttered and better organised. The content lists now feature powerful filters to help you find the content you are looking for faster.',
                    'cuar')
            ),
            array(
                'title' => __('Logging', 'cuar'),
                'text'  => __('When dealing with private content and secure areas, keeping track of events is crucial. We have added a module to track what is happening within your private area.',
                    'cuar')
            ),
            array(
                'title' => __('And more!', 'cuar'),
                'text'  => __('As with any new release, we constantly provide bug fixes. This update is no exception with no less than 23 issues corrected.',
                    'cuar'),
                'more'  => 'https://github.com/marvinlabs/customer-area/issues?q=milestone%3A6.1+is%3Aclosed'
            ),
        )
    ),
    '6.0' => array(
        'blog_post' => '',
        'codename'  => 'John Lennon',
        'tagline'   => __("WP Customer Area 6.0 is a major release which sees a lot of changes not only inside the plugin but also on the website.", 'cuar'),
        'blocks'    => array(
            array(
                'title' => __('Improved setup &amp; updates', 'cuar'),
                'text'  => __('We have implemented a new setup assistant that will make it even easier to install the plugin. Updates will be smoother too.',
                    'cuar')
            ),
            array(
                'title' => __('Better permissions', 'cuar'),
                'text'  => __('Some new permissions have been added to give you more control about what your users can do. On top of that, we have also improved the permissions screen to make it faster to set permissions.',
                    'cuar')
            ),
            array(
                'title' => __('And more!', 'cuar'),
                'text'  => __('As with any new release, we constantly provide bug fixes. This update is no exception with no less than 20 issues corrected.',
                    'cuar')
            ),
        )
    )
);
?>

<?php foreach ($whats_new as $ver => $desc) : ?>
    <h3><?php printf(__("New in %s ~ <em>%s</em>", 'cuar'), $ver, $desc['codename']); ?></h3>
    <h4><?php echo $desc['tagline']; ?></h4>
    <div class="cuar-whatsnew-boxes">
        <?php $i = 0;
        foreach ($desc['blocks'] as $item) : ?>
            <div class="cuar-whatsnew-box">
                <h3><?php echo $item['title']; ?></h3>

                <p><?php echo $item['text']; ?></p>
                <?php if ( !empty($item['more'])) : ?>
                    <p><a href="<?php echo $item['more']; ?>"><?php _e('Learn more', 'cuar'); ?></a></p>
                <?php endif; ?>
            </div>
            <?php
            $i++;
            if ($i > 2)
            {
                echo '<div class="cuar-clearfix">&nbsp;</div>';
                $i = 0;
            }
        endforeach; ?>
        <p class="cuar-clearfix">&nbsp;</p>
    </div>
<?php endforeach; ?>
