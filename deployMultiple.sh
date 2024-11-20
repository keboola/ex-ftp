#!/bin/bash
set -e

# FTP extractor
export KBC_DEVELOPERPORTAL_APP=keboola.ex-ftp
./deploy.sh

# FTP extractor for csas
export KBC_DEVELOPERPORTAL_APP=keboola.ex-ftp-csas
./deploy.sh
