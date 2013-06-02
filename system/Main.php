<?php
namespace system;

use system\logic\Component;
use system\logic\Module;

class Main {
	public static function setMessage($message, $type="error") {
		echo "<p>$message</p>";
	}
	
	/**
	 * Generates the module configuration array.
	 * Parses each info.yml looking for active modules
	 * @return array
	 */
	private static function loadConfiguration() {
		
		$WEIGHTS = array();
		
		$MODULES = array();
//		$EVENTS = array();
		$MODEL_CLASSES = array();
		$VIEW_CLASSES = array();
		$URLS = array();

		$d = \opendir("module");
		
		while (($moduleName = \readdir($d))) {
			
			if (!\is_dir(Module::getPath($moduleName))) {
				continue;
			}
			
			$moduleDir = Module::getPath($moduleName);
			$moduleNs = Module::getNamespace($moduleName);
			
			
			if (\file_exists($moduleDir . "info.yml")) {

				try {
					$moduleInfo = \system\yaml\Yaml::parse($moduleDir . "info.yml");
						
					if (!\is_array($moduleInfo)) {
						throw new \system\error\InternalError('Unable to parse <em>@name</em> module configuration.', array('@name' => $moduleName));
					}
					
					$enabled = \cb\array_item("enabled", $moduleInfo, array('default' => true));
					
					if (!$enabled) {
						// just ignore the module
						continue;
					}
					
					$moduleClass = $moduleNs . \cb\array_item('class', $moduleInfo, array('required' => true));
					
					if (!\class_exists($moduleClass)) {
						throw new \system\error\InternalError('Class <em>@name</em> does not exist.', array('@name' => $moduleClass));
					}
					
					$weight = (int)\cb\array_item('weight', $moduleInfo, array('default' => 0));
					
					// model class
					$modelNs = Module::getNamespace($moduleName, \cb\array_item('modelNs', $moduleInfo, array('default' => null)));
					$modelClass = \cb\array_item('modelClass', $moduleInfo, array(
						'default' => null,
						'prefix' => $modelNs
					));
					if (!\is_null($modelClass) && !\class_exists($modelClass)) {
						throw new \system\error\InternalError('Class <em>@name</em> does not exist.', array('@name' => $modelClass));
					}
					// templates class (API)
					$viewNs = Module::getNamespace($moduleName, \cb\array_item('viewNs', $moduleInfo, array('default' => null)));
					$viewClass = \cb\array_item('viewClass', $moduleInfo, array(
						'default' => null,
						'prefix' => $viewNs
					));
					if (!\is_null($viewClass) && !\class_exists($viewClass)) {
						throw new \system\error\InternalError('Class <em>@name</em> does not exist.', array('@name' => $viewClass));
					}
					
					$templatesPath = \cb\array_item('templatesPath', $moduleInfo, array(
						'default' => null,
						'prefix' => $moduleDir
					));
					if (!\is_null($templatesPath) && !\is_dir($templatesPath)) {
						throw new \system\error\InternalError('Directory <em>@path</em> not found', array('@path' => $templatesPath));
					}
					
					// components namespace
					$componentsNs = Module::getNamespace(
						$moduleName,
						\cb\array_item('componentsNs', $moduleInfo)
					);
					
					$events = \cb\array_item('events', $moduleInfo, array('default' => array()));
					foreach ($events as $eventName) {
						if (!\is_callable(array($moduleClass, $eventName))) {
							throw new \system\error\InternalError('Method <em>@class::@name</em> does not exist.', array('@class' => $moduleClass, '@name' => $eventName));
						}
					}
					
					$components = \cb\array_item('components', $moduleInfo, array('default' => array()));
					foreach ($components as $componentName => $component) {
						$componentClass = \cb\array_item('class', $component, array(
							 'required' => true, 
							 'prefix' => $componentsNs
						));
						if (!\class_exists($componentClass)) {
							throw new \system\error\InternalError('Class <em>@name</em> does not exist.', array('@name' => $componentClass));
							unset($components[$componentName]);
							continue;
						}
						$components[$componentName] = array(
							'name' => $componentName,
							'class' => $componentClass,
							'pages' => \cb\array_item('pages', $component, array('default' => array()))
						);
						foreach ($component['pages'] as $i => $page) {
							$components[$componentName]['pages'][$i] = array(
								'url' => \cb\array_item('url', $page, array('required' => true)),
								'name' => $componentName,
								'action' => \cb\array_item('action', $page, array('required' => true))
							);

							$rules = array(
								"@strid" => "[a-zA-Z0-9\-_]+",
								"@urlarg" => "[a-zA-Z0-9\-_.]+",
							);

							foreach ($rules as $ruleName => $replace) {
								$components[$componentName]['pages'][$i]['url'] = \str_replace(
									$ruleName,
									$replace,
									$components[$componentName]['pages'][$i]['url']
								);
							}
						}
					}
					
					$WEIGHTS[$weight][$moduleName] = array(
						'name' => $moduleName,
						'path' => $moduleDir,
						'class' => $moduleClass,
						'weight' => $weight,
						'modelNs' => $modelNs,
						'modelClass' => $modelClass,
						'viewNs' => $viewNs,
						'viewClass' => $viewClass,
						'templatesPath' => $templatesPath,
						'componentsNs' => $componentsNs,
						'events' => $events,
						'components' => $components
					);
					
				} catch (\Exception $ex) {
					throw $ex;
				}
			}
		}
		\closedir($d);
		
		\arsort($WEIGHTS);
		// discending order: heavier modules will override lighter ones
		foreach ($WEIGHTS as $modules) {
			\asort($modules);
			foreach ($modules as $module) {
				$MODULES[$module['name']] = $module;
				foreach ($module['components'] as $component) {
					foreach ($component['pages'] as $i => $page) {
						$URLS[$page['url']] = array(
							'module' => array(
								'name' => $module['name'],
								'class' => $module['class']
							),
							'component' => array(
								'name' => $component['name'],
								'class' => $component['class']
							),
							'action' => $page['action']
						);
					}
				}
//				foreach ($module['events'] as $eventName) {
//					if (\array_key_exists($eventName, $EVENTS)) {
//						$EVENTS[$eventName][] = $module['class'];
//					}
//				}
				if ($module['modelClass']) {
					$MODEL_CLASSES[] = $module['modelClass'];
				}
				if ($module['viewClass']) {
					$VIEW_CLASSES[] = $module['viewClass'];
				}
			}
		}
		
		// sorting the array by module weight descending
		//  this is because the view classes are designed for templates API,
		//  it makes more sense to search for the right api in hevier modules first
		\asort($VIEW_CLASSES);
		
		return array(
			'modules' => $MODULES,
			'urls' => $URLS,
//			'events' => $EVENTS,
			'tables' => self::loadModelCfg($MODULES),
			'modelClasses' => $MODEL_CLASSES,
			'templates' => self::loadViewCfg($MODULES),
			'viewClasses' => $VIEW_CLASSES
		);
	}
	
