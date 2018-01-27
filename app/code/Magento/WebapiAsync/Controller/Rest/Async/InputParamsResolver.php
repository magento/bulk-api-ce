<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\WebapiAsync\Controller\Rest\Async;

use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\WebapiAsync\Controller\Rest\Async\Router;
use Magento\WebapiAsync\Controller\Rest\Async\Router\Route;

/**
 * This class is responsible for retrieving resolved input data
 */
class InputParamsResolver
{
    /**
     * @var RestRequest
     */
    private $request;

    /**
     * @var ParamsOverrider
     */
    private $paramsOverrider;

    /**
     * @var ServiceInputProcessor
     */
    private $serviceInputProcessor;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * Initialize dependencies
     *
     * @param RestRequest           $request
     * @param ParamsOverrider       $paramsOverrider
     * @param ServiceInputProcessor $serviceInputProcessor
     * @param Router                $router
     * @param RequestValidator      $requestValidator
     */
    public function __construct(
        RestRequest $request,
        \Magento\Webapi\Controller\Rest\ParamsOverrider $paramsOverrider,
        ServiceInputProcessor $serviceInputProcessor,
        Router $router,
        \Magento\WebapiAsync\Controller\Rest\Async\RequestValidator $requestValidator
    )
    {
        $this->request               = $request;
        $this->paramsOverrider       = $paramsOverrider;
        $this->serviceInputProcessor = $serviceInputProcessor;
        $this->router                = $router;
        $this->requestValidator      = $requestValidator;
    }

    /**
     * Process and resolve input parameters
     *
     * @return array
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function resolve()
    {
        $this->requestValidator->validate();
        $route             = $this->getRoute();
        $serviceMethodName = $route->getServiceMethod();
        $serviceClassName  = $route->getServiceClass();

        /*
         * Valid only for updates using PUT when passing id value both in URL and body
         */
        if($this->request->getHttpMethod() == RestRequest::HTTP_METHOD_PUT) {
            $inputData = $this->paramsOverrider->overrideRequestBodyIdWithPathParam(
                $this->request->getParams(),
                $this->request->getBodyParams(),
                $serviceClassName,
                $serviceMethodName
            );
            $inputData = array_merge($inputData, $this->request->getParams());
        } else {
            $inputData = $this->request->getRequestData();
        }

        $inputData = $this->paramsOverrider->override($inputData, $route->getParameters());

        $message = [];
        $result  = [];
        /*
         * Simple check if we get array of item instead of one item
         * Will work because no one magento object doesn't have integer value key
         */
        $i = 1;
        if(isset($inputData[0])) {
            foreach($inputData as $item) {
                try {
                    //$entity  = $this->serviceInputProcessor->process($serviceClassName, $serviceMethodName, $item);

                    $message['meta_information'] = [
                        'entity' => $item,
                        'class'  => $serviceClassName,
                        'method' => $serviceMethodName,
                    ];

                    $message['entity_id']        = $i;
                    $result[]                    = $message;
                } catch(\Exception $e) {
                    $result[] = ['error' => true, 'message' => $e->getMessage()];
                }
                $i++;
            }
        } else {
            //$entity = $this->serviceInputProcessor->process($serviceClassName, $serviceMethodName, $inputData);
            $message['meta_information'] = [
                'entity' => $inputData,
                'class'  => $serviceClassName,
                'method' => $serviceMethodName,
            ];

            $message['entity_id']        = 1;
            $result[]                    = $message;
        }

        return $result;
    }

    /**
     * Retrieve current route.
     *
     * @return Route
     */
    public function getRoute()
    {
        if(!$this->route) {
            $this->route = $this->router->match($this->request);
        }

        return $this->route;
    }
}
