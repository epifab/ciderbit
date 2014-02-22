<?php
namespace system\metatypes;

abstract class MetaType {

  protected $name;
  protected $type;
  protected $attributes;

  private final function __construct($name, $type, array $attributes) {
    $this->name = $name;
    $this->type = $type;
    $this->attributes = $attributes;
    $this->onInit();
  }
  
  protected function onInit() { }
  
//  public function serialize() {
//    return serialize(array('name' => $this->name, 'type' => $this->type, 'attributes' => $this->attributes));
//  }
//  
//  public function unserialize($data) {
//    $data = unserialize($data);
//    return self::newMetaType($data['name'], $data['type'], $data['attributes']);
//  }
  
  public static function getMetaTypesMap() {
    return \system\utils\Cache::metaTypesMap();
    
//    static $map = null;
//    if (\is_null($map)) {
//      if (\config\settings()->CORE_CACHE) {
//        $map = \system\utils\Utils::get('system-mtmap', null);
//        if (!\is_null($map)) {
//          return $map;
//        }
//      }
//      $map = array();
//      
//      // default overridable values
//      $map['integer'] = '\\system\\metatypes\\MetaInteger';
//      $map['decimal'] = '\\system\\metatypes\\MetaDecimal';
//      $map['string'] = '\\system\\metatypes\\MetaString';
//      $map['boolean'] = '\\system\\metatypes\\MetaBoolean';
//      $map['date'] = '\\system\\metatypes\\MetaDate';
//      $map['datetime'] = '\\system\\metatypes\\MetaDateTime';
//      $map['virtual'] = '\\system\\metatypes\\MetaString';
//      
//      $conf = \system\Main::raiseModelEvent('metaTypesMap');
//
//      foreach ($conf as $m) {
//        if (\is_array($m)) {
//          foreach ($m as $type => $class) {
//            $map[$type] = $class;
//          }
//        }
//      }
//      if (\config\settings()->CORE_CACHE) {
//        \system\utils\Utils::set('system-mtmap', $map);
//      }
//    }
//    return $map;
  }
  
  public static function newMetaType($name, $type, $attributes = array()) {
    $fmap = self::getMetaTypesMap();
    
    if (!\array_key_exists($type, $fmap)) {
      throw new \system\exceptions\InternalError('Unknown metatype <em>@name</em>', array('@name' => $type));
    }
    $metaTypeClass = $fmap[$type];
    
    return new $metaTypeClass(
      $name,
      $type,
      $attributes
    );
  }
  
  public abstract function toProg($x);

  public function getDefaultValue() {
    return $this->toProg($this->getAttr('default', array('default' => null)));
  }

  public function attrExists($key) {
    return \array_key_exists($key, $this->attributes);
  }

  public function getAttr($key, $options = array()) {
    return \cb\array_item($key, $this->attributes, $options);
  }

  public function getAttributes() {
    return $this->attributes;
  }

  public function getName() {
    return $this->name;
  }

  public function getType() {
    return $this->type;
  }

  protected abstract function getEditWidgetDefault();

  public final function getEditWidget() {
    return $this->attrExists('widget') ? $this->getAttr('widget') : $this->getEditWidgetDefault();
  }

  public function db2Prog($x) {
    return $this->toProg($x);
  }

  public function prog2Db($x) {
    return $x;
  }

  public function edit2Prog($x) {
    $x = $this->toProg($x);
    $this->validate($x);
    return $x;
  }

  public function prog2Edit($x) {
    return $x;
  }
  
  public function validate($x) {
    
  }
  
  protected function toArray($x) {
    if (!\is_array($x)) {
      if (\is_null($x)) {
        return array();
      } else {
        return array($x);
      }
    } else {
      return $x;
    }
  }
}

