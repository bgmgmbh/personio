<?php

namespace Pkd\Personio\Service;

use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Personio Service
 */
class PersonioService extends ActionController
{
    use LoggerAwareTrait;

  /** @var RequestFactoryInterface */
  private $requestFactory;

  /**
   * Personio Service Constructor
   */
  public function __construct()
  {
     $this->requestFactory = GeneralUtility::makeInstance(RequestFactoryInterface::class);
     $this->setLogger(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__));
  }

  /**
   * Fetch Feed Items
   *
   * @return array
   */
  public function fetchFeedItems($feedUrl) {
    $additionalOptions = [
       'headers' => ['Cache-Control' => 'no-cache'],
       'allow_redirects' => false
    ];

    $response = $this->requestFactory->request($feedUrl, 'GET', $additionalOptions);

    $this->logger->info('Response Status Code ' . $response->getStatusCode());
    $this->logger->info('Response Content Type ' . $response->getHeaderLine('Content-Type'));

    if ($response->getStatusCode() === 200
    && strpos($response->getHeaderLine('Content-Type'), 'text/xml') === 0) {
      $content = $response->getBody()->getContents();

        $items = json_decode(
            json_encode(
                simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA)
            ),TRUE
        )['position'];

        $this->logger->info('Fetched XML ' . $content);

        // if there is only one item, it is an flat array
        // one item: ['id' => '1', 'name' => 'my job']
        // more items: [0 => ['id' => '1', 'name' => 'my job'], 1 => ['id' => '2', 'name' => 'my other job']]
        if(isset($items['id'])){
            $items = [$items];
        }

        $this->logger->info('Returned Items ' . json_encode($items));

        return $items;
    }

    return [];
  }
}
