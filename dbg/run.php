<?php

/*
    
    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/encoding.php' );
    require_once( '../api/tools/sql.php' );
    
    $time_begun = time() * 1000;
    
    $users = \Tools\SQL::query( "SELECT * FROM `users`" );
    while ( $row = \Tools\SQL::fetch_row( $users, true ) ):
        $data = '{"bit":{"event":{"access":true}}}';
        $id = $row['id'];
        \Tools\SQL::query( "UPDATE `users` SET `bit_data` = '$data' WHERE `id` = '$id'" );
    endwhile;
    
    $time_executed = ( time() * 1000 ) - $time_begun; // number of milliseconds taken to execute script
    
    \Tools\Communicator::throw_result( 'Task executed in ' . $time_executed . 'ms' );
    
*/
/*

    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/encoding.php' );
    require_once( '../api/tools/sql.php' );
    
    $time_begun = time() * 1000;
    
    $subscriptions = \Tools\SQL::query( "SELECT * FROM `subscriptions`" );
    while ( $row = \Tools\SQL::fetch_row( $subscriptions, true ) ):
        $id = $row['id'];
        $dump = (array)json_decode( $row['data'] );
        $data = array();
        foreach( $dump as $key=>$a )
        {
            if( $key == 'handler' )
            {
                $data['owner'] = $dump[$key];
            }
            else
            {
                $data[$key] = $a;
            }
        }
        $data = json_encode($data);
        \Tools\SQL::query( "UPDATE `subscriptions` SET `data` = '$data' WHERE `id` = '$id'" );
    endwhile;
    
    $time_executed = ( time() * 1000 ) - $time_begun; // number of milliseconds taken to execute script
    
    \Tools\Communicator::throw_result( 'Task executed in ' . $time_executed . 'ms' );
    
*/
/*
    
    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/encoding.php' );
    require_once( '../api/tools/sql.php' );
    
    $time_begun = time() * 1000;
    
    $subscriptions = \Tools\SQL::query( "SELECT * FROM `subscriptions`" );
    while ( $row = \Tools\SQL::fetch_row( $subscriptions, true ) ):
        $id = $row['id'];
        $dump = (array)json_decode( $row['data'] );
        $data = array();
        foreach( $dump as $key=>$a )
        {
            if( $key == 'tag_prefix' )
            {
                $data['nomenclature'] = $dump[$key];
            }
            else
            {
                $data[$key] = $a;
            }
        }
        $data = json_encode($data);
        \Tools\SQL::query( "UPDATE `subscriptions` SET `data` = '$data' WHERE `id` = '$id'" );
    endwhile;
    
    $time_executed = ( time() * 1000 ) - $time_begun; // number of milliseconds taken to execute script
    
    \Tools\Communicator::throw_result( 'Task executed in ' . $time_executed . 'ms' );
    
*/
/*

    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/encoding.php' );
    require_once( '../api/tools/sql.php' );
    
    $time_begun = time() * 1000;
    
    $subscriptions = \Tools\SQL::query( "SELECT * FROM `subscriptions`" );
    while ( $row = \Tools\SQL::fetch_row( $subscriptions, true ) ):
        $id = $row['id'];
        $dump = (array)json_decode( $row['data'] );
        unset( $dump['owner'] );
        $data = json_encode($dump);
        \Tools\SQL::query( "UPDATE `subscriptions` SET `data` = '$data' WHERE `id` = '$id'" );
    endwhile;
    
    $time_executed = ( time() * 1000 ) - $time_begun; // number of milliseconds taken to execute script
    
    \Tools\Communicator::throw_result( 'Task executed in ' . $time_executed . 'ms' );
    
*/
/*
    $to = "john@neetgroup.net";
                            
    $subject= "Subscription Confirmation";
                                
    $message= "
    <center>
    	<div class='body' style='display:block; background-color:#fff; width:600px; overflow:auto; margin:0; padding:0;'>
    		<img src='http://s28.postimg.org/av4fsnya5/wrapper.png' style='display:block; margin:0; padding:0;' />
    		<div class='wrapper' style='cursor:default; display:block; margin:0; padding:0;'>
    			<p class='message' style='display:block; margin:0; padding:15px; text-align:left; border-bottom:2px solid #3b3d3e; border-left:3px solid #3b3d3e; border-right:3px solid #3b3d3e;'>
    			Hello `<b><u>Veritas</u></b>`,<br />
    			We&#39;re sending you this message as confirmation that you have subscribed to subscription #xxxxxxxxxxxxxxxsgv1b ; TyE_1gWafg0Vz.
    			</p>
    		</div>
    	</div>
    </center>        
    ";
                                
    $headers= "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: do-not-reply@neetgroup.net' . "\r\n";
    $headers .= "Cc: $to" . "\r\n";
                                
    mail($to, $subject, $message, $headers);
    echo 'Email Sent!';
*/
/*
    require_once( '../connect.php' );
    require_once( '../api/tools/communicator.php' );
    require_once( '../api/tools/encoding.php' );
    require_once( '../api/tools/sql.php' );
    
    $users = \Tools\SQL::query( "SELECT * FROM `users`" ); // query all users
    while( $user = \Tools\SQL::fetch_row( $users, true ) ):
        
        $handle = $user['id'];
        
        $bits = array
        (
            'eRAxg6u39DEgwJa' => array
            (
                'foo' => bar
            )
        );
        
        $bits = json_encode( $bits );
        
        \Tools\SQL::query( "UPDATE `users` SET `bit_data` = '$bits' WHERE `id` = '$handle'" );
        
    endwhile;
*/