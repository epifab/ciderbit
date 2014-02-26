<?php
namespace module\core\controller;

use \system\Component;
use \system\model\Recordset;
use \system\model\RecordsetBuilder;
use \system\model\FilterClause;
use \system\model\FilterClauseGroup;
use \system\model\LimitClause;
use \system\model\SortClause;
use \system\model\SortClauseGroup;  

class EditNode extends Edit {
  /**
   * Check whether the user has access to the node identified by the $id
   *  parameter according to the $action parameter
   * @param string $action Action (READ, EDIT, DELETE)
   * @param int $id Node id
   * @param object $user User
   * @return boolean True if the user has access to the node
   */
  private static function accessRED($action, $id, $user) {
    $rsb = new RecordsetBuilder('node');
    $rsb->addFilter(new FilterClause($rsb->id, '=', $id));
    
    if ($rsb->countRecords() > 0) {
      switch ($action) {
        case "READ":
          $rsb->addReadModeFilters($user);
          break;
        case "EDIT":
          $rsb->addEditModeFilters($user);
          break;
        case "DELETE":
          $rsb->addDeleteModeFilters($user);
          break;
        default:
          throw new \system\exceptions\InternalError('Invalid @name parameter', array('@name' => 'action'));
      }
      return $rsb->countRecords() > 0;
    }
    return true;
  }

  /**
   * Determines access to creation of root nodes
   * @param array $urlArgs URL arguments
   * @param object $user User
   * @return boolean TRUE if the user is able to create a root node
   * @throws \system\exceptions\InputOutputError
   */
  public static function accessAdd($urlArgs, $user) {
    $nodeTypes = \system\utils\Cache::nodeTypes();
    
    if (isset($nodeTypes[$urlArgs[0]])) {
      throw new \system\exceptions\InputOutputError('Invalid node type.');
    }
    if (!\in_array($urlArgs[0], $nodeTypes['#'])) {
      return false;
    }
    
    // only superuser is able to add nodes to the root
    return $user && $user->superuser;
  }
  
  /**
   * Determines access to creation of a node as a child of a specific node
   * @param array $urlArgs URL arguments
   * @param object $user User
   * @return boolean TRUE if the user is able to create the node
   * @throws \system\exceptions\InputOutputError
   */
  public static function accessAdd2Node($urlArgs, $user) {
    $nodeTypes = \system\utils\Cache::nodeTypes();
    
    if (isset($nodeTypes[$urlArgs[1]])) {
      throw new \system\exceptions\InputOutputError('Invalid node type.');
    }
    
    // get the parent node
    $rsb = new RecordsetBuilder('node');
    $rsb->using('type');
    $rsb->addFilter(new FilterClause($rsb->id, '=', $urlArgs[0]));
    $rsb->addEditModeFilters($user); // Check if the logged user has sufficient permissions to edit the parent node
    $parentNode = $rsb->selectFirst();
    
    if (!$parentNode) {
      return false;
    }
    // edit permissions ok
    
    // just need to check that is allowed to add the node
    if (!\in_array($urlArgs[1], $nodeTypes[$parentNode->type]['children'])) {
      return false;
    }
    return true;
  }
  
  public static function accessRead($urlArgs, $user) {
    return self::accessRED("READ", $urlArgs[0], $user);
  }

  /**
   * Determines access to node editing
   * @param array $urlArgs URL arguments
   * @param object $user User
   * @return boolean TRUE if the user should be able to edit this node
   */
  public static function accessEdit($urlArgs, $user) {
    return self::accessRED("EDIT", $urlArgs[0], $user);
  }

  /**
   * Determines access to node deletion
   * @param array $urlArgs URL arguments
   * @param object $user User
   * @return boolean TRUE if the user should be able to delete this node
   */
  public static function accessDelete($urlArgs, $user) {
    return self::accessRED("DELETE", $urlArgs[0], $user);
  }
  
  /**
   * Edit actions (add, add to a node, edit.
   * @return array List of available actions
   */
  public function getEditActions() {
    return array('Add', 'Add2Node', 'Edit');
  }
  
  /**
   * Returns the node builder
   * @return \system\model\RecordsetBuilder Node builder
   */
  protected function getNodeBuilder() {
    $rsb = new \system\model\RecordsetBuilder('node');
    $rsb->usingAll();
    return $rsb;
  }
  
