=======================================
Contact Form 7 - Postcode Extension
=======================================

Contact Form 7 - Postcode Extension is a dynamic address field that is populated from the http://postcodesoftware.net API.
Note the account/password to for accessing the API is still encoded in the PHP directly for simplicity and security.
On first installation of the software create a .PHP file called account.php with the following template:

<?php 
$POSTCODE_ACCOUNT = '[accountname]';
$POSTCODE_PASSWORD = '[password]';
?>