	private static function setTables($tables, &$TABLES) {
		foreach ($tables as $tableName => $table) {
			if (!\array_key_exists($tableName, $TABLES)) {
				$TABLES[$tableName] = array(
					'fields' => array(),
					'keys' => array(),
					'relations' => array(),
					'virtuals' => array()
				);
			}
			$fields = \cb\array_item('fields', $table, array('default' => array()));
			foreach ($fields as $fieldName => $field) {
				$TABLES[$tableName]['fields'][$fieldName] = $field;
			}
			$keys = \cb\array_item('keys', $table, array('default' => array()));
			foreach ($keys as $keyName => $key) {
				$TABLES[$tableName]['keys'][$keyName] = $key;
			}
			$relations = \cb\array_item('relations', $table, array('default' => array()));
			foreach ($relations as $relationName => $relation) {
				$TABLES[$tableName]['relations'][$relationName] = $relation;
			}
			$virtuals = \cb\array_item('virtuals', $table, array('default' => array()));
			foreach ($virtuals as $virtualName => $virtual) {
				$TABLES[$tableName]['virtuals'][$virtualName] = $virtual;
			}
		}
	}
	
	/**
	 * Generates the model configuration array.
	 * Parses each model.yml file defined on every active module.
	 * If two modules declare the same table, the two table definitions are merged.
	 * If two modules declare also the same table property as a field a key or a relation 
	 *  the highest priority module definition will override the other one.
	 * @return array Associative array (<table name> => <table definition>, ...)
	 */
	private static function loadModelCfg($modules) {
		$TABLES = array();
		$baseModel = 'system/model/model.yml';
		try {
			$model = \system\yaml\Yaml::parse($baseModel);
			self::setTables(
				\cb\array_item('tables', $model, array('required' => true)),
				$TABLES
			);
		} catch (\Exception $ex) {
			throw $ex;
		}
		
		
		foreach ($modules as $module) {
			if (\file_exists($module['path'] . 'model.yml')) {
				try {
					$model = \system\yaml\Yaml::parse($module['path'] . "model.yml");
					self::setTables(
						\cb\array_item('tables', $model, array('required' => true)),
						$TABLES
					);
				} catch (\Exception $ex) {
					throw $ex;
				}
			}
		}
		
		return $TABLES;
	}
	
