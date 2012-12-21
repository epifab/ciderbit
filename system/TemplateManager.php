<?php
namespace system;

require "smarty/Smarty.class.php";

class TemplateManager extends \Smarty {
	private $mainTemplate;
	private $outlineTemplate;
	
	public function getOutlineTemplate() {
		return $this->outlineTemplate;
	}
	
	public function setOutlineTemplate($tpl) {
		$this->outlineTemplate = empty($tpl) ? null : \system\File::stripExtension($tpl) . ".tpl";
	}
	
	public function getMainTemplate() {
		return $this->mainTemplate;
	}
	
	public function setMainTemplate($tpl) {
		$this->mainTemplate = empty($tpl) ? null : \system\File::stripExtension($tpl) . ".tpl";
	}
	
	public function __construct() {
		parent::__construct();
		$this->addPluginsDir(array(
			"plugins",
			"system/tpl-api"
		));
		$this->setCompileDir(\config\settings()->TPL_CACHE_DIR);
	}
	
	public function process($datamodel) {
		if ($this->outlineTemplate) {
			$datamodel["private"]["mainTemplate"] = $this->mainTemplate;
		}
		foreach ($datamodel as $k => $v) {
			$this->assign($k, $v);
		}
		if ($this->outlineTemplate) {
			$this->display($this->outlineTemplate);
		} else {
			$this->display($this->mainTemplate);
		}
	}
}
?>