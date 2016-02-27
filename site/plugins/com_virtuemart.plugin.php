<?php
/**
 * JComments plugin for VirtueMart objects support
 * Fixed by Studio 42 , tested with Joomla 3.4.8
 * @version 2.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

class jc_com_virtuemart extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
		jimport('joomla.filesystem.file');

		$info = new JCommentsObjectInfo();
		$configHelper = JPATH_ADMINISTRATOR.'/components/com_virtuemart/helpers/config.php';

		if (JFile::exists($configHelper)) {
			if (!class_exists('VmConfig')) {
				require_once($configHelper);
			}
	
			VmConfig::loadConfig();

			$db = JFactory::getDBO();
			$db->setQuery('SELECT product_name, created_by FROM #__virtuemart_products_' . VMLANG . ' as l 
				LEFT JOIN #__virtuemart_products as p ON p.virtuemart_product_id = l.virtuemart_product_id  
				LEFT JOIN #__virtuemart_product_categories as pc ON pc.virtuemart_product_id = p.virtuemart_product_id  
				WHERE p.virtuemart_product_id =' . $id);
			$row = $db->loadObject();
			
			if (!empty($row)) {
				$info->title = $row->product_name;
				$info->userid = $row->created_by;
				$info->link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $id . '&virtuemart_category_id=' . $categoryId);
			}
		}

		return $info;
	}
}