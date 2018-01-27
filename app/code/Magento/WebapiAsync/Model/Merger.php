<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebapiAsync\Model;

use Magento\Framework\MessageQueue\MergerInterface;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Model\Counter\ItemsBuilder;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;

/**
 * Merges messages from the operations queue.
 */
class Merger implements MergerInterface
{
    /**
     * @var \Magento\ScalableInventory\Model\Counter\ItemsBuilder
     */
    private $itemsBuilder;

    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory
     */
    private $mergedMessageFactory;

    /**
     * @param ItemsBuilder $itemsBuilder
     * @param MergedMessageInterfaceFactory $mergedMessageFactory [optional]
     */
    public function __construct(
        ItemsBuilder $itemsBuilder,
        MergedMessageInterfaceFactory $mergedMessageFactory = null
    ) {
        $this->itemsBuilder = $itemsBuilder;
        $this->mergedMessageFactory = $mergedMessageFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(MergedMessageInterfaceFactory::class);
    }

    public function merge(array $messageList)
    {
        $result = [];

        foreach ($messageList as $topicName => $topicMessages) {
            foreach ($topicMessages as $messageId => $message) {
                $mergedMessage = $this->mergedMessageFactory->create(
                    [
                        'mergedMessage' => $message,
                        'originalMessagesIds' => [$messageId]
                    ]
                );
                $result[$topicName] = [$mergedMessage];
            }
        }

        return $result;
    }
}
