<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\WebapiAsync\Model;

use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\UrlInterface;

/**
 * Class AsyncBulkPublisher
 */
class AsyncBulkPublisher
{
    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * ScheduleBulk constructor.
     *
     * @param BulkManagementInterface    $bulkManagement
     * @param OperationInterfaceFactory  $operartionFactory
     * @param IdentityGeneratorInterface $identityService
     * @param UserContextInterface       $userContextInterface
     * @param UrlInterface               $urlBuilder
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operartionFactory,
        IdentityGeneratorInterface $identityService,
        UserContextInterface $userContextInterface,
        UrlInterface $urlBuilder,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    )
    {
        $this->userContext      = $userContextInterface;
        $this->bulkManagement   = $bulkManagement;
        $this->operationFactory = $operartionFactory;
        $this->identityService  = $identityService;
        $this->urlBuilder       = $urlBuilder;
        $this->jsonHelper       = $jsonHelper;

    }

    /**
     * Schedule new bulk operation
     *
     * @param array $operationData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function scheduleAsyncBulk($operationData)
    {
        $operationCount = count($operationData);
        $bulkUuid       = null;
        if($operationCount > 0) {
            $bulkUuid        = $this->identityService->generateId();
            $bulkDescription = 'Bulk description';

            $operations = [];
            foreach($operationData as $operation) {


                $serializedData = [
                    //this data will be displayed in Failed item grid in ID column
                    'entity_id'        => $operation['entity_id'],
                    //add here logic to add url for your entity(this link will be displayed in the Failed item grid)
                    'entity_link'      => '',
                    //this data will be displayed in Failed item grid in the column "Meta Info"
                    'meta_information' => $operation['meta_information'],
                ];
                $data           = [
                    'data' => [
                        'bulk_uuid'       => $bulkUuid,
                        //topic name must be equal to data specified in the queue configuration files
                        'topic_name'      => 'async.operation.add',
                        'serialized_data' => $this->jsonHelper->jsonEncode($serializedData),
                        'status'          => OperationInterface::STATUS_TYPE_OPEN,
                    ],
                ];

                /** @var OperationInterface $operation */
                $operation    = $this->operationFactory->create($data);
                $operations[] = $operation;

            }
            $userId = $this->userContext->getUserId();
            $result = $this->bulkManagement->scheduleBulk($bulkUuid, $operations, $bulkDescription, $userId);
            if(!$result) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }

        }

        return $bulkUuid;
    }
}