	/**
	 * Generates the view configuration array.
	 */
	private static function loadViewCfg($modules) {
		$TEMPLATES = array();
		// loop over active modules templates keeping a associative array <template name> => <template path>
		// so modules with higher priority can override templates
		// moreover, it will be easier for the template manager to check a template really exists and to look for a template 
		foreach ($modules as $module) {
			if ($module['templatesPath']) {
				$d = \opendir($module['templatesPath']);
				while (($fileName = \readdir($d))) {
					if (\substr($fileName, -8) == '.tpl.php') {
						$templateName = \substr($fileName, 0, -8);
						$TEMPLATES[$templateName] = $module['templatesPath'] . DIRECTORY_SEPARATOR . $fileName;
					}
				}
				\closedir($d);
			}
		}

		$themeTplPath = \system\Theme::getThemePath('templates');
		
		if (!\is_null($themeTplPath) && \is_dir($themeTplPath)) {
			$d = \opendir($themeTplPath);
			while (($fileName = \readdir($d))) {
				if (\substr($fileName, -8) == '.tpl.php') {
					$templateName = \substr($fileName, 0, -8);
					$TEMPLATES[$templateName] = $themeTplPath . DIRECTORY_SEPARATOR . $fileName;
				}
			}
			\closedir($d);
		}
		return $TEMPLATES;
	}

	/**
	 * NB. modules are sorted by weight ascending
	 * <code>
	 *		'modules' :
	 *			[module name] :
	 *				'name' : [module name]
	 *				'path' : [module path]
	 *				'class' : [module class]
	 *				'modelClass' : [model class]
	 *				'viewClass' : [view class]
	 *				'components' :
	 *					[component name] :ss
	 *					  'pages' :
	 *							0 :
	 *								'url' : [url]
	 *								'action' : [action]
	 *							...
	 *				'events' :
	 *					[event name]
	 *					...
	 *			...
	 *		'urls' :
	 *			[url] :
	 *				'module' :
	 *					'class' : [module class]
	 *					'name' : [module name]
	 *				'component' :
	 *					'class' : [component class]
	 *					'name' : [component name]
	 *				'action' : [action]
	 *		'events' :
	 *			[event name] :
	 *				[component name]
	 *				...
	 *    'tables' :
	 *			[table name] :
	 *				'fields' :
	 *					...
	 *				'keys' :
	 *					...
	 *				'relations' :
	 *					...
	 *				'virtuals' :
	 *					...
	 *		'modelClasses' :
	 *			[model class]
	 *			...
	 *		'templates' :
	 *			[template name] : [template path]
	 *			...
	 *		'viewClasses' :
	 *			[view class]
	 *			...
	 * </code>
	 * @return array
	 */
	public static function configuration() {
		static $configuration = null;
		
		if (\is_null($configuration) && \config\settings()->CORE_CACHE) {
			$configuration = \system\Utils::get('system-configuration', null);
		}
		if (\is_null($configuration)) {
			$configuration = self::loadConfiguration();
			if (\config\settings()->CORE_CACHE) {
				\system\Utils::set('system-configuration', $configuration);
			}
		}
		return $configuration;
	}
	
