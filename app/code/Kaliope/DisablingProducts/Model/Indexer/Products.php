<?php
namespace Kaliope\DisablingProducts\Model\Indexer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\DB\TransactionFactory;
use Psr\Log\LoggerInterface;

class Products implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    const MINIMUM_QUANTITY_AVAILABLE = 5;

    public function __construct(
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $productCollectionFactory,
        TransactionFactory $transactionFactory
    )
    {
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param int[] $ids
     * @throws \Exception
     */
    public function execute($ids){
        $this->actionDisabling($ids);
    }

    public function executeFull(){
        $collection = $this->getProductCollection();
        $this->actionDisabling($collection->getAllIds());
    }

    /**
     * @param array $ids
     * @throws \Exception
     */
    public function executeList(array $ids){
        $this->actionDisabling($ids);
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function executeRow($id){
        $this->actionDisabling([$id]);
    }

    /**
     * @param array $ids
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function actionDisabling(array $ids) {
        $transaction = $this->transactionFactory->create();

        foreach ($ids as $id) {
            $product = $this->productRepository->getById($id);
            $countImage = $product->getMediaGalleryImages()->getSize();
            $description = str_replace(' ', '', $product->getData('description'));
            $quantityAndStockStatus = $product->getQuantityAndStockStatus();
            $quantity = $quantityAndStockStatus['qty'];


            if ($countImage == 0 || is_null($description) || (int)$quantity <= self::MINIMUM_QUANTITY_AVAILABLE) {
                $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                    $transaction->addObject($product);
                $this->logger->debug('productdisabled , id:'.$id);
            }
        }

        try{
            $transaction->save();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addWebsiteFilter();
        $collection->addStoreFilter();
        $collection->joinField(
            'qty', 'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
        );
        $collection->joinField(
            'parent_id', 'catalog_product_super_link', 'parent_id', 'product_id=entity_id', null, 'left'
        );
        $collection->addAttributeToFilter([
            ['attribute' => 'qty', 'lt'=>self::MINIMUM_QUANTITY_AVAILABLE],
            ['attribute' => 'description', 'null' => true],
            ['attribute' => 'image', 'eq' => 'no_selection']
        ]);

        $collection->addAttributeToFilter('type_id', ['eq' => 'simple']);
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        return $collection;
    }
}