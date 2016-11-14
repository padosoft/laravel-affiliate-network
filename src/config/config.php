<?php
return array(
    'zanox' => array(
        'username' => env(
            'ZANOX_USERNAME',
            'padosoft'
        ),
        'password' => env(
            'ZANOX_PASSWORD',
            ''
        )
    ),
    'tradedoubler' => array(
        'username' => env(
            'TRADEDOUBLER_USERNAME',
            'padosoft'
        ),
        'password' => env(
            'TRADEDOUBLER_PASSWORD',
            ''
        )
    ),
    'commissionjunction' => array(
        'username' => env(
            'COMMISSIONJUNCTION_USERNAME',
            'padosoft'
        ),
        'password' => env(
            'COMMISSIONJUNCTION_PASSWORD',
            ''
        )
    ),
);
