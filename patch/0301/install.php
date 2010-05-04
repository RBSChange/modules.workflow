<?php
/**
 * workflow_patch_0301
 * @package modules.workflow
 */
class workflow_patch_0301 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('lists.xml');
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'workflow';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0301';
	}
}