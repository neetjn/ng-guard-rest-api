<?php

    define (ADMIN, 'admin'); // module admin request
    define (USER, 'user'); // module user request
    define (APP, 'app'); // module app request
    define (SUBSCRIPTION, 'subscription'); // module subscription request
    define (UTIL, 'util'); // module utility request

    set_include_path( dirname(__FILE__) ); // set root path as include

    require_once ( 'connect.php' );

    require_once ( get_include_path() . '/api/tools/request.php' );
    require_once ( get_include_path() . '/api/tools/communicator.php' );
    require_once ( get_include_path() . '/api/tools/encoding.php' );
    require_once ( get_include_path() . '/api/tools/utility.php' );
    require_once ( get_include_path() . '/api/tools/sql.php' );
    
?>