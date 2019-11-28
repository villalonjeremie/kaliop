<?php
namespace Kaliope\DisablingProducts\Model\Indexer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Setup\Exception;


class Products implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    protected $logger;
    protected $productRepository;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }


    public function execute($ids){
        $this->actionDisabling($ids);
    }

    public function executeFull(){
        $this->actionDisabling([1]);
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
    }
}