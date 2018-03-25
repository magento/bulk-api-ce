<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Swagger\Block;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;
use Magento\Swagger\Api\Data\SchemaTypeInterface;
use Magento\Swagger\Api\SchemaTypesInterface;

/**
 * Block for swagger index page
 *
 * @api
 */
class Index extends Template
{
    /**
     * @var SchemaTypesInterface
     */
    private $schemaTypes;

    /**
     * Index constructor.
     *
     * @param Template\Context $context
     * @param SchemaTypesInterface $schemaTypes
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SchemaTypesInterface $schemaTypes,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->schemaTypes = $schemaTypes;
    }

    /**
     * @return mixed|string
     */
    private function getParamStore()
    {
        return $this->getRequest()->getParam('store') ?: 'all';
    }

    /**
     * @return bool
     */
    private function hasSchemaTypes()
    {
        return count($this->schemaTypes->getTypes()) > 0;
    }

    /**
     * @return SchemaTypeInterface|null
     */
    private function getSchemaType()
    {
        if (!$this->hasSchemaTypes()) {
            return null;
        }

        $schemaTypes = $this->schemaTypes->getTypes();
        $defaultType = array_shift($schemaTypes);
        $schemaTypeCode = $this->getRequest()->getParam(
            'type',
            $defaultType->getCode()
        );

        return $this->schemaTypes->getType($schemaTypeCode);
    }

    /**
     * @return string|null
     */
    public function getSchemaUrl()
    {
        if ($this->getSchemaType() === null) {
            return null;
        }

        return rtrim($this->getBaseUrl(), '/') .
            $this->getSchemaType()->getSchemaUrlPath($this->getParamStore());
    }
}
