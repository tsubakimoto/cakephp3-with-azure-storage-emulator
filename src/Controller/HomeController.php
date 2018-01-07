<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use MicrosoftAzure\Storage\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Common\SharedAccessSignatureHelper;

/**
 * Home Controller
 *
 */
class HomeController extends AppController
{
    private $connectionString = 'UseDevelopmentStorage=true';
    private $container = 'test';

    public function index()
    {
        $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($this->connectionString);

        try {
            // List blobs.
            $blob_list = $blobRestProxy->listBlobs($this->container);
            $blobs = $blob_list->getBlobs();

            foreach ($blobs as $blob) {
                pr($blob->getName() . ": " . $blob->getUrl());
                $sas = $this->generateBlobDownloadLinkWithSAS($blob->getName());
                pr($sas);
            }
            die;
        } catch (ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
        }
    }

    private function generateBlobDownloadLinkWithSAS($blob)
    {
        $settings = StorageServiceSettings::createFromConnectionString($this->connectionString);
        $accountName = $settings->getName();
        $accountKey = $settings->getKey();
        $helper = new SharedAccessSignatureHelper(
            $accountName,
            $accountKey
        );
        // Refer to following link for full candidate values to construct a service level SAS
        // https://docs.microsoft.com/en-us/rest/api/storageservices/constructing-a-service-sas
        $sas = $helper->generateBlobServiceSharedAccessSignatureToken(
            Resources::RESOURCE_TYPE_BLOB,
            $this->container . '/' . $blob,
            'r',                            // Read
            '2019-01-01T08:30:00Z'//,       // A valid ISO 8601 format expiry time
            //'2016-01-01T08:30:00Z',       // A valid ISO 8601 format expiry time
            //'0.0.0.0-255.255.255.255'
            //'https,http'
        );
        if (Configure::read('debug')) {
            $connectionStringWithSAS = Resources::BLOB_ENDPOINT_NAME .
                '=' .
                'http://127.0.0.1:10000/' .
                Resources::DEV_STORE_NAME .
                ';' .
                Resources::SAS_TOKEN_NAME .
                '=' .
                $sas;
        } else {
            $connectionStringWithSAS = Resources::BLOB_ENDPOINT_NAME .
                '=' .
                'https://' .
                $accountName .
                '.' .
                Resources::BLOB_BASE_DNS_NAME .
                ';' .
                Resources::SAS_TOKEN_NAME .
                '=' .
                $sas;
        }
        $blobClientWithSAS = ServicesBuilder::getInstance()->createBlobService(
            $connectionStringWithSAS
        );
        // We can download the blob with PHP Client Library
        // downloadBlobSample($blobClientWithSAS);
        // Or generate a temporary readonly download URL link
        $blobUrlWithSAS = sprintf(
            '%s%s?%s',
            (string)$blobClientWithSAS->getPsrPrimaryUri(),
            $this->container . '/' . $blob,
            $sas
        );
        //file_put_contents("outputBySAS.txt", fopen($blobUrlWithSAS, 'r'));
        return $blobUrlWithSAS;
    }
}
