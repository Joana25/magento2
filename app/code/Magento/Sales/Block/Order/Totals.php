<?php
/**
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
 * @package     Magento_Sales
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Sales\Block\Order;

class Totals extends \Magento\View\Element\Template
{
    /**
     * Associated array of totals
     * array(
     *  $totalCode => $totalObject
     * )
     *
     * @var array
     */
    protected $_totals;
    protected $_order = null;

    /**
     * Core registry
     *
     * @var \Magento\Core\Model\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\View\Element\Template\Context $context
     * @param \Magento\Core\Model\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\View\Element\Template\Context $context,
        \Magento\Core\Model\Registry $registry,
        array $data = array()
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize self totals and children blocks totals before html building
     *
     * @return \Magento\Sales\Block\Order\Totals
     */
    protected function _beforeToHtml()
    {
        $this->_initTotals();
        foreach ($this->getLayout()->getChildBlocks($this->getNameInLayout()) as $child) {
            if (method_exists($child, 'initTotals')) {
                $child->initTotals();
            }
        }
        return parent::_beforeToHtml();
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            if ($this->hasData('order')) {
                $this->_order = $this->_getData('order');
            } elseif ($this->_coreRegistry->registry('current_order')) {
                $this->_order = $this->_coreRegistry->registry('current_order');
            } elseif ($this->getParentBlock()->getOrder()) {
                $this->_order = $this->getParentBlock()->getOrder();
            }
        }
        return $this->_order;
    }

    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Get totals source object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * Initialize order totals array
     *
     * @return \Magento\Sales\Block\Order\Totals
     */
    protected function _initTotals()
    {
        $source = $this->getSource();

        $this->_totals = array();
        $this->_totals['subtotal'] = new \Magento\Object(array(
            'code'  => 'subtotal',
            'value' => $source->getSubtotal(),
            'label' => __('Subtotal')
        ));


        /**
         * Add shipping
         */
        if (!$source->getIsVirtual() && ((float) $source->getShippingAmount() || $source->getShippingDescription()))
        {
            $this->_totals['shipping'] = new \Magento\Object(array(
                'code'  => 'shipping',
                'field' => 'shipping_amount',
                'value' => $this->getSource()->getShippingAmount(),
                'label' => __('Shipping & Handling')
            ));
        }

        /**
         * Add discount
         */
        if (((float)$this->getSource()->getDiscountAmount()) != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = __('Discount (%1)', $source->getDiscountDescription());
            } else {
                $discountLabel = __('Discount');
            }
            $this->_totals['discount'] = new \Magento\Object(array(
                'code'  => 'discount',
                'field' => 'discount_amount',
                'value' => $source->getDiscountAmount(),
                'label' => $discountLabel
            ));
        }

        $this->_totals['grand_total'] = new \Magento\Object(array(
            'code'  => 'grand_total',
            'field'  => 'grand_total',
            'strong'=> true,
            'value' => $source->getGrandTotal(),
            'label' => __('Grand Total')
        ));

        /**
         * Base grandtotal
         */
        if ($this->getOrder()->isCurrencyDifferent()) {
            $this->_totals['base_grandtotal'] = new \Magento\Object(array(
                'code'  => 'base_grandtotal',
                'value' => $this->getOrder()->formatBasePrice($source->getBaseGrandTotal()),
                'label' => __('Grand Total to be Charged'),
                'is_formated' => true,
            ));
        }
        return $this;
    }

    /**
     * Add new total to totals array after specific total or before last total by default
     *
     * @param   \Magento\Object $total
     * @param   null|string|last|first $after
     * @return  \Magento\Sales\Block\Order\Totals
     */
    public function addTotal(\Magento\Object $total, $after=null)
    {
        if ($after !== null && $after != 'last' && $after != 'first') {
            $totals = array();
            $added = false;
            foreach ($this->_totals as $code => $item) {
                $totals[$code] = $item;
                if ($code == $after) {
                    $added = true;
                    $totals[$total->getCode()] = $total;
                }
            }
            if (!$added) {
                $last = array_pop($totals);
                $totals[$total->getCode()] = $total;
                $totals[$last->getCode()] = $last;
            }
            $this->_totals = $totals;
        } elseif ($after=='last')  {
            $this->_totals[$total->getCode()] = $total;
        } elseif ($after=='first')  {
            $totals = array($total->getCode()=>$total);
            $this->_totals = array_merge($totals, $this->_totals);
        } else {
            $last = array_pop($this->_totals);
            $this->_totals[$total->getCode()] = $total;
            $this->_totals[$last->getCode()] = $last;
        }
        return $this;
    }

    /**
     * Add new total to totals array before specific total or after first total by default
     *
     * @param   \Magento\Object $total
     * @param   null|string $after
     * @return  \Magento\Sales\Block\Order\Totals
     */
    public function addTotalBefore(\Magento\Object $total, $before=null)
    {
        if ($before !== null) {
            if (!is_array($before)) {
                $before = array($before);
            }
            foreach ($before as $beforeTotals) {
                if (isset($this->_totals[$beforeTotals])) {
                    $totals = array();
                    foreach ($this->_totals as $code => $item) {
                        if ($code == $beforeTotals) {
                            $totals[$total->getCode()] = $total;
                        }
                        $totals[$code] = $item;
                    }
                    $this->_totals = $totals;
                    return $this;
                }
            }
        }
        $totals = array();
        $first = array_shift($this->_totals);
        $totals[$first->getCode()] = $first;
        $totals[$total->getCode()] = $total;
        foreach ($this->_totals as $code => $item) {
            $totals[$code] = $item;
        }
        $this->_totals = $totals;
        return $this;
    }

    /**
     * Get Total object by code
     *
     * @@return \Magento\Object
     */
    public function getTotal($code)
    {
        if (isset($this->_totals[$code])) {
            return $this->_totals[$code];
        }
        return false;
    }

    /**
     * Delete total by specific
     *
     * @param   string $code
     * @return  \Magento\Sales\Block\Order\Totals
     */
    public function removeTotal($code)
    {
        unset($this->_totals[$code]);
        return $this;
    }

    /**
     * Apply sort orders to totals array.
     * Array should have next structure
     * array(
     *  $totalCode => $totalSortOrder
     * )
     *
     *
     * @param   array $order
     * @return  \Magento\Sales\Block\Order\Totals
     */
    public function applySortOrder($order)
    {
        return $this;
    }

    /**
     * get totals array for visualization
     *
     * @return array
     */
    public function getTotals($area=null)
    {
        $totals = array();
        if ($area === null) {
            $totals = $this->_totals;
        } else {
            $area = (string)$area;
            foreach ($this->_totals as $total) {
                $totalArea = (string) $total->getArea();
                if ($totalArea == $area) {
                    $totals[] = $total;
                }
            }
        }
        return $totals;
    }

    /**
     * Format total value based on order currency
     *
     * @param   \Magento\Object $total
     * @return  string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->getOrder()->formatPrice($total->getValue());
        }
        return $total->getValue();
    }
}
