# Condense
Flat-file database in PHP.

![Logo](logo.png)

Based on the [Fllat](https://github.com/wylst/fllat) and [Prequel](https://github.com/wylst/prequel) libraries by [Wylst](https://github.com/wylst). Special mention for [Alfred Xing](https://github.com/alfredxing) who seems to be the main contributor behind both. With added support for:

* Encrypted databases using [php-encryption](https://github.com/defuse/php-encryption) by [Taylor Hornby](https://github.com/defuse)
* Composer via Packagist

## Caveats
This is a flat file database system. It removes the headache of setting up and configuring a database server, but introduces a few of its own:

* I/O will be _much_ slower due to many disk read/write actions
* Encrypting a database will hugely affect performance
* Bugs may arise due to concurrency issues
* Misconfigured web applications using this library may accidentally allow their databases to be downloaded over HTTP
