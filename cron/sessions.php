<?php

    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/sql.php' );
    
    $time_begun = time() * 1000;
    
    $sessions = \Tools\SQL::query( "SELECT * FROM `sessions`" );
    while ( $row = \Tools\SQL::fetch_row( $sessions, true ) ):
        $disable = strtotime($row['created']) + ((60*60)*24) < time(); // if session is active and older than 24 hours, disable
        if( $disable ): // begun over 24 hours ago
            $sesh_id = $row['id'];
            \Tools\SQL::query( "UPDATE `sessions` SET `active` = 0 WHERE `id` = '$sesh_id'" ); // disable session
        endif;
    endwhile;
    
    $time_executed = ( time() * 1000 ) - $time_begun; // number of milliseconds taken to execute script
    
    \Tools\Communicator::throw_result( 'Task executed in ' . $time_executed . 'ms' );