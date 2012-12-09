<?php
namespace system\model;

class Recordset implements RecordsetInterface {
	/**
	 * @var RecordsetBuilder
	 */
	private $builder;
	private $fields = array();
	private $modifiedFields = array();
	private $hasOneRelations = array();
	private $hasManyRelations = array();
	// flag indicante la presenza del record nel DB
	private $stored = false;
	
	public function __construct(RecordsetBuilder $builder, $data=null) {

		$this->builder = $builder;

		$this->stored = !\is_null($data);
		
		// Inizializzo i valori dei campi
		foreach ($this->builder->getMetaTypeList() as $name => $metaType) {
			// inizializzo il campo
			$this->fields[$name] = null;
			
			if (!\is_null($data) && \array_key_exists($metaType->getAlias(), $data)) {
				$this->setDb($name, $data[$metaType->getAlias()]);
			} else {
				$this->setDb($name, $metaType->getDefaultValue());
			}
			
//			if ($name == "path_file1")
//				throw new \Exception(\array_key_exists("path_file1", $this->fields) ? "esiste" : "NONESISTE");
		}

		// Inizializzo le has one relations
		foreach ($this->builder->getHasOneRelationBuilderList() as $name => $builder) {
			// carico tutte le has many relation
			$this->hasOneRelations[$name] = $builder->newRecordset($data);
		}
	}
		
	public function isStored() {
		return $this->stored;
	}
	
	public function searchMetaType($path) {
		return $this->builder->searchMetaType($path);
	}
	
	public function getMetaTypeList() {
		return $this->builder->getMetaTypeList();
	}
	
	public function getBuilder() {
		return $this->builder;
	}
	
	public function __set($name, $value) {
		return $this->setProg($name, $value);
	}
	
	public function __get($name) {
		if (\array_key_exists($name, $this->hasOneRelations)) {

			// Has One Relation
			return $this->hasOneRelations[$name];

		} else if (\array_key_exists($name, $this->hasManyRelations)) {

			// Has Many Relation (precedentemente caricata)
			return $this->hasManyRelations[$name];

		} else if (\array_key_exists($name, $this->builder->getHasManyRelationBuilderList())) {
			
			// Has Many Relation valida ma ancora non caricata (LAZY LOAD)

			// recupero il builder del recordset corrispondente alla has many relation
			$builder = $this->builder->searchRelationBuilder($name);

			// salvo il filtro originale
			$oldFilter = $builder->getFilter();

			// costruisco un nuovo filtro con le clausole della relazione in AND con il filtro originale
			$newFilter = null;
			
			foreach ($builder->getClauses() as $parentField => $childField) {
				$metaType = $builder->searchMetaType($childField);
				$filter = new FilterClause($metaType, "=", $this->fields[$parentField]);
				if (\is_null($newFilter)) {
					$newFilter = new FilterClauseGroup($filter);
				} else {
					$newFilter->addClauses("AND", $filter);
				}
			}
			if (!\is_null($oldFilter)) {
				$newFilter->addClauses("AND", $oldFilter);
			}

			// setto il nuovo filtro nel builder
			$builder->setFilter($newFilter);

			// seleziono i records
			$this->hasManyRelations[$name] = $builder->select();

			// reimposto il filtro originale
			$builder->setFilter($oldFilter);
			
			return $this->hasManyRelations[$name];

		} else {
			
			// Campo
			return $this->getProg($name);
			
		}
	}
	
	public function search($path) {
		if (empty($path)) {
			return $this;
		}
		$dotPosition = strpos($path, ".");
		try {
			if ($dotPosition === false) {
				return $this->__get($path);
			} else {
				$first = $this->__get(substr($path, 0, $dotPosition));
				if ($first instanceof RecordsetInterface) {
					return $first->search(substr($path, $dotPosition+1));
				} else {
					return null;
				}
			}
		} catch (\system\InternalErrorException $ex) {
			return null;
		}
	}

