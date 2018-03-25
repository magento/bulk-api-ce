<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Swagger\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;
use Magento\Swagger\Api\SchemaTypesInterface;

class Router implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ActionList
     */
    private $actionList;

    /**
     * @var ConfigInterface
     */
    private $routeConfig;

    /**
     * @var array
     */
    private $pathParameters = [
        1 => 'type',
        2 => 'store',
    ];
    /**
     * @var SchemaTypesInterface
     */
    private $schemaTypes;

    /**
     * @param ActionFactory $actionFactory
     * @param ActionList $actionList
     * @param ConfigInterface $routeConfig
     * @param SchemaTypesInterface $schemaTypes
     */
    public function __construct(
        ActionFactory $actionFactory,
        ActionList $actionList,
        ConfigInterface $routeConfig,
        SchemaTypesInterface $schemaTypes
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionList = $actionList;
        $this->routeConfig = $routeConfig;
        $this->schemaTypes = $schemaTypes;
    }

    /**
     * Allows a user to specify a specific type of Swagger schema to generate.
     *
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request)
    {
        $parts = explode('/', trim($request->getPathInfo(), '/'));

        $module = $parts[0] ?? '';
        if ($module !== 'swagger') {
            return null;
        }

        $modules = $this->routeConfig->getModulesByFrontName('swagger');
        if (empty($modules)) {
            return null;
        }

        $convertedParameters = [];
        foreach ($this->pathParameters as $position => $parameter) {
            if (!array_key_exists($position, $parts)) {
                continue;
            }

            $convertedParameters[$parameter] = $parts[$position];
        }

        // Only accept valid schema types
        if ($request->getParam('type') !== null
            && $this->schemaTypes->getType($request->getParam('type')) === null
        ) {
            return null;
        }

        $request->setParams($convertedParameters);

        $request->setModuleName('swagger')->setControllerName('index')->setActionName('index');

        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }
}
