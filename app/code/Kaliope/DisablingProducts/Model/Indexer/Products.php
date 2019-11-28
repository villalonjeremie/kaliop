<?php
namespace Kaliope\DisablingProducts\Model\Indexer;

class Products implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    public function execute($ids){
        echo 'execute';
    }

    public function executeFull(){
        echo 'executeFull';

    }

    public function executeList(array $ids){
        echo 'executeList';
    }


    public function executeRow($id){
        echo 'executeRow';

    }
}