	public function setProg($name, $value) {
		if (\array_key_exists($name, $this->fields)) {
			if (!$this->getBuilder()->searchMetaType($name)->isVirtual()) {
				$this->modifiedFields[$name] = $value;
			}
			$this->fields[$name] = $value;
		} else {
			throw new \system\InternalErrorException(\system\Lang::translate('Field or relation <em>@name</em> does not exist or is not used.', array('@name' => $name)));
		}
	}
	
	public function getProg($name) {
		if (\array_key_exists($name, $this->modifiedFields)) {
			return $this->modifiedFields[$name];
		} else if (\array_key_exists($name, $this->fields)) {
			return $this->fields[$name];
		} else {
			$mt = $this->builder->searchMetaType($name);
			if ($mt && $mt instanceof MetaVirtual) {
				return \call_user_func($mt->getHandler(), $this);
			}
			throw new \system\InternalErrorException(\system\Lang::translate('Field or relation <em>@name</em> not found.', array("@name" => $name)));
		}
	}
	
	public function setDb($name, $value) {
		if (\array_key_exists($name, $this->fields)) {
			$metaType = $this->builder->searchMetaType($name);
			$this->fields[$name] = $metaType->db2Prog($value);
		}
	}
	
	public function getDb($name) {
		$progValue = $this->getProg($name);
		$metaType = $this->builder->searchMetaType($name);
		return $metaType->prog2Db($progValue);
	}

	public function setEdit($name, $value) {
		if (\array_key_exists($name, $this->fields)) {
			$metaType = $this->builder->searchMetaType($name);
			if ($metaType instanceof MetaVirtual) {
				throw new \system\InternalErrorException(\system\Lang::translate('Cannot set <em>@name</em> value (virtual field).', array("@name" => $name)));
			}
			$this->modifiedFields[$name] = $metaType->edit2Prog($value);
		} else {
			throw new \system\InternalErrorException(\system\Lang::translate('Field or relation <em>@name</em> not found.', array("@name" => $name)));
		}
	}
	
	public function getEdit($name) {
		$progValue = $this->getProg($name);
		$metaType = $this->builder->searchMetaType($name);
		return $metaType->prog2Edit($progValue);
	}
	
	public function getRead($name) {
		$progValue = $this->getProg($name);
		$metaType = $this->builder->searchMetaType($name);
		return $metaType->prog2Read($progValue);
	}
	
	public function save($readMode=null, $editMode=null, $deleteMode=null, $roleId=null) {
		if (!$this->isStored()) {
			return $this->create($readMode, $editMode, $deleteMode, $roleId);
		} else {
			return $this->update($readMode, $editMode, $deleteMode, $roleId);
		}
	}
	
	/**
	 * Inserisce il record.
	 * Nel caso la tabella contenga un campo MySql di tipo auto_increment
	 * ne recupera contestualmente il valore.
	 * Nel caso la tabella sfrutti i record modes, ne inserisce contestualmente le informazioni.
	 * NB: NON INSERISCE AUTOMATICAMENTE RECORD RELATIVI A RELAZIONI HAS MANY O HAS ONE.
	 * @param int $readMode Read Mode (vedi \system\model\RecordMode)
	 * @param int $editMode Edit Mode (vedi \system\model\RecordMode)
	 * @param int $deleteMode Delete Mode (vedi \system\model\RecordMode)
	 * @param int $roleId Id del ruolo utente
	 */
	public function create($readMode=null, $editMode=null, $deleteMode=null, $roleId=null) {
		
		\system\logic\Module::raise("onCreate", $this);
		
		if ($this->builder->isRecordModed()) {
			$this->createRecordMode($readMode, $editMode, $deleteMode, $roleId);
		}
		
		$q1 = "";
		$q2 = "";

		foreach ($this->builder->getMetaTypeList() as $name => $metaType) {
			if (\array_key_exists($name, $this->modifiedFields)) {
				$q1 .= ($q1 === "" ? "" : ", ") . $name;
				$q2 .= ($q2 === "" ? "" : ", ") . $metaType->prog2Db($this->modifiedFields[$name]);
			}
		}
		
		if ($q1 === "") {
			throw new \system\InternalErrorException(\system\Lang::translate('Cannot create a record with no field values.'));
		}

		$query = "INSERT INTO " . $this->builder->getTableName() . " (" . $q1 . ") VALUES (" . $q2 . ")";

		$dataAccess = DataLayerCore::getInstance();
		$dataAccess->executeUpdate($query, __FILE__, __LINE__);
		
		foreach ($this->modifiedFields as $k => $v) {
			$this->fields[$k] = $v;
		}
		$this->modifiedFields = array();
		
		if ($this->builder->isAutoIncrement()) {
			$primaryMetaTypeList = $this->builder->getPrimaryKey();
			$this->fields[$primaryMetaTypeList[0]->getName()] = $dataAccess->sqlLastInsertId();
		}

		$this->stored = true;
	}

