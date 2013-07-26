mychaelstyle/php-utils
=============

mychaelstyle/php-utils is imple utilities for php 5.  

# Outline

1. Storage version 0.1.0 ... easy access to online storages.
2. Datastore version 0.1.0 ... easy access to key value stores.
3. queue\\Factory  version 0.1.0 ... easy access to queue service.

# mychaelstyle\\Storage

Storage is an uploading utility class to online or offline storage services.
You can transfer and read, write files like local files only simple configurations.


Storageはローカルファイルを扱い様な感覚で様々なストレージサービスにファイルを保存するライブラリです。
簡単な設定だけで同時に複数のストレージにファイルをアップロードすることもできます

#### Supported storage

* Local file system
* Amazon S3 (require AWS SDK files)
* Mysql

#### Usage

You can read and write as local file.

ローカルファイルを扱うように読み書きできます。

    $dsn = 'local:///home/hoge/storage';
    $options = array('permission'=>0644,'permission_folder'=>0755)
    $storage = new mychaelstyle\Storage($dsn,$options);
    
    // you can open and write,
    $file = $storage->createFile('/foo/var.txt');
    $file->open('w');
    $file->write("Hello mychaelstyle\\Storage!\n");
    $file->close();
     
    // you can read
    $file = $storage->createFile('/foo/hoge.txt');
    $file->open('r');
    $str = $file->fgets();
    $file->close();
    
    // import from local file
    $file = $storage->createFile('/foo/boo.txt');
    $file->import('/home/hoge/boo.txt');
    
    // file_get_contents
    $file = $storage->createFile('/foo/example.txt');
    $string = $file->getContents();
    
    // file_put_contents
    $file = $storage->createFile('/foo/me.txt');
    $file->putContents("My name is Masanori Nakashima.\n");

#### simple transaction support.

You can upload after all tasks are success.
but this transaction don't support exclusive.

すべてのタスクが完了してからアップロードを確定するシンプルなトランザクション機能があります。
ただしこのトランザクションは現在は排他ロックをサポートしていません。

    $dsn = 'amazon_aws://TOKYO/my_bucket';
    $options = array(
			'acl'      => AmazonS3::ACL_PUBLIC,
			'curlopts' => array(CURLOPT_SSL_VERIFYPEER => false)
    );
    $storage = new mychaelstyle\Storage($dsn,$options,false);
    try {
      $file1 = $storage->createFile('/foo/me.txt');
      $file1->putContents("My name is Masanori Nakashima.\n");

      $file2 = $storage->createFile('/foo/you.txt');
      $file2->putContents("What's your name?\n");

      $storage->commit();
    } catch(Exception $e){
      $storage->rollback();
    }
    
#### support upload some storages at once.

You can upload to some storage at once,
do followings.

複数のオンラインストレージに同時に同じファイルをアップロードすることができます。

    // create new instance
    $dsn = 'local:///home/hoge/storage';
    $options = array('permission'=>0644,'permission_folder'=>0755)
    $storage = new mychaelstyle\Storage($dsn,$options);
    // add a provider
    $dsn = 'amazon_aws://TOKYO/my_bucket';
    $options = array(
			'acl'      => AmazonS3::ACL_PUBLIC,
			'curlopts' => array(CURLOPT_SSL_VERIFYPEER => false)
    );
    $storage->addProvider($dsn,$options);
    // put contents
    $storage->putContents('/my/foo.txt', 'This is test contents!');

## Amazon S3

### DSN

    amazon_s3://[your aws region]/[your bucket name]

### Initialize Options

* key    ... Amazon Web Services Key.
* secret ... Amazon Web Services Secret.
* default_cache_config ... see the aws php sdk document.
* certificate_autority ... see the aws php sdk document.
* curlopts ... curl options. array.
* acl      ... acl. see the aws php sdk.
* contentType ... content-type

### File upload options

You can use all options of AWS SDK Amazon S3 file options.
see the AWS PHP SDK.

## Mysql

### DSN

    mysql://[host]:[port]/[database]/[table]
    mysql://[host]:[port]/[database]/[table]?uri=[field name for uri]&contents=[field name for contents]

### Initialize options

* user  ... mysql connect user
* pass  ... mysql connect password

# How to develop a provider plugin

You can develop anothe storage provider plugin, only extends from mychaelstyle\storage\Provider class.

for example,

    class GoogleDrive extends \mychaelstyle\storage\Provider {
      public function connect($dsn,$options=array()){
        // ... something to do for connection
      }
      public function get($uri,$pathto=null){
        // something to do,
        // but if $pathto is not null, you should save the file to the path
        // after getting the file from the uri.
      }
      public function put($path,$to,$options=array()){
        // something to do,
      }
      public function remove($uri){
        // something to do,
      }
    }

## Testing

You should set following envs for Tests of AWS plougins.

* AWS_KEY
* AWS_SECRET_KEY
* AWS_REGION
* AWS_S3_BUCKET

e.g. for .*rc

    AWS_KEY="Your aws key"
    AWS_SECRET_KEY="Your aws secret key"
    AWS_REGION="TOKYO"
    AWS_S3_BUCKET="your-bucket-name"
    export AWS_KEY AWS_SECRET_KEY AWS_REGION AWS_S3_BUCKET


