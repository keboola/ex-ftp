# FTP Extractor
This component downloads file(s) from an FTP (optional TLS) or SFTP server. It supports glob syntax.

# Configuration
## Options
The configuration requires the following properties: 

- `host` (string, required) – IP address or hostname of the FTP(s)/SFTP server.
- `port` (integer, required) – Server port (default: 21).
- `username` (string, required) – User with the correct access rights.
- `password` (string, optional) – Password for the given user.
- `ssh` (object, optional) – SSH proxy settings.
  - `enabled` (boolean, optional) – Whether SSH is enabled (defaults to `true` if not set).
  - `keys` (object, required) – SSH key settings:
    - `#private` (string, required) – Private SSH key.
    - `public` (string, required) – Public SSH key.
  - `user` (string, required) – SSH user.
  - `sshHost` (string, required) – Host of the SSH proxy.
  - `sshPort` (string, optional) – Port where SSH runs (defaults to `22` if not set).
  - `passivePortRange` (string, required) – Port range for FTP passive mode (e.g., `10000:10005`)
- `path` (string, required) – Path to a specific file or a glob syntax path.
   - FTP(s) uses an **absolute** path.
   - SFTP uses a **relative** path based on the user's home directory.
- `connectionType` (string, required) – Type of connection (possible values: [FTP|FTPS|SFTP]).
- `privateKey` (string, optional) – Used only with an SFTP `connectionType`.
- `onlyNewFiles` (boolean, optional) – Compares timestamps of files from the last run and downloads only new files.
- `listing` (string, optional, enum [`manual|recursion`] default: `recursion`) – Use `manual` if the FTP server does not support recursive listing.
- `ignorePassiveAddress` (boolean, optional) – Enables ignoring passive addresses.

## Example
**Configuration to download a specific file:**

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

**Configuration to download files using glob syntax:**

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
**Configuration for recursive manual listing (for servers without recursion support):**

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
**Configuration to download only new files using glob syntax:**

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
**Configuration to download only new *.CSV files using glob syntax from an SFTP server:***

*(Uses a relative path)*

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

**Configuration to download an exact file from an SFTP server:**

*(Uses a relative path)*

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


**Configuration to download files using an SSH proxy:**

*(Set `ignorePassiveAddress` to `true`)*

```json
    {
        "parameters": {
            "host":"ftp.example.com",
            "username": "ftpuser",
            "#password": "userpass",
            "ssh": {
                "enabled": true,
                "keys": {
                  "#private": "PRIVATE_KEY",
                  "public": "PUBLIC_KEY"
                },
                "user": "root",
                "sshHost": "sshproxy",
                "passivePortRange": "10000:10005"
            },
            "port": 22,
            "path": "files/data.csv",
            "connectionType": "SFTP"
        }
    } 
``` 

# Development
 
Clone this repository and initialize the workspace with the following commands:

```
git clone https://github.com/keboola/ex-ftp
cd ex-ftp
docker compose build
docker compose run --rm dev composer install --no-scripts
```

Build the image:
```
docker compose build dev
```

## Tools

- Run all tests: `docker compose run --rm dev composer tests`
- Unit tests: `docker compose run --rm dev composer tests-phpunit`
- Datadir tests: `docker compose run --rm dev composer tests-datadir`
- Code sniffer: `docker compose run --rm dev composer phpcs`
- Static analysis: `docker compose run --rm dev composer phpstan`

## Adding a New Functional Test

Since the FTP extractor works with file timestamps, all `state.json`
files must be crated at runtime. For functional tests with `onlyNewFiles` set to `false`, 
add the following to `tests/functional/DatadirTest.php`:
```php
$state = [
    "ex_ftp_state" => [
        "newest_timestamp" => 0,
        "last_timestamp_files" => [],
    ],
];
JsonHelper::writeFile(__DIR__ . '/###NAME_OF_TEST###/expected/data/out/state.json', $state);

``` 

For tests with `onlyNewFiles` set to `true`, specify both `state.json` files:
```php
$state = [
    "ex_ftp_state" => [
        "newest_timestamp" => $timestamps["dir1/alone.txt"],
        "last_timestamp_files" => ["dir1/alone.txt"],
    ],
];
JsonHelper::writeFile(__DIR__ . '/###NAME_OF_TEST###/expected/data/out/state.json', $state);
JsonHelper::writeFile(__DIR__ . '/###NAME_OF_TEST###/source/data/in/state.json', $state);
```
*(Where `alone.txt` is the only file in the downloaded folder.)*
 
# Integration

For details on deployment and integration with Keboola, refer to the [deployment section of the developer documentation](https://developers.keboola.com/extend/component/deployment/). 

## License

MIT licensed. See [LICENSE](./LICENSE) file.
