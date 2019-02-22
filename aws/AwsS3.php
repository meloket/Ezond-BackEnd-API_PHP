<?php
require_once (__DIR__ . '/../config.php');
require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../apis/img_library.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class AwsS3
{
    protected $client;
    protected $bucket;
    protected $region;
    public $path;

    public function __construct()
    {
        $this->region = S3_REGION;
        $this->bucket = S3_BUCKET;
        $this->path = 's3://' . $this->bucket;

        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => $this->region
        ]);
    }

    public function sendResizeImage($path, $fileName, $defaultFileName, $width, $height)
    {
        $this->client->registerStreamWrapper();
        $fullPath = $this->path . $path;

        $file = file_exists($fullPath . $fileName) ? $fullPath . $fileName : $fullPath . $defaultFileName;
        $image = file_get_contents($file);
        smart_resize_image(null, $image, $width, $height, false, 'browser');
    }

    public function isExistFile($file)
    {
        try {
            $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $file
            ]);

            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    public function uploadFile($target, $source)
    {
        try{
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $target,
                'SourceFile' => $source,
            ]);
        } catch (S3Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function removeFile($file)
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => $file,
        ]);
    }

    public function renameFile($newName, $oldName)
    {
        try{
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $newName,
                'CopySource' => $this->bucket . '/' . $oldName,
            ]);

            $this->removeFile($oldName);
        } catch (S3Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function getFile($file)
    {
        try{
            return $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $file
            ]);
        } catch (S3Exception $e) {
            error_log($e->getMessage());

            return null;
        }
    }

    public function createFileByContent($path, $content)
    {
        try {
            $result = $this->client->putObject([
                'Bucket'    => $this->bucket,
                'Key'       => $path,
                'Body'      => $content,
            ]);

            return $result;
        } catch (S3Exception $e) {
            error_log($e->getMessage());

            return false;
        }
    }

}