<div id="desktopBar">

<?php
	$lang_url = base_url().Settings::get_lang('current').'/'.Settings::get('admin_url');
?>
<?php if (ENVIRONMENT == 'development' OR ENVIRONMENT == 'testing'): ?>
	<div id="preprod-flag">
			<span class="help" title="<?php echo lang('ionize_help_environment_flag', ENVIRONMENT); ?>">
				<?php echo strtoupper(ENVIRONMENT); ?>
			</span>
	</div>
	<script type="text/javascript">
		ION.initLabelHelpLinks('#preprod-flag');
	</script>
<?php endif; ?>

<a id="logoAnchor" class="navlink" href="dashboard"></a>
<div class="desktopTitlebarWrapper">
	<div class="desktopTitlebar">
		<h1 class="applicationTitle">ionize <?php echo($this->config->item('version')) ;?></h1>
		<div class="topNav">
			<ul class="menu-right">
				<li><?php echo lang('ionize_logged_as'); ?> : <?php echo $current_user['screen_name']; ?></li>
				<li><a href="<?php echo base_url(); ?>" target="_blank"><?php echo lang('ionize_website'); ?></a></li>
				<li><a href="<?php echo base_url().Settings::get_lang('current').'/'.config_item('admin_url'); ?>/user/logout"><?php echo lang('ionize_logout'); ?></a></li>
				<li>
					<?php foreach(Settings::get('displayed_admin_languages') as $lang) :?>
						<a href="<?php echo base_url().$lang; ?>/<?php echo config_item('admin_url'); ?>"><img src="<?php echo theme_url(); ?>images/world_flags/flag_<?php echo $lang; ?>.gif" alt="<?php echo $lang; ?>" /></a>
					<?php endforeach ;?>
				</li>
			</ul>
		</div>
	</div>
</div>