	public static function moduleExists($moduleName) {
		$c = self::configuration();
		return \array_key_exists($moduleName, $c['modules']);
	}
	
	public static function templateExists($templateName) {
		$c = self::configuration();
		return \array_key_exists($templateName, $c['templates']);
	}
	
	public static function getTemplate($templateName) {
		if (\is_null($templateName)) {
			return null;
		}
		if (self::templateExists($templateName)) {
			$c = self::configuration();
			return $c['templates'][$templateName];
		} else {
			$c = self::configuration();
			throw new \system\error\InternalError('Template <em>@name</em> not found.', array('@name' => $templateName));
		}
	}

	public static function tableExists($tableName) {
		$c = self::configuration();
		return \array_key_exists($tableName, $c['tables']);
	}
	
	public static function getTable($tableName) {
		if (self::tableExists($tableName)) {
			$c = self::configuration();
			return $c['tables'][$tableName];
		} else {
			throw new \system\error\InternalError('Table <em>@name</em> not found.', array('@name' => $tableName));
		}
	}
	
	public static function urlExists($url) {
		return !\is_null(self::getComponent($url));
	}
	
	public static function getComponent($url) {
		static $urls = null;
		
		if ($url == \config\settings()->BASE_DIR) {
			$url = '';
		} else if (\substr($url, 0, \strlen(\config\settings()->BASE_DIR)) == \config\settings()->BASE_DIR) {
			$url = \substr($url, \strlen(\config\settings()->BASE_DIR));
		}
		
		if (!empty($url)) {
			$x = \strstr($url, '?', true);
			if ($x) {
				$url = $x;
			}
		}
		
		if (\is_null($urls)) {
			if (\config\settings()->CORE_CACHE) {
				// Url cache
				$urls = \system\Utils::get("system-urls", array());
			} else {
				$urls = array();
			}
		}
		if (!\array_key_exists($url, $urls)) {
			$urls[$url] = null;
			$configuration = self::configuration();
			foreach ($configuration['urls'] as $regexp => $info) {
				if (\preg_match('@^' . $regexp . '$@', $url, $m)) {
					\array_shift($m);
					$urls[$url] = array(
						'module' => $info['module']['name'],
						'name' => $info['component']['name'],
						'class' => $info['component']['class'],
						'action' => $info['action'],
						'urlArgs' => $m
					);
					if (\config\settings()->CORE_CACHE) {
						\system\Utils::set("system-urls", $urls);
					}
					break;
				}
			}
		}
		return $urls[$url];
	}
	
	public static function checkAccess($url, $request=array(), $user=false) {
		if ($user === false) {
			$user = \system\Login::getLoggedUser();
		}
		if (self::urlExists($url)) {
			$x = self::getComponent($url);
			return Component::access(
				$x['class'],
				$x['action'],
				$x['urlArgs'],
				$request,
				$user
			);
		} else {
			return true;
		}
	}
	
	public static function run($url, $request=null) {
		if (\is_null($request)) {
			$request = $_REQUEST;
		}
		
		$component = self::getComponent($url);
		
		if (!\is_null($component)) {
			$obj = new $component['class'](
				$component['name'],
				$component['module'],
				$component['action'],
				$url,
				$component['urlArgs'],
				$request
			);

			if (!$obj->isNested()) {
				// Raise event onRun
				self::raiseEvent('onRun', $obj);
			}

			$obj->process();
		} else {
			\header("HTTP/1.0 404 Not Found");
			die();
		}
	}
  
