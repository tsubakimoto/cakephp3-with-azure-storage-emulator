<?php

namespace App\Controller;

use App\Controller\AppController;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

/**
 * Home Controller
 *
 */
class HomeController extends AppController
{
    public function index()
    {
        $connectionString = 'UseDevelopmentStorage=true';
        $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);

        try {
            // List blobs.
            $blob_list = $blobRestProxy->listBlobs("test");
            $blobs = $blob_list->getBlobs();

            foreach ($blobs as $blob) {
                $text = $blob->getName() . ": " . $blob->getUrl();
                pr($text);
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
}
