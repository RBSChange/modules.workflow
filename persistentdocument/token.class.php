<?php
/**
 * workflow_persistentdocument_token
 * @package modules.workflow
 */
class workflow_persistentdocument_token extends workflow_persistentdocument_tokenbase
{
	/**
	 * @var workflow_persistentdocument_case
	 */
	private $m_case;

	/**
	 * @return workflow_persistentdocument_case
	 */
	public function getCase()
	{
		if (is_null($this->m_case))
		{
			$this->checkLoaded();
			$relations = $this->getProvider()->getChildRelationBySlaveDocumentId($this->getId(), 'token', 'modules_workflow/case');
			$this->m_case = $this->getProvider()->getDocumentInstance($relations[0]->getDocumentId1());
		}
		return $this->m_case;
	}

	/**
	 * @param workflow_persistentdocument_case $case
	 */
	public function setCase($case)
	{
		$this->checkLoaded();
		$this->m_case = $case;
	}
}