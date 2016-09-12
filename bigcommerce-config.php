<?php

Bigcommerce::configure(array(
    'store_url' => '',
    'username'  => '',
    'api_key'   => ''
));

Bigcommerce::setCipher('TLSv1');
Bigcommerce::verifyPeer(true);

?>