	/**
	 * Aggiorna il record.
	 * Controlla contestualmente i permessi dell'utente.
	 * NB: NON AGGIORNA AUTOMATICAMENTE LE RELAZIONI HAS ONE ED HAS MANY!
	 */
	public function update($readMode=null, $editMode=null, $deleteMode=null, $roleId=null) {
		$q1 = "";
		
		\system\logic\Module::raise("onUpdate", $this);

		if (!$this->isStored()) {
			throw new \system\InternalErrorException(\system\Lang::translate('Recordset does not exist.'));
		}
		
		// Controllo i permessi dell'utente per l'aggiornamento del record
		if ($this->builder->isRecordModed()) {
			$this->updateRecordMode($readMode, $editMode, $deleteMode, $roleId);
		}

		foreach ($this->modifiedFields as $name => $value) {
			$metaType = $this->builder->searchMetaType($name);
			$q1 .= ($q1 === "" ? "" : ", ") . $name . " = " . $metaType->prog2Db($this->modifiedFields[$name]);
		}

		$query = "UPDATE " . $this->builder->getTableName() . " SET " . $q1 . " WHERE ";
		$first = true;
		foreach ($this->builder->getPrimaryKey() as $metaType) {
			$first ? $first = false : $query .= " AND ";
			$query .= $metaType->getName() . " = " . $metaType->prog2Db($this->fields[$metaType->getName()]);
		}

		$dataAccess = DataLayerCore::getInstance();
		$dataAccess->executeUpdate($query, __FILE__, __LINE__);
	}

//	private function deleteRelations() {
//		foreach ($this->builder->getHasManyRelationBuilderList() as $name => $builder) {
//			// forzo il caricamento di tutte le relazioni has many
//			$recordsets = $this->__get($name);
//
//			if (count($recordsets) == 0) {
//				continue;
//			}
//			
//			// le cancello
//			$query = "DELETE FROM " . $builder->getTableName();
//			$where = "";
//			foreach($builder->getClauses() as $parentField => $childField) {
//				$where .= empty($where) ? " WHERE " : " AND ";
//
//				// recupero le informazioni sul campo della tabella madre
//				$parentMetaType = $this->builder->searchMetaType($parentField);
//				// imposto la clausola
//				$where .= $childField . " = " . $parentMetaType->prog2Db($this->fields[$parentField]);
//			}
//			
//			$query .= $where;
//			
//			// Cancello tutte le righe della relazione
//			DataLayerCore::getInstance()->executeUpdate($query, __FILE__, __LINE__);
//
//			// Controllo che la chiave primaria della tabella
//			// della has many relation associata
//			// sia composta da un unico campo di tipo intero
//			$deleteRecordModes = $builder->isRecordMode();
//			
//			foreach($recordsets as $recordset) {
//				if ($deleteRecordModes) {
//					// Cancello il record_mode associato al record della has many relation (se esiste)
//					$recordset->deleteRecordMode();
//				}
//				$recordset->deleteRelations();
//			}
//		}
//
//		foreach ($this->builder->getHasOneRelationBuilderList() as $name => $builder) {
//			
//			$query = "DELETE FROM " . $builder->getTableName() . " WHERE ";
//			$first = true;
//			foreach($builder->getClauses() as $parentField => $childField) {
//				$first ? $first = false : $query .= " AND ";
//
//				// recupero le informazioni sul campo della tabella madre
//				$parentMetaType = $this->builder->searchMetaType($parentField);
//				// imposto la clausola
//				$query .= $childField . " = " . $parentMetaType->prog2Db($this->fields[$parentField]);
//			}
//			// Cancello tutte le righe della relazione
//			DataLayerCore::getInstance()->executeUpdate($query, __FILE__, __LINE__);
//
//			$recordset = $this->__get($name);
//			// Controllo che la chiave primaria della tabella
//			// della has many relation associata
//			// sia composta da un unico campo di tipo intero
//			if ($builder->isRecordMode()) {
//				// Cancello il record_mode associato alla has one relation (se esiste)
//				$recordset->deleteRecordMode();
//			}
//			$recordset->deleteRelations();
//		}
//	}