<div id="desktopNav" class="desktopNav">

	<div class="toolMenu left">

		<ul>
			<li><a class="navlink" href="dashboard" title="<?php echo lang('ionize_title_welcome'); ?>"><?php echo lang('ionize_menu_dashboard'); ?></a></li>
			<li><a class="returnFalse"><?php echo lang('ionize_menu_content'); ?></a>
				<ul>
					<?php if($this->connect->is('super-admins')) :?>
						<li><a class="navlink" href="menu" title="<?php echo lang('ionize_title_menu'); ?>"><?php echo lang('ionize_menu_menu'); ?></a></li>
					<?php endif ;?>
					<li><a class="navlink" href="page/create/0" title="<?php echo lang('ionize_title_new_page'); ?>"><?php echo lang('ionize_menu_page'); ?></a></li>
					<li><a class="navlink" href="article/list_articles" title="<?php echo lang('ionize_title_articles'); ?>"><?php echo lang('ionize_menu_articles'); ?></a></li>
					<li><a class="navlink" href="category" title="<?php echo lang('ionize_title_categories'); ?>"><?php echo lang('ionize_menu_categories'); ?></a></li>

					<li class="divider"><a class="navlink" href="translation" title="<?php echo lang('ionize_title_translation'); ?>"><?php echo lang('ionize_menu_translation'); ?></a></li>
					<li class="divider"><a id="mediamanagerlink" href="media/get_media_manager" title="<?php echo lang('ionize_menu_media_manager'); ?>"><?php echo lang('ionize_menu_media_manager'); ?></a></li>
					<?php if ($this->connect->is('super-admins')) :?>
						<li class="divider"><a class="navlink" href="article_type/index" title="<?php echo lang('ionize_menu_types'); ?>"><?php echo lang('ionize_menu_types'); ?></a></li>
						<li><a class="navlink" href="element_definition/index" title="<?php echo lang('ionize_menu_content_elements'); ?>"><?php echo lang('ionize_menu_content_elements'); ?></a></li>
						<li><a class="navlink" href="extend_field/index" title="<?php echo lang('ionize_menu_extend_fields'); ?>"><?php echo lang('ionize_menu_extend_fields'); ?></a></li>
					<?php endif ;?>
				</ul>
			</li>
			<?php if($this->connect->is('editors')) :?>
			<li><a class="returnFalse"><?php echo lang('ionize_menu_modules'); ?></a>
				<ul>
					<!-- Module Admin controllers links -->
					<?php foreach($modules as $uri => $module) :?>
						<?php if($this->connect->is($module['access_group'])) :?>
							<li><a class="navlink" id="<?php echo $uri; ?>ModuleLink" href="module/<?php echo $uri; ?>/<?php echo $uri; ?>/index" title="<?php echo $module['name']; ?>"><?php echo $module['name']; ?></a></li>
						<?php endif ;?>
					<?php endforeach ;?>
					<?php if($this->connect->is('admins')) :?>
						<li class="divider"><a class="navlink" href="modules" title="<?php echo lang('ionize_title_modules'); ?>"><?php echo lang('ionize_menu_modules_admin'); ?></a></li>
					<?php endif ;?>
				</ul>
			</li>
			<?php endif ;?>
			<li><a class="returnFalse"><?php echo lang('ionize_menu_tools'); ?></a>
				<ul>
					<li><a href="https://www.google.com/analytics/web/" target="_blank">Google Analytics</a></li>
					<li><a class="navlink" href="system_check"><?php echo lang('ionize_menu_system_check'); ?></a></li>
				</ul>
			</li>

			<li><a class="returnFalse"><?php echo lang('ionize_menu_settings'); ?></a>
				<ul>
					<li><a class="navlink" href="setting/ionize" title="<?php echo lang('ionize_menu_ionize_settings'); ?>"><?php echo lang('ionize_menu_ionize_settings'); ?></a></li>
					<li><a class="navlink" href="lang" title="<?php echo lang('ionize_menu_languages'); ?>"><?php echo lang('ionize_menu_languages'); ?></a></li>
					<?php if($this->connect->is('admins')) :?>
						<li><a class="navlink" href="users" title="<?php echo lang('ionize_menu_users'); ?>"><?php echo lang('ionize_menu_users'); ?></a></li>
					<?php endif ;?>
					<?php if($this->connect->is('super-admins')) :?>
						<li><a class="navlink" href="setting/themes" title="<?php echo lang('ionize_title_theme'); ?>"><?php echo lang('ionize_menu_theme'); ?></a></li>
					<?php endif ;?>
					<li class="divider"><a class="navlink" href="setting" title="<?php echo lang('ionize_menu_site_settings'); ?>"><?php echo lang('ionize_menu_site_settings'); ?></a></li>
					<?php if($this->connect->is('super-admins')) :?>
						<li><a class="navlink" href="setting/technical" title="<?php echo lang('ionize_menu_site_settings_technical'); ?>"><?php echo lang('ionize_menu_site_settings_technical'); ?></a></li>
					<?php endif ;?>
				</ul>
			</li>
			<li><a class="returnFalse"><?php echo lang('ionize_menu_help'); ?></a>
				<ul>
					<?php if (is_dir(realpath(APPPATH.'../user-guide'))) :?>
						<li><a id="docLink" href="../user-guide/index.html" target="_blank"><?php echo lang('ionize_menu_documentation'); ?></a></li>
					<?php endif; ?>
					<li<?php if (is_dir(realpath(APPPATH.'../user-guide'))) :?> class="divider"<?php endif; ?>><a id="aboutLink" href="<?php echo theme_url(); ?>views/about.html"><?php echo lang('ionize_menu_about'); ?></a></li>
				</ul>
			</li>
		</ul>
	</div>
	
	<div id="desktopNavToolbar_spinner" class="spinner"></div>

</div>


</div>

<script type="text/javascript">
	
	/**
	 * Init of all main menu links
	 *
	 */
	$$('.navlink').each(function(item)
	{
		item.addEvent('click', function(event)
		{
			event.preventDefault();

			ION.contentUpdate({
            	element : 'mainPanel',
                url: this.getProperty('href'),
				title: this.getProperty('title')
			});
		});
	});

	if ($('mediamanagerlink'))
	{
		$('mediamanagerlink').addEvent('click', function(event)
		{
			event.preventDefault();

			ION.contentUpdate({
				element: 'mainPanel',
				url: this.getProperty('href'),
				title: this.getProperty('title'),
				padding: {top: 0, right: 0, bottom: 0, left: 0}
			});
		});
	}


	$('aboutLink').addEvent('click', function(event)
	{
		event.preventDefault();

		new MUI.Modal({
			id: 'about',
			title: 'MUI',
			content: {url: admin_url + 'desktop/get/system/about'},
			type: 'modal2',
			width: 360,
			height: 210,
			padding: {top: 70, right: 12, bottom: 10, left: 22},
			scrollbars: false
		});
	});
	
</script>

