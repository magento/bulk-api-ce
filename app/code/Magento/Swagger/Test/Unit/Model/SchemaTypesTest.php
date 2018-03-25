<?php
/**
 * Created by PhpStorm.
 * User: carey
 * Date: 25/03/18
 * Time: 9:21 AM
 */

namespace Magento\Swagger\Test\Unit\Model;


use Magento\Swagger\Api\Data\SchemaTypeInterface;
use Magento\Swagger\Model\SchemaTypes;

class SchemaTypesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Magento\Swagger\Model\SchemaTypes::getTypes()
     */
    public function testGetTypes()
    {
        $schemaTypes = new SchemaTypes([
            'test' => $this->getMockBuilder(SchemaTypeInterface::class)
                ->getMockForAbstractClass()
        ]);

        $this->assertArrayHasKey('test', $schemaTypes->getTypes());
    }

    /**
     * @covers \Magento\Swagger\Model\SchemaTypes::getTypes()
     */
    public function testGetType()
    {
        $schemaTypes = new SchemaTypes([
            'test' => $this->getMockBuilder(SchemaTypeInterface::class)
                ->getMockForAbstractClass()
        ]);

        $this->assertNotNull($schemaTypes->getType('test'));
        $this->assertNull($schemaTypes->getType('invalid'));
    }
}
