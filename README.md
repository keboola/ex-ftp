# FTP extractor

[![Build Status](https://travis-ci.com/keboola/ex-ftp.svg?branch=master)](https://travis-ci.com/keboola/ex-ftp)
[![Maintainability](https://api.codeclimate.com/v1/badges/633ff7508d0e316269da/maintainability)](https://codeclimate.com/github/keboola/ex-ftp/maintainability)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/ex-ftp/blob/master/LICENSE.md)

Download file(s) from FTP (optional TLS) or SFTP server. Supports glob syntax.
# Configuration

## Options

The configuration requires following properties: 

- `host` - string (required): IP address or Hostname of FTP(s)/SFTP server
- `port` - integer (required): Server port (default port is 21)
- `username` - string (required): User with correct access rights
- `password` - string (optional): Password for given User
- `path` - string (required): Path to specific file or glob syntax path
    - FTP(s) uses absolute path
    - SFTP uses relative path according to user's HOME directory
- `connectionType` - string (required): Type of connection (possible value [FTP|FTPS|SFTP])
- `privateKey` - string (optional): Possible to use only with SFTP connectionType.
- `onlyNewFiles` - boolean (optional): Compares timestamp of files from last run and download only new files
- `listing` - string (optional, enum [manual|recursion] default: recursion): Use `manual` in case your FTP server does not support listing recursion.
- `ignorePassiveAddress` - boolean (optional): Sets ignore passive address

## Example
Configuration to download specific file:

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "port": 21,
            "path": "/dir1/file.csv",
            "connectionType": "FTP"
        }
    } 
``` 

Configuration to download files by glob syntax:

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "port": 21,
            "path": "/dir1/*.csv",
            "connectionType": "FTP"
        }
    } 
    
```
Configuration to download files by glob syntax with recursion manually (when server does not support recursive listing):

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "port": 21,
            "path": "/dir1/*/*.csv",
            "connectionType": "FTP",
            "listing": "manual"
        }
    } 
    
``` 
Configuration to download only new files by glob syntax:

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "port": 21, 
            "path": "/dir1/*.csv",
            "connectionType": "FTP",
            "onlyNewFiles": true
        }
    } 
``` 
Configuration to download only new *.csv files by glob syntax from SFTP server:
(you need to use relative path)

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "port": 22, 
            "path": "**/*.csv",
            "connectionType": "SFTP",
            "onlyNewFiles": true
        }
    } 
``` 

Configuration to download exact file on SFTP server
(you need to use relative path)

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "port": 22, 
            "path": "files/data.csv",
            "connectionType": "SFTP"
        }
    } 
``` 


# Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/ex-ftp
cd ex-ftp
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Build the image:
```
docker-compose build dev
```

## Tools

- Tests: `docker-compose run --rm dev composer tests`
  - Unit tests: `docker-compose run --rm dev composer tests-phpunit`
  - Datadir tests: `docker-compose run --rm dev composer tests-datadir`
- Code sniffer: `docker-compose run --rm dev composer phpcs`
- Static analysis: `docker-compose run --rm dev composer phpstan`

## New functional test

Because FTP extractor works with file's timestamps, all `state.json`
files must be crated at runtime. When you add new functional test with
config option `onlyNewFiles` set to `false` add following to 
`tests/functional/DatadirTest.php`:
```php
$state = [
    "ex-ftp-state" => [
        "newest-timestamp" => 0,
        "last-timestamp-files" => [],
    ],
];
JsonHelper::writeFile(__DIR__ . '/###NAME_OF_TEST###/expected/data/out/state.json', $state);

``` 

For tests with `onlyNewFiles` set to `true` you have to specify both state.json files:
```php
$state = [
    "ex-ftp-state" => [
        "newest-timestamp" => $timestamps["dir1/alone.txt"],
        "last-timestamp-files" => ["dir1/alone.txt"],
    ],
];
JsonHelper::writeFile(__DIR__ . '/###NAME_OF_TEST###/expected/data/out/state.json', $state);
JsonHelper::writeFile(__DIR__ . '/###NAME_OF_TEST###/source/data/in/state.json', $state);
```
Where `alone.txt` should be the single file in downloaded folder.

 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
