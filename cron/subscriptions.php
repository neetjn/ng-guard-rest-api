<?php

    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/sql.php' );
    
    $time_begun = time() * 1000;
    
    // left here, update for new subscription data structure
    
    $users = \Tools\SQL::query( "SELECT * FROM `users`" );
    while ( $row = \Tools\SQL::fetch_row( $users, true ) ): // left here, clean up code
        $data = json_decode( $row['bit_data'] ); // decode bit data to be read
        
        if( $data->bit->tye->access !== false && $data->bit->tye->access !== null ):
            if ( strtotime( $data->bit->tye->expires ) < time() ):
                $data->bit->tye->access = false;
                $data->bit->tye->expires = null;
            endif;
        endif;
        if( $data->bit->pye->access !== false && $data->bit->pye->access !== null):
            if ( strtotime( $data->bit->pye->expires ) < time() ):
                $data->bit->pye->access = false;
                $data->bit->pye->expires = null;
            endif;
        endif;
        
        $bit_data = json_encode( $data ); // re-encode bit data for deployment
    
        $user_id = $row['id'];
        \Tools\SQL::query( "UPDATE `users` SET `bit_data` = '$bit_data' WHERE `id`='$user_id'" ); // update user
    endwhile;
    
    $time_executed = ( time() * 1000 ) - $time_begun; // number of milliseconds taken to execute script
    
    \Tools\Communicator::throw_result( 'Task executed in ' . $time_executed . 'ms' );