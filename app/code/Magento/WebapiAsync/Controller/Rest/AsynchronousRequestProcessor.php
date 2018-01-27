<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 16.01.18
 * Time: 20:54
 */

namespace Magento\WebapiAsync\Controller\Rest;

use Magento\Webapi\Controller\Rest\RequestProcessorInterface;
use Magento\Framework\Webapi\Rest\Response as RestResponse;

class AsynchronousRequestProcessor implements RequestProcessorInterface
{
    const ASYNC_PATH = "/async/V1/";

    protected $_response;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $_publisher;

    /**
     * @var Async\InputParamsResolver
     */
    private $inputParamsResolver;

    /**
     * @var \Magento\Framework\Bulk\OperationInterface
     */
    protected $_operation;

    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface
     */
    protected $_bulkManagement;

    /**
     * @var \Magento\WebapiAsync\Model\AsyncBulkPublisher
     */
    protected $_asyncBulkPublisher;

    /**
     * @var \Magento\WebapiAsync\Model\AsyncConsumer
     */
    protected $_asyncConsumer;

    public function __construct(
        RestResponse $response,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        //\Magento\Framework\Bulk\OperationInterfaceFactory $operation,
        \Magento\AsynchronousOperations\Model\OperationFactory $operation,
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\WebapiAsync\Model\AsyncBulkPublisher $asyncBulkPublisher,
        \Magento\WebapiAsync\Model\AsyncConsumer $asyncConsumer
    )
    {
        $this->_response           = $response;
        $this->_publisher          = $publisher;
        $this->_operation          = $operation;
        $this->_bulkManagement     = $bulkManagement;
        $this->_asyncBulkPublisher = $asyncBulkPublisher;
        $this->_asyncConsumer      = $asyncConsumer;
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(\Magento\Framework\Webapi\Rest\Request $request)
    {
        return strpos($request->getPathInfo(), self::ASYNC_PATH) === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function process(\Magento\Framework\Webapi\Rest\Request $request)
    {
        $inputParams = $this->getInputParamsResolver()->resolve();
        $res         = [];
        $operations  = [];
        try {
            $res = $this->_asyncBulkPublisher->scheduleAsyncBulk($inputParams);
        } catch(\Exception $be) {
            $res = ['error' => $be->getMessage()];
        }
        /*
        foreach($inputParams as $key => $messageParams){
            try {
                continue;

                $res[$key] = $this->_publisher->publish('catalog.product.added', $entityToPublish[0]);
            } catch (\Exception $e){
                $res[$key] = ['error'=>$e->getMessage()];
            }
        }
        */
        $this->_response->setStatusCode(202)->setContent(json_encode(['result'=>$res,'finish'=>true]));
    }

    /**
     * The getter function to get InputParamsResolver object
     *
     * @return \Magento\WebapiAsync\Controller\Rest\Async\InputParamsResolver
     *
     * @deprecated 100.1.0
     */
    private function getInputParamsResolver()
    {
        if($this->inputParamsResolver === null) {
            $this->inputParamsResolver = \Magento\Framework\App\ObjectManager::getInstance()
                                                                             ->get(\Magento\WebapiAsync\Controller\Rest\Async\InputParamsResolver::class);
        }

        return $this->inputParamsResolver;
    }

}