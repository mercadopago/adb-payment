#!/bin/bash

scriptPath=$(dirname -- "$(readlink -f -- "$BASH_SOURCE")")

changelogPath=$scriptPath/../CHANGELOG.md
modulePath=$scriptPath/../etc/module.xml

lastChangelogVersion=$(sed -n 's/## \[\([0-9]\+.[0-9]\+.[0-9]\+\)\].*/\1/p' $changelogPath | head -n1 | xargs)
moduleVersion=$(sed -n 's/.*setup_version="\(.*\)".*/\1/p' $modulePath | head -n1 | xargs)

if [ "$lastChangelogVersion" = "$moduleVersion" ]; then
    echo "OK"
    echo "CHANGELOG VERSION: $lastChangelogVersion"
    echo "MODULE VERSION: $moduleVersion"
    exit 0
else
    echo "ERROR - CHANGELOG VERSION DIFFER FROM MODULE VERSION"
    echo "CHANGELOG VERSION: $lastChangelogVersion"
    echo "MODULE VERSION: $moduleVersion"
    exit 1
fi
