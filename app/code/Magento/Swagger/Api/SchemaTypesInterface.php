<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Swagger\Api;

interface SchemaTypesInterface
{
    /**
     * @return \Magento\Swagger\Api\Data\SchemaTypeInterface[]
     */
    public function getTypes();

    /**
     * @param string $code
     * @return \Magento\Swagger\Api\Data\SchemaTypeInterface|null
     */
    public function getType($code);
}
