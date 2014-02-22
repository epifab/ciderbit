<?php
namespace module\core\view;

use system\view\Api as Api;
use system\view\Form as Form;

class CoreApi {
  private static $javascript;
  
  public static function javascript($code) {
    self::$javascript .= "\n" . $code;
  }

  public static function jss() {
    return self::$javascript;
  }

  
  public static function getEditFormId() {
    $vars = \system\view\Template::current()->getVars();
    return 'system-edit-form-' . $vars['system']['component']['requestId'];
  }

  public static function generateInputId($path) {
    static $ids = array();
    if (\array_key_exists($path, $ids)) {
      return 'de-input-' . \str_replace('.', '-', $path) . '-' . $ids[$path];
      $ids[$path]++;
    } else {
      return 'de-input-' . \str_replace('.', '-', $path);
      $ids[$path] = 1;
    }
  }

  public static function generateInputName($path) {
    return 'recordset[' . $path . ']';
  }

  //////////////////////
  // BLOCKS
  //////////////////////
  public static function blockForm($content, $params, $open) {
    $vars = \system\view\Template::current()->getVars();
    
    $recordset = \cb\array_item('recordset', $params);
    $formId = \cb\array_item('id', $params, array('required' => true));

    $id = $formId . '-' . $vars['system']['component']['requestId'];

    if ($open) {
      Form::startForm($formId, $recordset);
    } 
    else {
      Form::closeForm();

      $form = "\n"
        . '<form id="' . $id . '" name="' . $formId . '" method="post" enctype="multipart/form-data" action="' . $vars['system']['component']['url'] . '">'
        // forzo le risposte ad avere gli stessi ID per il form e per i contenuti
        . '<input type="hidden" name="system[formId]" value="' . $formId . '"/>'
        . '<input type="hidden" name="system[requestId]" value="' . $vars['system']['component']['requestId'] . '"/>';
      
      return $form . $content . '</form>';
    }
  }
  
  public static function blockRecordset($content, $params, $open) {
    
  }

  public static function deError($path) {
    $vars = \system\view\Template::current()->getVars();
    if (\array_key_exists('errors', $vars) && \array_key_exists($path, $vars['errors'])) {
      return '<div class="de-error">' . $vars['errors'][$path] . '</div>';
    }
  }
  
  public static function input($params) {
    $widget = \cb\array_item('widget', $params, array('required' => true));
    $name = \cb\array_item('name', $params, array('required' => true));
    $value = \cb\array_item('value', $params, array('required' => true));

    $form = Form::getCurrent();
    if ($form) {
      $form->addInput($name, $widget, $value, $params);
      return $form->renderInput($name);
    }
  }
  
  public static function formRecordset($name, \system\model\RecordsetInterface $recordset) {
    $form = Form::getCurrent();
    if ($form) {
      $form->addRecordset($name, $recordset);
    }
  }
  
  public static function recordsetInput($params) {
    $recordsetName = \cb\array_item('recordset', $params, array('required' => true));
    $path = \cb\array_item('path', $params, array('required' => true));

    $form = Form::getCurrent();
    if ($form) {
      $recordset = $form->getRecordset($recordsetName);
      if ($recordset) {
        $field = $recordset->getBuilder()->searchField($path, true);
        
        $widget = isset($params['widget']) ? $params['widget'] : $field->getEditWidget();
        
        $id = $form->getName() . '__recordset__' . $recordsetName . '__' . \cb\plaintext(\str_replace('.', '__', $path));
        $name = 'recordset[' . $recordsetName . '][' . $path . ']';
        $value = $recordset->getProg($path);
        
        $input = 
          $params
          + array('id' => $id, 'name' => $name, 'value' => $value)
          + $field->getAttributes();

        $form->addInput($name, $widget, $value, $input);
        $form->addRecordsetInput($recordsetName, $name, $path);

        return $form->renderInput($name);
      }
    }
  }
  
  public static function submitControl($label=null) {
    if (\is_null($label)) {
      $label = \cb\t('Save');
    }
    $vars = \system\view\Template::current()->getVars();
    if ($vars['system']['component']['requestType'] != 'MAIN') {
      return '<div class="de-controls"><input class="de-control" type="submit" value="' . $label . '"/></div>';
    }
  }

  public static function blockLink($content, $params, $open) {
    if (!$open) {
      $params['url'] = \cb\array_item('url', $params, array('required' => true, 'prefix' => \config\settings()->BASE_DIR));

      $url = $params['url'];
      $ajax = \cb\array_item('ajax', $params, array('default' => true, 'options' => array(false, true)));
      $class = \cb\array_item('class', $params, array('default' => 'link'));
      $params['system'] = array(
        'requestType' => 'MAIN',
  //      'requestId' => null
      );
      $jsArgs = \system\utils\Utils::php2Js($params); //array_merge(array('url' => $url), \cb\array_item('args', $params, array('default' => array()))));

      if ($ajax) {
        $confirm = \cb\array_item('confirm', $params, array('default' => false, 'options' => array(false, true)));
        if ($confirm) {
          $confirmTitle = str_replace("'", "\\'", \cb\array_item('confirmTitle', $params, array('default' => '')));
          $confirmQuest = str_replace("'", "\\'", \cb\array_item('confirmQuest', $params, array('default' => '')));
          $action = "ciderbit.confirm('" . $confirmTitle . "', '" . $confirmQuest . "', " . $jsArgs . "); return false;";
        } else {
          $action = "ciderbit.request(" . $jsArgs . "); return false;";
        }
      }
      return 
        '<a href="' . $url . '"'
        . (empty($class) ? '' : ' class="' . $class . '"')
        . (empty($action) ? '' : ' onclick="' . $action . '"') . '>'
        . $content
        . '</a>';
    }
  }
  
  public static function displayNode($node, $display = 'default') {
    $templates = array(
      'node-'. $display . '--' . $node->id,
      'node-'. $display . '-' . $node->type,
      'node-'. $display
    );
    if ($display != 'default') {
      $templates += array(
        'node-default--' . $node->id,
        'node-default-' . $node->type,
        'node-default'
      );
    }
    
    foreach ($templates as $t) {
      if (\system\Main::templateExists($t)) {
        \system\view\Api::import($t, array('node' => $node));
        return;
      }
    }
  }
  
  public static function editNode($node, $display = 'default') {
    $templates = array(
      'edit-node-'. $display . '--' . $node->id,
      'edit-node-'. $display . '-' . $node->type,
      'edit-node-'. $display
    );
    if ($display != 'default') {
      $templates += array(
        'edit-node-default--' . $node->id,
        'edit-node-default-' . $node->type,
        'edit-node-default'
      );
    }
    
    foreach ($templates as $t) {
      if (\system\Main::templateExists($t)) {
        \system\view\Api::import($t, array('node' => $node));
        return;
      }
    }
  }
  
  public static function access($url, $args = array()) {
    return \system\Main::checkAccess($url, $args);
  }

  //////////////////////
  // 
  //////////////////////
}