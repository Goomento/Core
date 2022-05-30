<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Plugin\Framework\Controller\ResultInterface;

use Exception;
use Goomento\Core\Model\HtmlModifier;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;

class ModifyResponse
{
    /**
     * @var HtmlModifier
     */
    private $htmlModifier;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param HtmlModifier $htmlModifier
     * @param LoggerInterface $logger
     */
    public function __construct(
        HtmlModifier $htmlModifier,
        LoggerInterface $logger
    )
    {
        $this->htmlModifier = $htmlModifier;
        $this->logger = $logger;
    }

    /**
     * Modify the HTML output in order to make it faster to Page Builder.
     *
     * @param ResultInterface $subject
     * @param mixed $result
     * @param ResponseHttp $response
     * @return mixed
     */
    public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseInterface $response)
    {
        try {
            $body = $response->getBody();
            if (!empty($body)) {
                $html = $this->htmlModifier->modify($body);
                if ($body !== $html) {
                    $response->setBody($html);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e);
        }

        return $result;
    }
}
