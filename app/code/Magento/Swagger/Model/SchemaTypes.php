<?php
/**
 * Created by PhpStorm.
 * User: carey
 * Date: 25/03/18
 * Time: 9:19 AM
 */

namespace Magento\Swagger\Model;

use Magento\Swagger\Api\SchemaTypesInterface;

class SchemaTypes implements SchemaTypesInterface
{
    /**
     * @var array
     */
    private $types;

    /**
     * SchemaTypes constructor.
     * @param array $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @return \Magento\Swagger\Api\Data\SchemaTypeInterface[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param string $code
     * @return \Magento\Swagger\Api\Data\SchemaTypeInterface|null
     */
    public function getType($code)
    {
        return $this->types[$code] ?? null;
    }
}