## Installation

This Magento 2.x extension must be installed manually, as follows:

1. Copy the files to your Magento root, so that the extension exists at
   /app/code/Appmerce/[ExtensionName]/..
   
2. Run the following commands from your root to finish the installation:
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy -f