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
- `password` - string (required): Password for given User
- `path` - string (required): Path to specific file or glob syntax path
- `connectionType` - string (required): Type of connection (possible value [FTP|FTPS|SFTP])
- `privateKey` - string (optional): Possible to use only with SFTP connectionType.
- `wildcard` - boolean (optional): TRUE if path is glob syntax (default FALSE)
- `onlyNewFiles` - boolean (optional): Compares timestamp of files from last run and download only new files

## Example
Configuration to download specific file:

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "password": "userpass",
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
            "password": "userpass",
            "port": 21,
            "path": "/dir1/*.csv",
            "connectionType": "FTP",
            "wildCard": true
        }
    } 
    
``` 
Configuration to download only new files by glob syntax:

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "password": "userpass",
            "port": 21, 
            "path": "/dir1/*.csv",
            "connectionType": "FTP",
            "wildCard": true,
            "onlyNewFiles": true
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

 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
