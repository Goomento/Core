<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Traits;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\UrlInterface;

/**
 * Trait TraitAjaxAction
 * @package Goomento\Core\Traits
 * @property  $responseDataStatusCode = 200
 * @property  $responseDataData = []
 */
trait TraitHttpAction
{
    use TraitStaticInstances;

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        if (!isset($this->_request)) {
            $this->_request = self::getInstance(
                RequestInterface::class
            );
        }

        return $this->_request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        if (!isset($this->_response)) {
            $this->_response = self::getInstance(
                ResponseInterface::class
            );
        }

        return $this->_response;
    }

    /**
     * @return array
     */
    protected function getResponseData()
    {
        if (!isset($this->responseDataStatusCode)) {
            $this->responseDataStatusCode = 200;
        }
        if (!isset($this->responseDataData)) {
            $this->responseDataData = [];
        }

        return [
            'status_code' => $this->responseDataStatusCode,
            'data' => $this->responseDataData,
        ];
    }

    /**
     * @param $data
     * @return TraitHttpAction
     */
    protected function setResponseData($data)
    {
        $this->getResponseData();
        if (isset($data['status_code']) || isset($data['data'])) {
            if (isset($data['status_code'])) {
                $this->responseDataStatusCode = $data['status_code'];
            }
            if (isset($data['data'])) {
                $this->responseDataData = $data['data'];
            }
        } else {
            $this->responseDataData = $data;
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function sendResponse404()
    {
        return $this->setResponseData('')
            ->setResponseDataStatusCode(404)
            ->sendResponse();
    }

    /**
     * @return mixed
     */
    protected function sendResponse()
    {
        $data = $this->getResponseData();
        return $this->getResponse()->representJson(
            json_encode($data['data'])
        )->setStatusHeader($data['status_code']);
    }

    /**
     * @param $code
     * @return $this
     */
    protected function setResponseDataStatusCode($code)
    {
        $data = $this->getResponseData();
        $data['status_code'] = $code;
        return $this->setResponseData($data);
    }


    /**
     * @param $dataResponse
     * @return $this
     */
    protected function setResponseDataData($dataResponse)
    {
        $data = $this->getResponseData();
        $data['data'] = $dataResponse;
        return $this->setResponseData($data);
    }

    /**
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        if (!isset($this->_url)) {
            $this->_url = self::getInstance(
                UrlInterface::class
            );
        }

        return $this->_url;
    }

    /**
     * @return mixed
     */
    protected function redirectToPage($route)
    {
        return $this->getResponse()->setRedirect(
            $this->getUrlBuilder()->getUrl($route)
        );
    }

    /**
     * @return mixed
     */
    protected function redirect404Page()
    {
        return $this->redirectToPage('noroute');
    }
}
