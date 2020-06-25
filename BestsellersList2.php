<?php

namespace Smartwave\Filterproducts\Block\Home;

use Magento\Catalog\Api\CategoryRepositoryInterface;

class BestsellersList2 extends \Magento\Catalog\Block\Product\ListProduct {

    protected $_collection;
    protected $_collectionFactory;

    protected $categoryRepository;

    protected $_resource;

    public function __construct(
            \Magento\Catalog\Block\Product\Context $context,
            \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
            \Magento\Catalog\Model\Layer\Resolver $layerResolver,
            CategoryRepositoryInterface $categoryRepository,
            \Magento\Framework\Url\Helper\Data $urlHelper,
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
            \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
            \Magento\Framework\App\ResourceConnection $resource,
            array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->_collection = $collection;
        $this->_collectionFactory = $collectionFactory;
        $this->_resource = $resource;

        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    protected function _getProductCollection() {
        return $this->getProducts();
    }

    public function getProducts() {
        $count = $this->getProductCount();

        $from = $this->getData("from");
        $to = $this->getData("to");

        // $collection = clone $this->_collection;
        // $collection->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE)->reset(\Magento\Framework\DB\Select::ORDER)->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)->reset(\Magento\Framework\DB\Select::GROUP)->reset(\Magento\Framework\DB\Select::COLUMNS)->reset('from');
        $collection = $this->_collectionFactory->create();
        $connection  = $this->_resource->getConnection();
        // $collection->getSelect()->join(['e' => $connection->getTableName('catalog_product_entity')],'');

        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('small_image')
            ->addAttributeToSelect('thumbnail')
            ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addUrlRewrite();

        $where = !empty($from) ? "and order.period>='$from'" : "";
        $where .= !empty($to) ? "and order.period<='$to'" : "";

        $collection->getSelect()
            ->join(['order' => $connection->getTableName('sales_bestsellers_aggregated_daily')], "order.product_id = e.entity_id", ['SUM(order.qty_ordered) AS ordered_qty'])
            ->where("1 $where")
            ->group('order.product_id')
            ->order('ordered_qty DESC')
            ->limit($count);

        return $collection;
    }

    public function getLoadedProductCollection() {
        return $this->getProducts();
    }

    public function getProductCount() {
        $limit = $this->getData("product_count");
        if(!$limit)
            $limit = 10;
        return $limit;
    }
}