  /**
   * Creates a new (temporary) node recordset
   * @param string $type Recordset type
   * @param int $parentId Parent node id
   * @return \system\model\RecordsetInterface
   * @throws \system\exceptions\InputOutputError
   */
  private function getTmpRecordset($type, $parentId=null) {
    $parentNode = null;
    if ($parentId) {
      // Initialize parent node (if any)
      $prsb = new \system\model\RecordsetBuilder('node');
      $prsb->using('*');
      $parentNode = $prsb->selectFirstBy(array('id' => $parentId));
      if (empty($parentNode)) {
        // Parent node not found
        throw new \system\exceptions\InputOutputError('The node you were looking for was not found.');
      }
    }
    
    // Initialize node builder
    $rsb = $this->getNodeBuilder();
    
    $node = null;

    // Always handles with a temporary node
    // Get the temp node id from the session
    $nodeId = \system\session\Session::getInstance()->get('core::EditNode', 'temp_node_id');
    // If the node ID exists..
    if ($nodeId) {
      // Load the node
      $node = $rsb->selectFirstBy(array('id' => $nodeId));
      if (!$node || !$node->temp) {
        // Node not found or "temp" field set to 0
        $node = null;
      }
      else if ($node->record_mode->owner_id != \system\utils\Login::getLoggedUserId()) {
        // The node owner must be the current logged in user
        $node->delete(); // This should never happen!
        $node = null;
      }
      else if ($node->type != $type) {
        // The node type must match the current node type
        $node->delete();
        $node = null;
      }
    }

    if (empty($node)) {
      // If the temp node is invalid or does not exist we need to create a new 
      //  one
      $da = \system\model\DataLayerCore::getInstance();
      $da->beginTransaction();
      
      try {
        $node = $rsb->newRecordset();
        
        $node->temp = true;
        $node->type = $type;

        if (empty($parentNode)) {
          // No parent node
          $node->parent_id = null;
          $node->ldel = 1 + $da->executeScalar("SELECT MAX(rdel) FROM node");
          $node->rdel = $node->ldel + 1;
          // Node sort index
          $node->sort_index = 1 + $da->executeScalar("SELECT MAX(sort_index) FROM node WHERE parent_id IS NULL");
        }
        else {
          // Parent node
          $node->parent_id = $parentNode->id;
          // We set the left delimiter to the parent node right delimiter
          $node->ldel = $parentNode->rdel;
          $node->rdel = $node->ldel + 1;
          // We need to adjust the parent node right delimiter 
          //  and delimiters for every node to preserve the tree structure
          $da->executeUpdate("UPDATE node SET ldel = ldel + 2 WHERE ldel > " . $node->rdel);
          $da->executeUpdate("UPDATE node SET rdel = rdel + 2 WHERE rdel >= " . $node->ldel);
          // Node sort index
          $node->sort_index = 1 + $da->executeScalar("SELECT MAX(sort_index) FROM node WHERE parent_id = " . $parentNode->id);
        }

        $node->save(
          // default record mode options
          \system\model\RecordMode::MODE_SU_OWNER,
          \system\model\RecordMode::MODE_SU_OWNER,
          \system\model\RecordMode::MODE_SU_OWNER
        );
        
        \system\session\Session::getInstance()->set('code::EditNode', 'temp_node_id', $node->id);
        
        $da->commitTransaction();
      }
      
      catch (\Exception $ex) {
        $da->rollbackTransaction();
        throw $ex;
      }
    }
    return $node;
  }
  
  /**
   * Returns the node recordset to edit
   * @return \system\model\RecordsetInterface
   * @throws \system\exceptions\InputOutputError
   */
  protected function getEditRecordsets() {
    $node = null;
    switch ($this->getAction()) {
      case 'Add':
        $node = $this->getTmpRecordset($this->getUrlArg(0));
        break;

      case 'Add2Node':
        $node = $this->getTmpRecordset($this->getUrlArg(1), $this->getUrlArg(0));
        break;

      case 'Edit':
        $node = $this->getNodeBuilder()->selectFirstBy(array('id' => $this->getUrlArg(0)));
        break;
    }
    $recordsets = array(
      'node' => $node,
      'node__record_mode' => $node->record_mode
    );
    foreach ($node->texts as $nodeText) {
      $recordsets['node_' . $nodeText->lang] = $nodeText;
    }
    
    return $recordsets;
  }
  
  /**
   * Form ID
   * @return string Form ID
   */
  public function getFormId() {
    switch ($this->getAction()) {
      case 'Add':
      case 'Add2Node':
        return 'node-create-form';
        break;
      case 'Edit':
      default:
        return 'node-update-form';
        break;
    }
  }
  
  /**
   * Form template
   * @return string Form template
   */
  public function getFormTemplate() {
    $node = $this->getForm()->getRecordset('node');
    
    // Template name suggestions
    $templates = array(
      'edit-node--' . $node->id,
      'edit-node-' . $node->type,
      'edit-node'
    );
    foreach ($templates as $t) {
      if (\system\Main::templateExists($t)) {
        return $t;
      }
    }
    throw new \system\exceptions\InternalError(
      'No suitable editing template found for this node.'
      . '<p>Possible template suggestions are:</p>'
      . '<ul>'
      . '<li>edit-node--@nid</li>'
      . '<li>edit-node-@type</li>'
      . '<li>edit-node</li>'
      . '</ul>', array('@nid' => $rs->id, '@type' => $rs->type)
    );
  }
  
  public function submitAdd() {
    $form = $this->getForm();
    foreach ($form->getRecordsets() as $recordset) {
      $recordset->update();
    }
  }
  
  public function submitAdd2Node() {
    $form = $this->getForm();
    // Make the node valid
    $form->getRecordset('node')->temp = false;
    foreach ($form->getRecordsets() as $recordset) {
      $recordset->update();
    }
  }
  
  public function submitEdit() {
    $form = $this->getForm();
    foreach ($form->getRecordsets() as $recordset) {
      $recordset->update();
    }
  }
  
  public function runDelete() {
    throw new \system\exceptions\InternalError('Not yet implemented');
  }
}