	/**
	 * Cancella il record.
	 */
	public function delete() {
		if (!$this->isStored()) {
			return;
		}
		
		\system\logic\Module::raise("onDelete", $this);
		
		$query = "DELETE FROM " . $this->builder->getTableName() . " WHERE ";
		$first = true;
		foreach ($this->builder->getPrimaryKey() as $metaType) {
			$first ? $first = false : $query .= " AND ";
			$query .= $metaType->getName() . " = " . $metaType->prog2Db($this->fields[$metaType->getName()]);
		}
		$dataAccess = DataLayerCore::getInstance();
		$dataAccess->executeUpdate($query, __FILE__, __LINE__);

		$this->stored = false;

//		foreach ($this->builder->getHasManyRelationBuilderList() as $name => $builder) {
//			if ($builder->getOnDelete() == "CASCADE") {
//				// forzo il caricamento di tutte le relazioni has many
//				$recordsets = $this->__get($name);
//
//				if (count($recordsets) == 0) {
//					continue;
//				}
//
//				foreach ($recordsets as $recordset) {
//					// Le cancello tutte una ad una
//					$recordset->delete();
//				}
//			}
//		}
//
//		foreach ($this->builder->getHasOneRelationBuilderList() as $name => $builder) {
//			if ($builder->getOnDelete() == "CASCADE") {
//				$this->__get($name)->delete();
//			}
//		}

	}

