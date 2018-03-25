<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Magento\Swagger\Test\Unit\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Swagger\Api\SchemaTypesInterface;
use Magento\Swagger\Controller\Index\Index;
use Magento\Swagger\Controller\Router;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var ActionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $actionFactoryMock;

    /**
     * @var ActionList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $actionListMock;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $routeConfigMock;

    /**
     * @var SchemaTypesInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $schemaTypesMock;

    protected function setUp()
    {
        $this->actionFactoryMock = $this->getMockBuilder(ActionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->actionListMock = $this->getMockBuilder(ActionList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->getMockForAbstractClass();

        $this->schemaTypesMock = $this->getMockBuilder(SchemaTypesInterface::class)
            ->getMockForAbstractClass();

        $this->router = new Router(
            $this->actionFactoryMock,
            $this->actionListMock,
            $this->routeConfigMock,
            $this->schemaTypesMock
        );
    }

    public function testMatchLegacyMissingModule()
    {
        /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPathInfo'])
            ->getMockForAbstractClass();

        $requestMock->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/swagger');

        $this->routeConfigMock->expects($this->once())
            ->method('getModulesByFrontName')
            ->with('swagger')
            ->willReturn([]);

        $this->assertNull($this->router->match($requestMock));
    }

    public function testMatchLegacyWithModule()
    {
        /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPathInfo'])
            ->getMockForAbstractClass();

        $requestMock->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('swagger');

        $this->routeConfigMock->expects($this->once())
            ->method('getModulesByFrontName')
            ->with('swagger')
            ->willReturn(['swagger']);

        $this->actionListMock->expects($this->once())
            ->method('get')
            ->with('swagger', null, 'index', 'index')
            ->willReturn(Index::class);

        $this->actionFactoryMock->expects($this->once())
            ->method('create')
            ->with(Index::class)
            ->willReturn($this->getMockBuilder(ActionInterface::class)->getMockForAbstractClass());

        $this->assertNotNull($this->router->match($requestMock));
    }

    /**
     * @param $path
     * @param array $expectedRequestParameters
     * @dataProvider matchWithPathParametersProvider
     */
    public function testMatchWithPathParameters($path, array $expectedRequestParameters)
    {
        /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPathInfo'])
            ->getMockForAbstractClass();

        $requestMock->expects($this->once())
            ->method('getPathInfo')
            ->willReturn($path);

        $requestMock->expects($this->once())
            ->method('setParams')
            ->with($expectedRequestParameters);

        $this->routeConfigMock->expects($this->once())
            ->method('getModulesByFrontName')
            ->with('swagger')
            ->willReturn(['swagger']);

        $this->actionListMock->expects($this->once())
            ->method('get')
            ->with('swagger', null, 'index', 'index')
            ->willReturn(Index::class);

        $this->actionFactoryMock->expects($this->once())
            ->method('create')
            ->with(Index::class)
            ->willReturn($this->getMockBuilder(ActionInterface::class)->getMockForAbstractClass());

        $this->assertNotNull($this->router->match($requestMock));
    }

    public function matchWithPathParametersProvider()
    {
        return [
            [
                'swagger',
                []
            ],
            [
                'swagger/new',
                [
                    'type' => 'new'
                ]
            ],
            [
                'swagger/new/store',
                [
                    'type' => 'new',
                    'store' => 'store',
                ]
            ]
        ];
    }
}