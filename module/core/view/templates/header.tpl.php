	<div id="admin-menu-wrapper">
		<ul id="admin-menu">
			<li><?php $this->api->open('link',array(
						'url' => "content/add/page",
						'title' => 'Your account'
					)); ?>
					account<?php $this->api->close(); ?>
			</li>
		</ul>
	</div>

	<div id="header-wrapper">
		<div id="header">
			<h1 id="header-title">
				<?php $this->api->open('link', array(
					 'url' => '',
					'ajax' => false
				)); ?><img src="<?php $this->api->theme_path("img/logo.png"); ?>" alt="<?php print $website['title']; ?>"/>
				<?php $this->api->close(); ?>
			</h1>
			<?php // <h2 id="header-subtitle"><span><{$website.subtitle </span></h2> ?>

			<div id="header-sidebar">
				<?php $this->api->open('panel', array(
					'name' => "header-sidebar"
				)); ?>
					<div id="header-sidebar-login">
						<?php if (!$user->id): ?>
							<li><?php $this->api->open('link', array(
									'url' => 'user/login',
									'okButtonLabel' => $this->api->t("Login"),
									'width' => 300,
									'showResponse' => false
								)); ?><img src="<?php print $this->api->theme_path("img/login.jpg"); ?>" alt="Login"/>
								<?php $this->api->close(); ?>
							</li>
						<?php else: ?>
							<li>
								<?php $this->api->open('link', array(
									'url' => 'user/logout',
									'okButtonLabel' => $this->api->t('Logout'),
									'width' => 300,
									'showResponse' => false
								)); ?><img src="<?php print $this->api->theme_path('img/logout.jpg'); ?>" alt="Logout"/>
								<?php $this->tpl->close(); ?>
							</li>
						<?php endif; ?>
					</div>
					<div id="header-sidebar-langs">
						<?php foreach ($system['langs'] as $lang): ?>
							<?php if ($lang != $system['lang']): ?>
							<a href="http://<?php print $this->api->lang_link($lang); ?>">
								<img alt="<?php print $lang; ?>" src="<?php print $this->api->theme_path('img/lang/40/' . $lang . '.jpg'); ?>"/>
							</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php $this->api->close(); ?>
			</div>
		</div>
	</div>

	<div id="main-menu-wrapper">
		<ul id="main-menu">
			<?php foreach ($page['mainMenu'] as $menuItem): ?>
				<li <?php if ($page['url'] == $menuItem['url']): ?>class="selected" <?php endif; ?>id="item-<?php print $menuItem['id']; ?>">
					<?php $this->api->open('link', array(
						'ajax' => false,
						'url' => $menuItem['url']
					)); ?><?php print $menuItem['title']; ?><?php $this->api->close(); ?>
				</li>
			<?php endforeach; ?>
			<?php $this->api->open('protected', array('url' => 'content/add/page')); ?>
				<li>
					<?php $this->api->open('link', array(
						'url' => 'content/add/page',
						'width' => 800,
						'height' => 500,
						'title' => $this->api->t('Create a new page')
					)); ?>
						Create a new page
					<?php $this->api->close(); ?>
				</li>
			<?php $this->api->close(); // protected ?>
		</ul>
	</div>