	/**
	 *	Aggiunge informazioni sui permessi di lettura e scrittura sul record.
	 * Setta la chiave esterna per il record_mode_idR
	 * @param int $readMode Read Mode (vedi \system\model\RecordMode)
	 * @param int $editMode Edit Mode (vedi \system\model\RecordMode)
	 * @param int $deleteMode Delete Mode (vedi \system\model\RecordMode)
	 * @param int $roleId Id ruolo utente
	 */
	private function createRecordMode($readMode, $editMode, $deleteMode, $roleId) {
		$recordMode = $this->record_mode;
		
		switch ($readMode) {
			case \system\model\RecordMode::MODE_NOBODY:
			case \system\model\RecordMode::MODE_SU:
			case \system\model\RecordMode::MODE_SU_OWNER:
			case \system\model\RecordMode::MODE_SU_OWNER_ROLE:
			case \system\model\RecordMode::MODE_ANYONE:
				$recordMode->read_mode = $readMode;
				break;
			
			default:
				$recordMode->read_mode = \system\model\RecordMode::MODE_ANYONE;
				break;
		}
		
		switch ($editMode) {
			case \system\model\RecordMode::MODE_NOBODY:
			case \system\model\RecordMode::MODE_SU:
			case \system\model\RecordMode::MODE_SU_OWNER:
			case \system\model\RecordMode::MODE_SU_OWNER_ROLE:
			case \system\model\RecordMode::MODE_ANYONE:
				$recordMode->edit_mode = $editMode;
				break;
			
			default:
				$recordMode->edit_mode = \system\model\RecordMode::MODE_SU_OWNER_ROLE;
				break;
		}
		
		switch ($deleteMode) {
			case \system\model\RecordMode::MODE_NOBODY:
			case \system\model\RecordMode::MODE_SU:
			case \system\model\RecordMode::MODE_SU_OWNER:
			case \system\model\RecordMode::MODE_SU_OWNER_ROLE:
			case \system\model\RecordMode::MODE_ANYONE:
				$recordMode->delete_mode = $deleteMode;
				break;
			
			default:
				$recordMode->delete_mode = \system\model\RecordMode::MODE_SU_OWNER_ROLE;
				break;
		}
		
		if (!\is_null($roleId)) {
			$recordMode->roleId = (int)$roleId;
		}
		
		$recordMode->owner_id = \system\Login::getLoggedUserId();
		$recordMode->ins_date_time = \time();
		$recordMode->last_upd_date_time = \time();
		$recordMode->last_modifier_id = \system\Login::getLoggedUserId();
		
		$recordMode->create();
		
		if (\config\settings()->RECORD_MODE_LOGS) {
			$this->createRecordModeLog($recordMode);
		}
		
		$this->setProg($this->builder->getRecordModeField(), $recordMode->id);
	}
	
	private function updateRecordMode($readMode, $editMode, $deleteMode, $roleId) {
		$recordMode = $this->record_mode;
		
		if (\is_null($recordMode)) {
			return null;
		}
		
		switch ($readMode) {
			case \system\model\RecordMode::MODE_NOBODY:
			case \system\model\RecordMode::MODE_SU:
			case \system\model\RecordMode::MODE_SU_OWNER:
			case \system\model\RecordMode::MODE_SU_OWNER_ROLE:
			case \system\model\RecordMode::MODE_ANYONE:
				$recordMode->read_mode = $readMode;
				break;
			
			default:
				// Lascio inalterato il valore
				break;
		}
		
		switch ($editMode) {
			case \system\model\RecordMode::MODE_NOBODY:
			case \system\model\RecordMode::MODE_SU:
			case \system\model\RecordMode::MODE_SU_OWNER:
			case \system\model\RecordMode::MODE_SU_OWNER_ROLE:
			case \system\model\RecordMode::MODE_ANYONE:
				$recordMode->edit_mode = $editMode;
				break;
			
			default:
				// Lascio inalterato il valore
				break;
		}
		
		switch ($deleteMode) {
			case \system\model\RecordMode::MODE_NOBODY:
			case \system\model\RecordMode::MODE_SU:
			case \system\model\RecordMode::MODE_SU_OWNER:
			case \system\model\RecordMode::MODE_SU_OWNER_ROLE:
			case \system\model\RecordMode::MODE_ANYONE:
				$recordMode->delete_mode = $deleteMode;
				break;
			
			default:
				// Lascio inalterato il valore
				break;
		}

		if (!\is_null($roleId)) {
			// Cambio il gruppo solo se non nullo
			$recordMode->role_id = (int)$roleId;
		}
		
		$recordMode->last_modifier_id = \system\Login::getLoggedUserId();
		$recordMode->last_upd_date_time = \time();
		$recordMode->update();
		
		if (\config\settings()->RECORD_MODE_LOGS) {
			$this->createRecordModeLog($recordMode);
		}
	}
	
	private function createRecordModeLog($recordMode) {
		$recordModeLog = new RecordsetBuilder("record_mode_log");
		$recordModeLog->using("*");

		$rs = $recordModeLog->newRecordset();

		$rs->record_mode_id = $recordMode->id;
		$rs->modifier_id = $recordMode->last_modifier_id;
		$rs->upd_date_time = $recordMode->last_upd_date_time;
		
		$rs->create();
	}
}
?>