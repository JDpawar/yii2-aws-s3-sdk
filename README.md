# Yii2 AWS S3

The code provides a layer above the Amazon S3 SDK to be used in Yii2 app.
Following thing need to be done before using the code

1. Install the S3 SDK using composer. The file looks like follow

    ```
    "require": {
      ...
      "aws/aws-sdk-php": "~3.0@dev"
      ...
    },
    ```
    
2. Once done you can find the aws sdk in **app/vendor/aws**
3. Add the following code to params.php file located in **app/config/params.php**
  
    ```
    return [
      ...
      'amazon' => [
          'region' => 'ap-southeast-1',
          'credentials' => [
              'key'    => 'your_amazon_key',
              'secret' => 'your_amazon_secrete',
          ],
      ],
      ...
    ]
    ```
    
  You can modify your own region of the server.
  
4. You can use the code by creating the object of the **_AwsS3_** class.

### Code snippets

1. Uploading/adding object to amazon bucket.
  
  ```
  $url = $object->uploadObject($source, $bucket, $key, $acl);
  ```
  
2. Get presigned url for particular time.

  ```
  $preSignedUrl = $object->getPreSignedUrl($bucket, $key, $timeInMinutes);
  ```

3. Deleting an object from S3.

  ```
  $success = $object->deleteObject($bucket, $key);
  ```

4. Get/Download object

  ```
  $data = $object->downloadObject($bucket, $key);
  ```
