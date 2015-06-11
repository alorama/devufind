#!/bin/bash
# base dir definition mylibcloudn
BASE_DIR="/usr/local/devufind"
SOLR_DIR="${BASE_DIR}/solr"
# dir to get MARC records
INPUT_DIR="/usr/local/devufind/clouds/in"
# tmp dir to process MARC records
TMP_DIR="/usr/local/devufind/clouds/tmp_files"
WORKFILE="/tmp/solr_indexing.working"
DELETEFILE="/tmp/delete_ids"
# dir to archive records
ARCHIVE_DIR="/usr/local/devufind/clouds/archive"
ARCHIVE_LOG_DIR="/usr/local/devufind/clouds/logs"
LOG_EXT=`date +%m_%d_%H`
IMPORT_LOG="import-log.${LOG_EXT}"

echo $BASE_DIR
echo $SOLR_DIR
echo $INPUT_DIR
echo $TMP_DIR
echo $ARCHIVE_DIR
echo $ARCHIVE_LOG_DIR
echo $LOG_EXT
echo $IMPORT_LOG

# See if a previous version is still running
if [ -e $WORKFILE ]; then
    echo "`date`: Tried to run, but another SOLR indexing job is already running"
    exit
fi
# If not, touch the WORKFILE
touch $WORKFILE

files=`find ${INPUT_DIR} -mmin +2 -type f`
fileCT=`echo ${files} | wc -w`

# Do we have any files? if not don't do anything
if [ ${fileCT} != 0 ]; then
    echo "`date`: Runnning another SOLR indexing job"
    for f in ${files}
    do
        mv $f ${TMP_DIR}
    done
    # process files
    cd ${BASE_DIR}
    for f in `ls ${TMP_DIR}`
    do
        ./import-marc.sh ${TMP_DIR}/$f >> ${IMPORT_LOG} 2>&1
        mv ${TMP_DIR}/${f} ${ARCHIVE_DIR}/${f}
    done
    grep "Attempting to open data file:"  ${IMPORT_LOG}
    echo
    grep "Adding"                    ${IMPORT_LOG}
    echo
    grep "Deleting"                  ${IMPORT_LOG}
    mv ${IMPORT_LOG} ${ARCHIVE_LOG_DIR}/${IMPORT_LOG}
fi

if [ -e $DELETEFILE ]; then
    rm $DELETEFILE
fi
# Done, remove the WORKFILE
rm $WORKFILE
exit
