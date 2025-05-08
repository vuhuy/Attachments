php maintenance/install.php \
    --dbname=mediawiki \
    --dbserver=database \
    --installdbuser=mediawiki \
    --installdbpass=mediawiki1234 \
    --dbuser=mediawiki \
    --dbpass=mediawiki1234 \
    --server='http://localhost:8080' \
    --scriptpath='' \
    --lang=en \
    --pass=mediawiki1234 \
    'Attapedia' 'Attachment' &&
sed -i "1a\\
error_reporting( -1 );\\
ini_set( 'display_errors', 1 );\\
\$wgShowExceptionDetails = true;\\
\$wgShowDBErrorBacktrace = true;
" LocalSettings.php &&
echo "wfLoadExtension( 'WikiEditor' );" >> LocalSettings.php  &&
echo "wfLoadExtension( 'CodeEditor' );" >> LocalSettings.php  &&
echo "wfLoadExtension( 'VisualEditor' );" >> LocalSettings.php  &&
echo "wfLoadExtension( 'Attachments' );" >> LocalSettings.php  &&
echo "\$wgAttachmentsShowInNamespaces = true;" >> LocalSettings.php &&
echo "\$wgAttachmentsShowInViews = true;" >> LocalSettings.php &&
echo "\$wgAttachmentsShowEmptySection = true;" >> LocalSettings.php &&
echo "\$wgNamespacesWithSubpages[NS_MAIN] = 1;" >> LocalSettings.php &&
echo "\$wgEnableUploads = true;" >> LocalSettings.php &&
echo "\$wgGroupPermissions['*']['upload'] = true;" >> LocalSettings.php &&
echo "\$wgGroupPermissions['*']['reupload'] = true;" >> LocalSettings.php &&
echo "\$wgRateLimits = [];" >> LocalSettings.php &&
apachectl graceful &&
php maintenance/edit.php --conf LocalSettings.php Control < extensions/Attachments/tests/data/Control.html
php maintenance/edit.php --conf LocalSettings.php Attachments < extensions/Attachments/tests/data/Control.html