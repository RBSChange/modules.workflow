<?php
/**
 * workflow_persistentdocument_case
 * @package modules.workflow
 */
class workflow_persistentdocument_case extends workflow_persistentdocument_casebase
{
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getParameter($name)
	{
		return $this->getDocumentService()->getParameter($this, $name);
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParameter($name, $value)
	{
		$this->getDocumentService()->setParameter($this, $name, $value);
	}
	
	/**
	 * Add the parameters defined in the document model.
	 * @param integer $documentid
	 */
	public function setDocumentid($documentid)
	{
		parent::setDocumentid($documentid);

		// Get the document associated to this case.
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$document = $provider->getDocumentInstance($documentid);

		// Get the parameters and set them to the case.
		$parametersArray = $document->getPersistentModel()->getWorkflowParameters();
		$cs = $this->getDocumentService();
		foreach ($parametersArray as $name => $value)
		{
			$cs->setParameter($this, $name, $value);
		}
	}

	/**
	 * Add the case to the workitem.
	 * workflow_persistentdocument_workitem
	 */
	public function addWorkitem($newValue)
	{
		parent::addWorkitem($newValue);
		$newValue->setCase($this);
		$this->setModificationdate(null);
	}

	/**
	 * Add the case to the token.
	 * workflow_persistentdocument_token
	 */
	public function addToken($newValue)
	{
		parent::addToken($newValue);
		$newValue->setCase($this);
		$this->setModificationdate(null);
	}
	
	/**
	 * workflow_persistentdocument_workitem
	 */
	public function workitemTrigged($workitem)
	{
		Framework::fatal(__METHOD__);
		$this->removeWorkitem($workitem);
		parent::addWorkitem($workitem);
		$this->setModificationdate(null);
	}
	
	/**
	 * @var Array
	 */
	private $pendingCurrentUserDocumentEntries = array();
	
	/**
	 * @param String $actionName
	 * @param array $info
	 * @param String $moduleName
	 */
	public function addPendingCurrentUserDocumentEntry($actionName, $info, $moduleName)
	{
		$this->pendingCurrentUserDocumentEntries[] = array($actionName, $info, $moduleName);
	}
	
	/**
	 * @param String $actionName
	 * @param array $info
	 * @param String $moduleName
	 */
	public function savePendingCurrentUserDocumentEntry()
	{
		Framework::debug(__METHOD__ . ' ' . count($this->pendingCurrentUserDocumentEntries) . ' entries to save');
		foreach ($this->pendingCurrentUserDocumentEntries as $entry)
		{
			UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($entry[0], $this, $entry[1], $entry[2]);
		}
		$this->pendingCurrentUserDocumentEntries = array();
	}
}