<?php
namespace Kaliope\DisablingProducts\Model\Indexer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Setup\Exception;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Products implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    protected $logger;
    protected $productRepository;
    protected $productCollectionFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $productCollectionFactory
    )
    {
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
    }


    public function execute($ids){
        $this->actionDisabling($ids);
    }

    public function executeFull(){

        $collection = $this->getProductCollection();
        $this->actionDisabling($collection->getAllIds());
    }

    public function executeList(array $ids){
        $this->actionDisabling($ids);
    }


    public function executeRow($id){
        $this->actionDisabling([$id]);
    }

    protected function actionDisabling(array $ids) {
        foreach ($ids as $id) {
            $product = $this->productRepository->getById($id);
            $countImage = $product->getMediaGalleryImages()->getSize();
            $description = str_replace(' ', '', $product->getData('description'));
            $quantity = $product->getQuantityAndStockStatus()['qty'];
            $this->logger->debug(var_dump($product->getStatus()));

            if ($countImage == 0 || is_null($description) || $description=='' || (int)$quantity <= 5) {
                $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                try{
                    $this->productRepository->save($product);
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());

                }
                $this->logger->debug('productdisabled , id:'.$id);
            }
        }
        //reindexall for update flat tables and index tables.
    }

    protected function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addWebsiteFilter();
        $collection->addStoreFilter();
        $collection->joinField(
            'qty', 'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
        );
        $collection->addAttributeToFilter('qty',['lt'=>5]);
        //filter with OR condition for description and media
        return $collection;
    }
}