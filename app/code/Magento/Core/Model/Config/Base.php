<?php
/**
 * Abstract configuration class
 * Used to retrieve core configuration values
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Core
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Core\Model\Config;

class Base extends \Magento\Simplexml\Config
{
    /**
     * List of instances
     *
     * @var array
     */
    public static $instances = array();

    /**
     * @param string|\Magento\Simplexml\Element $sourceData $sourceData
     */
    public function __construct($sourceData = null)
    {
        $this->_elementClass = 'Magento\Core\Model\Config\Element';
        parent::__construct($sourceData);
        self::$instances[] = $this;
    }

    /**
     * Cleanup objects because of simplexml memory leak
     */
    public static function destroy()
    {
        if (is_array(self::$instances)) {
            foreach (self::$instances  as $instance) {
                $instance->_xml = null;
            }
        }
        self::$instances = array();
    }
}
