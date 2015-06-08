<?php

namespace app\models\amazon;

use Aws\S3\S3Client;
use Yii;
use Aws\Sdk;
use yii\base\Model;

class AwsS3Component extends Model
{
    CONST ACL_PRIVATE = 'private';
    CONST ACL_PUBLIC_READ = 'public-read';
    CONST ACL_PUBLIC_READ_WRITE = 'public-read-write';
    CONST ACL_AUTHENTICATED_READ = 'authenticated-read';
    CONST ACL_BUCKET_OWNER_READ = 'bucket-owner-read';
    CONST ACL_BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';
    CONST ACL_LOG_DELIVERY_WRITE = 'log-delivery-write';

    public $source;
    public $bucket;
    public $key;
    public $acl;

    public function rules()
    {
        return [
            [['source', 'bucket', 'key'], 'required', 'on' => 'put'],
            [['bucket', 'key'], 'required', 'on' => 'get'],
            [['bucket', 'key'], 'required', 'on' => 'presignedUrl'],
            [['bucket', 'key'], 'required', 'on' => 'delete'],
            [['bucket', 'key'], 'required', 'on' => 'download']
        ];
    }

    public function uploadObject($source, $bucket, $key, $acl = self::ACL_PRIVATE)
    {
        $this->source = $source;
        $this->bucket = $bucket;
        $this->key = $key;
        $this->acl = $acl;

        $this->scenario = 'put';

        if ($this->validate()) {

            $sdk = new Sdk([
                'version' => 'latest',
                'region'  => Yii::$app->params['amazon']['region'],
                'credentials' => Yii::$app->params['amazon']['credentials']
            ]);

            $s3 = $sdk->createS3();

            $file = null;

            try {

                $file = fopen($this->source, 'r');

                $result = $s3->upload($this->bucket, $this->key, $file, $this->acl, ['Content-Type' => $this->getContentType()]);

                return $result->get('ObjectURL');

            } catch (\Exception $e) {

                Yii::error('Error uploading files to S3. ' . $e->getMessage());
            }

            if ($file != null) {

                fclose($file);
            }

            return false;

        } else {

            return false;
        }
    }

    public function deleteObject($bucket, $key)
    {
        $this->bucket = $bucket;
        $this->key = $key;

        $this->scenario = 'delete';

        if ($this->validate()) {

            try {

                $client = new S3Client([
                    'version' => 'latest',
                    'region'  => Yii::$app->params['amazon']['region'],
                    'credentials' => Yii::$app->params['amazon']['credentials']
                ]);

                $client->registerStreamWrapper();

                return unlink('s3://' . $bucket . '/' . $key);

            } catch (\Exception $e) {

                Yii::error('Error deleting object from S3. Bucket - ' . $this->bucket . ' Key - ' .$this->key . ' Extra - ' . $e->getMessage());

                return false;
            }
        }

        return false;
    }

    public function getPreSignedUrl($bucket, $key, $expiryInMinutes = 10)
    {
        $this->bucket = $bucket;
        $this->key = $key;

        $this->scenario = 'delete';

        if ($this->validate()) {

            try {

                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => Yii::$app->params['amazon']['region'],
                    'credentials' => Yii::$app->params['amazon']['credentials']
                ]);

                $cmd = $s3Client->getCommand('GetObject', [
                    'Bucket' => $this->bucket,
                    'Key' => $this->key
                ]);

                $request = $s3Client->createPresignedRequest($cmd, '+' . $expiryInMinutes . ' minutes');

                return (string)$request->getUri();

            } catch (\Exception $e) {

                Yii::error('Error getting pre-signed url from S3. Bucket - ' . $this->bucket . ' Key - ' .$this->key . ' Extra - ' . $e->getMessage());

                return false;
            }
        }

        return false;
    }

    public function downloadObject($bucket, $key)
    {
        $this->bucket = $bucket;
        $this->key = $key;

        $this->scenario = 'download';

        if ($this->validate()) {

            try {

                $client = new S3Client([
                    'version' => 'latest',
                    'region'  => Yii::$app->params['amazon']['region'],
                    'credentials' => Yii::$app->params['amazon']['credentials']
                ]);

                $client->registerStreamWrapper();

                return file_get_contents('s3://' . $bucket . '/' . $key);

            } catch (\Exception $e) {

                Yii::error('Error getting file content from S3. Bucket - ' . $this->bucket . ' Key - ' .$this->key . ' Extra - ' . $e->getMessage());

                return null;
            }
        }

        return null;
    }

    public function getContentType()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $this->source);
        finfo_close($finfo);

        return $contentType;
    }
}