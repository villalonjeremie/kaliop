<?php
namespace Kaliope\DisablingProducts\Model\Indexer;

use Magento\Catalog\Api\ProductRepositoryInterface;


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
        echo 'execute';
        $this->logger->debug($ids);
    }

    public function executeFull(){
        echo 'executeFull';
        $product = $this->productRepository->getById(1);


        $countImage = $product->getMediaGalleryImages()->getSize();
        $description = str_replace(' ', '', $product->getData('description'));
        $quantity = $product->getQuantityAndStockStatus()['qty'];

        if ($countImage == 0 || is_null($description) || $description=='' || (int)$quantity <= 5) {

            $this->logger->debug(var_dump('productdisabled'));

        }
    }

    public function executeList(array $ids){
        echo 'executeList';
        $this->logger->debug($ids);

    }


    public function executeRow($id){
        echo 'executeRow';
        $this->logger->debug('executeRow');

        $this->logger->debug($id);


    }
}