  public static function moduleConfigArray($eventName) {
    $results = \system\Utils::get('module-config-' . $eventName, null);
    if (\is_null($results)) {
      $results = array();
      $v = self::invokeMethodAll($eventName);
      foreach ($v as $m) {
        if (!\is_array($m)) {
          // skipping all non-array values
          continue;
        }
        $results = $m + $results;
      }
      \system\Utils::set('module-config-' . $eventName, $results);
    }
    return $results;
  }
  
  public static function moduleConfig($eventName) {
    $results = \system\Utils::get('module-config-' . $eventName, null);
    if (\is_null($results)) {
      $results = \end(self::invokeMethod($eventName));
      \system\Utils::set('module-config-' . $eventName, $results);
    }
    return $results;
  }
  
  public static function moduleConfigAll($eventName) {
    $results = \system\Utils::get('module-config-' . $eventName, null);
    if (\is_null($results)) {
      $results = self::invokeMethodAll($eventName); // last element of the array
      \system\Utils::set('module-config-' . $eventName, $results);
    }
    return $results;
  }

	/**
	 * Alias of invokeMethodAll
	 * @param string $eventName Method name
	 */
	public static function raiseEvent($eventName) {
	  return \call_user_func_array(array('self', 'invokeMethodAll'), \func_get_args());
	}
	
	public static function invokeMethodAll($methodName) {
		$configuration = self::configuration();
		$result = array();
		
		$args = null;
		if (\func_num_args() > 1) {
			$args = \func_get_args();
			\array_shift($args);
		}
		
		foreach ($configuration['modules'] as $module) {
			$class = $module['class'];
			if (\is_callable(array($class, $methodName))) {
				$x = \is_null($args)
					? \call_user_func(array($class, $methodName))
					: \call_user_func_array(array($class, $methodName), $args);
				if (\is_null($x)) {
					// do nothing
				} else {
					$result[] = $x;
				}
			}
		}
		return $result;
	}
	
	public static function invokeMethod($methodName) {
		$configuration = self::configuration();
		$result = array();
		
		$args = null;
		if (\func_num_args() > 1) {
			$args = \func_get_args();
			\array_shift($args);
		}
		
		foreach ($configuration['modules'] as $module) {
			$class = $module['class'];
			if (\is_callable(array($class, $methodName))) {
				return \is_null($args)
					? \call_user_func(array($class, $methodName))
					: \call_user_func_array(array($class, $methodName), $args);
			}
		}
		return null;
	}
	
	public static function raiseModelEvent($eventName) {
		$c = self::configuration();
		$result = array();
		
		$args = null;
		if (\func_num_args() > 1) {
			$args = \func_get_args();
			\array_shift($args);
		}
		
		foreach ($c['modelClasses'] as $class) {
			if (\is_callable(array($class, $eventName))) {
				$x = \is_null($args)
					? \call_user_func(array($class, $eventName))
					: \call_user_func_array(array($class, $eventName), $args);
				if (\is_null($x)) {
					// do nothing
				} else {
					$result[] = $x;
				}
			}
		}
		return $result;
	}
	
	public static function getModelClasses() {
		$c = self::configuration();
		return $c['modelClasses'];
	}
	
	public static function getViewClasses() {
		$c = self::configuration();
		return $c['viewClasses'];
	}

	public static function getTemplateManager() {
		static $tpl = null;
		if (\is_null($tpl)) {
			$tpl = new \system\view\TemplateManager();
		}
		return $tpl;
	}
	
	public static function tempPath() {
		return \config\settings()->BASE_DIR_ABS . 'temp/';
	}
	
	public static function dataPath() {
		return \config\settings()->BASE_DIR_ABS . 'data/';
	}
}
?>