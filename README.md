snb-php-utils
=============

snb-php-utils is imple utilities for php 5.  

# Outline

1. snb\\Storage version 0.1.0 ... easy access to online storages.

# snb\\Storage

snb\\Storage is an uploading utility class to online or offline storage services.
You can transfer and read, write files like local files only simple configurations.

snb\\Storageはローカルファイルを扱い様な感覚で様々なストレージサービスにファイルを保存するライブラリです。

#### Supported storage

* Amazon S3 (require AWS SDK files)
* Mysql
* Local file system

#### I'll develop followings.

* HTTP POST/PUT/DELETE
* Amazon Glacier (planed ver 0.2)
* SCP (planed ver 0.2)
* Memcache (ver 0.3)
* Dropbox (ver. 0.3)
* Google Drive (ver 0.3)
* Redis

#### Usage

eg.

    $storage = new snb\Storage($dsn,$options);
    
    // you can open and write,
    $file = $storage->createFile('/foo/var.txt');
    $file->open('w');
    $file->write("Hello snb\\Storage!\n");
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

simple transaction support.

    $storage = new snb\Storage($dsn,$options,false);
    try {
      $file1 = $storage->createFile('/foo/me.txt');
      $file1->putContents("My name is Masanori Nakashima.\n");

      $file2 = $storage->createFile('/foo/you.txt');
      $file2->putContents("What's your name?\n");

      $storage->commit();
    } catch(Exception $e){
      $storage->rollback();
    }
    

### Amazon S3

#### DSN
    amazon_s3://[your aws region]/[your bucket name]

#### Initialize Options

* key    ... Amazon Web Services Key.
* secret ... Amazon Web Services Secret.
* default_cache_config ... see the aws php sdk document.
* certificate_autority ... see the aws php sdk document.
* curlopts ... curl options. array.
* acl      ... acl. see the aws php sdk.
* contentType ... content-type

#### File upload options



### Mysql

#### DSN

    mysql://[host]:[port]/[database]/[table]

    mysql://[host]:[port]/[database]/[table]?uri=[field for uri]&contents=[field for contents]



