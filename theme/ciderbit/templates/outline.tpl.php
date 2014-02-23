<!DOCTYPE HTML>
<html lang="<?php echo $system['lang']; ?>">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo $page['title']; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $page['description']; ?>">
    <meta name="author" content="<?php echo $page['author']; ?>">
    
    <?php foreach ($page['css'] as $css): ?>
    <link href="<?php echo $css; ?>" type="text/css" rel="stylesheet"/>
    <?php endforeach; ?>
  </head>
  
  <body>

    <?php $this->api->loadBlock('admin-menu-wrapper', 'system/block/admin-menu'); ?>
    
    <header id="header-wrapper">
      <div id="header" class="container">
        <div class="row">
          <div id="header-main" class="span8">
            <h1 id="header-title">
              <?php $this->api->open('link', array(
                'url' => '',
                'ajax' => false
              )); ?><img src="<?php echo $this->api->themePath('img/layout/header-logo.png'); ?>" alt="<?php echo $website['title']; ?>"/>
              <?php echo $this->api->close(); ?>
              <span><?php echo $website['title']; ?></span>
            </h1>
            <h2 id="header-subtitle">
              <span><?php echo $website['subtitle']; ?></span>
            </h2>
          </div>
          <div id="header-sidebar" class="span4">
            <?php $this->api->region('header-sidebar'); ?>
          </div>
        </div>
      </div>
    </header>
    
    <div id="main-menu-wrapper">
      <?php $this->api->loadBlock('main-menu-wrapper', 'system/block/main-menu'); ?>
    </div>

    <div id="main-wrapper">
      <div id="main" class="container">
        <?php if (!empty($system['messages'])): ?>
          <div class="messages">
            <?php foreach ($system['messages'] as $level => $errorMessages): ?>
              <?php foreach ($errorMessages as $errorMessage): ?>
                <div class="alert alert-<?php echo $level; ?>"><?php echo $errorMessage; ?></div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <?php $this->api->import($website['outlineLayoutTemplate']); ?>
      </div>
    </div>

    <div id="footer-wrapper">
      <?php $this->api->region('footer'); ?>
    </div>
    
    <?php if ($this->api->access('/admin/logs')): ?>
    <?php $this->api->open('block', array('url' => '/admin/logs', 'name' => 'logs')); ?>
    <?php endif; ?>
    
    <script type="text/javascript"><?php $this->api->jss(); ?></script>
    
    <?php foreach ($page['js'] as $js): ?>
    <script type="text/javascript" src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
  </body>
</html>