<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

//namespace Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price;
namespace Magento\WebapiAsync\Model;

use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;

use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Framework\ObjectManagerInterface;
use Magento\Webapi\Controller\Rest\InputParamsResolver;

/**
 * Class Consumer
 */
class AsyncConsumer
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\AsynchronousOperations\Model\OperationManagement
     */
    private $operationManagement;


    /**
     * @var InputParamsResolver
     */
    protected $inputParamsResolver;

    /**
     * @var ServiceInputProcessor
     */
    private $serviceInputProcessor;

    /**
     * @var ServiceOutputProcessor
     */
    protected $serviceOutputProcessor;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;


    /**
     * Consumer constructor.
     *
     * @param \Psr\Log\LoggerInterface            $logger
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Bulk\OperationManagementInterface $operationManagement,
        InputParamsResolver $inputParamsResolver,
        ServiceInputProcessor $serviceInputProcessor,
        ServiceOutputProcessor $serviceOutputProcessor,
        ObjectManagerInterface $objectManager
    )
    {
        $this->logger              = $logger;
        $this->jsonHelper          = $jsonHelper;
        $this->operationManagement = $operationManagement;

        $this->inputParamsResolver    = $inputParamsResolver;
        $this->serviceInputProcessor  = $serviceInputProcessor;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->_objectManager         = $objectManager;
    }

    /**
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @return void
     */
    public function processOperation(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        $status           = OperationInterface::STATUS_TYPE_COMPLETE;
        $errorCode        = null;
        $message          = null;
        $serializedData   = $operation->getSerializedData();
        $unserializedData = $this->jsonHelper->jsonDecode($serializedData);
        try {
            /*
                        $message['meta_information'] = [
                            'entity' => $entity[0],
                            'class'  => $serviceClassName,
                            'method' => $serviceMethodName,
                        ];
            */
            $entityData    = $unserializedData->meta_information->entity;
            $serviceClass  = $unserializedData->meta_information->class;
            $serviceMethod = $unserializedData->meta_information->method;

            $result = $this->processMessage($serviceClass, $serviceMethod, $entityData);

        }
        catch(\Zend_Db_Adapter_Exception  $e) {
            //here sample how to process exceptions if they occured
            $this->logger->critical($e->getMessage());
            //you can add here your own type of exception when operation can be retried
            if(
                $e instanceof LockWaitException
                || $e instanceof DeadlockException
                || $e instanceof ConnectionException
            ) {
                $status    = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message   = __($e->getMessage());
            } else {
                $status    = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message   =
                    __('Sorry, something went wrong during product prices update. Please see log for details.');
            }

        }
        catch(\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status    =
                ($e instanceof TemporaryStateExceptionInterface) ? OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();

            $message = $e->getMessage();
            unset($unserializedData['entity_link']);
            $serializedData = $this->jsonHelper->jsonEncode($unserializedData);
        } catch(\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status    = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message   = $e->getMessage();
        }
        catch(\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status    = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message   = __('Sorry, something went wrong during product prices update. Please see log for details.');
        }

        //update operation status based on result performing operation(it was successfully executed or exception occurs
        $this->operationManagement->changeOperationStatus(
            $operation->getId(),
            $status,
            $errorCode,
            $message,
            $serializedData
        );
    }

    protected function processMessage($serviceClassName, $serviceMethodName, $requestData)
    {
        $inputParams = $this->serviceInputProcessor->process($serviceClassName, $serviceMethodName, $requestData);

        $service = $this->_objectManager->get($serviceClassName);
        /** @var \Magento\Framework\Api\AbstractExtensibleObject $outputData */
        $outputData = call_user_func_array([$service, $serviceMethodName], $inputParams);
        $outputData = $this->serviceOutputProcessor->process(
            $outputData,
            $serviceClassName,
            $serviceMethodName
        );

        return $outputData;
    }
}