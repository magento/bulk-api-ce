<?php

namespace Magento\WebapiAsync\Controller\Rest;

use Magento\Webapi\Model\Rest\Swagger\Generator;
use Magento\Framework\Webapi\Rest\Response as RestResponse;
use Magento\Framework\Webapi\Request;
use Magento\Webapi\Controller\Rest\RequestProcessorInterface;

class AsynchronousSchemaRequestProcessor implements RequestProcessorInterface
{
    /** Path for accessing REST API schema */
    const SCHEMA_PATH = '/async/schema';
    /**
     *  {@inheritdoc}
     */

    /**
     * @var \Magento\Webapi\Model\Rest\Swagger\Generator
     */
    protected $swaggerGenerator;


    /**
     * @var \Magento\Framework\Webapi\Rest\Response
     */
    protected $_response;

    public function __construct(
        Generator $swaggerGenerator,
        RestResponse $response
    )
    {
        $this->swaggerGenerator = $swaggerGenerator;
        $this->_response = $response;
    }

    public function canProcess(\Magento\Framework\Webapi\Rest\Request $request)
    {
       return $request->getPathInfo() === self::SCHEMA_PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function process(\Magento\Framework\Webapi\Rest\Request $request)
    {
        $requestedServices = $request->getRequestedServices('all');
        $requestedServices = $requestedServices == Request::ALL_SERVICES
            ? $this->swaggerGenerator->getListOfServices()
            : $requestedServices;
        $responseBody = $this->swaggerGenerator->generate(
            $requestedServices,
            $request->getScheme(),
            $request->getHttpHost(),
            $request->getRequestUri()
        );
        $this->_response->setBody($responseBody)->setHeader('Content-Type', 'application/json');
    }
}