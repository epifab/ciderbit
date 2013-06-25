<?php
namespace module\core;

class Core extends \system\Module {
	
	public static function metaTypesMap() {
		return array(
			'html' => '\module\core\model\MetaHTML',
			'plaintext' => '\system\metatypes\MetaString'
		);
	}
	
	/**
	 * Define node types and variables allowed files / node children
	 * @return type 
	 */
	public static function nodeTypes() {
		return array(
			'#' => array(
				'page'
			),
			'profile' => array(
				'label' => \cb\t('User profile'),
				'file' => array('avatar'),
				'children' => array() // no children
			),
			'page' => array(
				'label' => \cb\t('Page'),
				'children' => array(
					'article'
				),
				'files' => array() // no files allowed for pages
			),
			'article' => array(
				'label' => \cb\t('Article'),
				'children' => array(
					'gallery',
					'audioplayer',
					'videoplayer',
					'comment'
				),
				'files' => array(
					'image',
					'downloads' // has many relation
				)
			),
			'photogallery' => array(
				'label' => \cb\t('Photo gallery'),
				'children' => array(
					'photo',
					'comment'
				),
				'files' => array()
			),
			'photo' => array(
				'label' => \cb\t('Photo'),
				'children' => array(
					'comment'
				),
				'files' => array(
					'image'
				)
			),
			'audioplayer' => array(
				'label' => \cb\t('Audio player'),
				'children' => array(
					'audiotrack',
					'comment'
				),
				'files' => array(
					'image'
				)
			),
			'audiotrack' => array(
				'label' => \cb\t('Audio track'),
				'children' => array(
					'comment'
				),
				'files' => array(
					'image',
					'track',
				)
			),
			'videoplayer' => array(
				'label' => \cb\t('Video player'),
				'children' => array(
					'comment'
				),
				'files' => array(
					'image',
					'video'
				)
			),
			'comment' => array(
				'label' => \cb\t('Comment'),
				'children' => array(
					'comment'
				),
				'files' => array()
			)
		);
	}

	public static function imageVersion($version, $fileName, \system\model\RecordsetInterface $nodeFile) {
		switch ($version) {
			case 'thumb':
				return self::imageVersionFixedSizes('60x60', $fileName, $nodeFile);
				break;
			case 's':
				return self::imageVersionFixedW('120-Y', $fileName, $nodeFile);
				break;
			case 'm':
				return self::imageVersionFixedW('240-Y', $fileName, $nodeFile);
				break;
			case 'l':
				return self::imageVersionFixedW('480-Y', $fileName, $nodeFile);
				break;
			case 'xl':
				return self::imageVersionFixedW('960-Y', $fileName, $nodeFile);
				break;
		}
	}
	
	public static function imageVersionFixedSizes($version, $fileName, \system\model\RecordsetInterface $nodeFile) {
		list($x, $y) = \explode('x', $version);
		\system\utils\File::saveImageFixedSize($nodeFile->file->path, $fileName, $x, $y);
	}
	
	public static function imageVersionFixedWidth($version, $fileName, \system\model\RecordsetInterface $nodeFile) {
		list($x, ) = \explode('-', $version);
		\system\utils\File::saveImage($nodeFile->file->path, $fileName, $x);
	}
	
	public static function imageVersionFixedHeight($version, $fileName, \system\model\RecordsetInterface $nodeFile) {
		list(, $y) = \explode('-', $version);
		\system\utils\File::saveImage($nodeFile->file->path, $fileName, 0, $y);
	}
	
	public static function imageVersionMakers() {
		$makers = array(
			'thumb' => array(\system\Module::getNamespace('core') . 'Core', 'imageVersion'),
			's' => array(\system\Module::getNamespace('core') . 'Core', 'imageVersion'),
			'm' => array(\system\Module::getNamespace('core') . 'Core', 'imageVersion'),
			'l' => array(\system\Module::getNamespace('core') . 'Core', 'imageVersion'),
			'xl' => array(\system\Module::getNamespace('core') . 'Core', 'imageVersion'),
		);
		
//		$makers = array();
//		$sizes = array(
//			'50x50',
//			'100x100',
//			'200x200',
//			'300x300',
//			'150x50',
//			'300x100',
//			'600x200',
//			'900x300',
//		);
//		foreach ($sizes as $s) {
//			$makers[$s] = array(\system\Module::getNamespace('core') . 'Core', 'imageVersionFixedSizes');
//		}
//		$a = array(50, 100, 200, 300, 600, 900);
//		foreach ($a as $x) {
//			$makers[$x . '-Y'] = array(\system\Module::getNamespace('core') . 'Core', 'imageVersionFixedWidth');
//		}
//		foreach ($a as $y) {
//			$makers['X-' . $y] = array(\system\Module::getNamespace('core') . 'Core', 'imageVersionFixedHeight');
//		}
		
		return $makers;
	}
	
	public static function cron() {

	}
	
	public static function onRun(\system\Component $component) {
		$component->addJs(\config\settings()->BASE_DIR . 'js/jquery-1.8.2.js');
		
		$component->addJs(\config\settings()->BASE_DIR . 'js/jquery_ui/jquery-ui-1.9.0.custom.js');
		$component->addCss(\config\settings()->BASE_DIR . 'js/jquery_ui/css/jquery-ui.css');

		$component->addJs(\config\settings()->BASE_DIR . 'js/jquery.form.js');
		
		$component->addJs(\config\settings()->BASE_DIR . 'js/jquery.ciderbit.js');

		$component->addCss(\system\Theme::getThemePath() . 'css/layout.css');
		
		$component->addTemplate('website-logo', 'header');
		$component->addTemplate('langs-control', 'header-sidebar');
		$component->addTemplate('footer', 'footer');
//		$component->addTemplate('sidebar', 'sidebar');
		
		$component->addCss(\system\Theme::getThemePath() . 'css/upload-jquery/jquery.fileupload-ui.css');
		
		$component->addJs(\system\Module::getAbsPath('core', 'js') . 'core.js');
	}
}
?>