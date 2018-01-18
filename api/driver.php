<?php

    // error_reporting(E_ALL);
    // ini_set('display_errors', 1);

    require_once( '../guard.php' );

    $driver_error = array(
        0 => 'Request Could Not Be Processed',
        1 => 'Invalid Developer Credentials',
        2 => 'Server Currently Offline',
        3 => 'Custom Script Does Not Exist',
        4 => 'Invalid Command',
        5 => 'Invalid Driver Request',
        6 => 'Module Does Not Exist',
        7 => 'Guard Is Currently Under Maintenance',
        8 => 'This Address Has Been Filtered From Guard Services'
    );

    $general_module_error = array(
        0 => 'Insufficient Module Parameters'
    );

    $admin_module_result = array(
        0 => 'User Banned Successfully',
        1 => 'Blacklist Successful',
        2 => 'Server Status Updated Successfully'
    );

    $admin_module_error = array (
        0 => 'Developer Does Not Have Administrative Access',
        1 => 'Blacklist Nomenclature Not Specified'
    );

    $user_module_result = array(
        0 => 'User Session Created Successfully',
        1 => 'User Session Ended Successfully',
        3 => 'Bit Updated Successfully',
        4 => 'Subscribed Successfully',
        5 => 'Credit Transferred Successfully'
    );

    $user_module_error = array(
        0 => 'User Session Does Not Exist',
        1 => 'Invalid Forum Identification',
        2 => 'Action Not Specified'
    );

    $subscription_module_result = array(
        0 => 'Subscription Updated Successfully'
    );

    $app_module_result = array(
        0 => 'App Updated Successfully'
    );
    
    require_once ( get_include_path() . '/api/modules/util.php' );
    
    if( \Module\Util::Filtered() ):
        \Tools\Communicator::throw_error( $driver_error[8] ); // ip address is banned
    endif;
    
    function status_check() {
        global $driver_error;
        if ( !\Module\Util::Online() ):
            \Tools\Communicator::throw_error( $driver_error[2] ); // if server is offline
        endif;
        if ( \Module\Util::Maintenance() ):
            \Tools\Communicator::throw_error( $driver_error[7] ); // if guard in maintenance
        endif;
    }

    $request = \Tools\Request::type('key'); // specify post or get request
    if( $request !== 'GET' && $request !== 'POST' ): // left here, getting error thrown
        \Tools\Communicator::throw_error( $driver_error[0] ); // if request not found
    endif;

    function catch_parameters() {
        global $request;
        $params = array(
            0 => 'a',
            1 => 'b',
            2 => 'c',
            3 => 'd',
            4 => 'e',
            5 => 'f'
        ); // max 6 parameters
        foreach( $params as $key=>$var ):
            if( \Tools\Request::read( $var, $request ) ):
                $params[$key] = \Tools\Request::read( $var, $request );
            else: // split array into param size
                $params = array_splice( $params, 0, $key );
                break;
            endif;
        endforeach;
        return $params;
    }
    
    $developer_key = \Tools\Request::read( 'key', $request );
    
    require_once ( get_include_path() . '/api/modules/developer.php' );
    
    $developer = \Module\Developer::who($developer_key); // validate developer credentials
    if ( !$developer ):
        \Tools\Communicator::throw_error( $driver_error[1] ); // if developer not found
    endif;

    $module = \Tools\Request::read('mod', $request); // [USER=>user][PROJECT=>project][UTIL=>util][APP=>app][SUBSCRIPTION=>subscription]
    if( !$module ):
        // load custom script
        $custom = \Tools\Request::read('ctm', $request);
        if( !$custom ):
            \Tools\Communicator::throw_error( $driver_error[0] ); // if module and script not found
        else: // if script parameter found
            if( !$developer['admin'] ):
                \Tools\Communicator::throw_error( $admin_module_error[0] );
            endif;
            if( !file_exists( get_include_path() . '/api/modules/custom/' . $custom . '.php' ) ):
                \Tools\Cummunicator::throw_error( $driver_error[3] ); // if script not found
            endif;
            require_once( get_include_path() . '/api/modules/custom/' . $custom . '.php' );
            exit;
        endif;
    endif;

    switch($module):

        case ADMIN:

            if( !$developer['admin'] ):
                \Tools\Communicator::throw_error( $admin_module_error[0] );
            endif;
            
            $command = \Tools\Request::read( 'cmd', $request );
            if ( !$command ):
                \Tools\Communicator::throw_error( $driver_error[5] );
            endif;

            require_once ( get_include_path() . '/api/modules/admin.php' );
            
            $params = catch_parameters();
            
            switch($command):
               case 'ban':
                   \Module\Admin::Ban( $params[0] );
                   \Tools\Communicator::throw_result( $admin_module_result[0] );
                   break;
               case 'blacklist':
                   $input = $params[0];
                   switch( $params[1] ):
                       case 'key':
                           \Module\Admin::Blacklist( $input );
                           break;
                       case 'user':
                           \Module\Admin::Blacklist( $input, true );
                           break;
                       default:
                           \Tools\Communicator::throw_error( $admin_module_error[1] );
                           break;
                   endswitch;
                   \Tools\Communicator::throw_result( $admin_module_result[1] );
                   break;
               case 'filter':
                   \Module\Admin::Filter( $params[0] );
                   \Tools\Communicator::throw_result( $admin_module_result[0] );
                   break;
               case 'server':
                   \Module\Admin::Server( $params[0] );
                   \Tools\Communicator::throw_result( $admin_module_result[2] );
                   break;
               default:
                   \Tools\Communicator::throw_error( $driver_error[4] );
                   break;
            endswitch;

            break;
        case USER: // accepts both sessions and direct commands

            status_check(); // check if offline or maintenance

            require_once ( get_include_path() . '/api/modules/user.php' );
            
            session_start(); // for user sessions
            
            $params = catch_parameters();
            
            $session_command = \Tools\Request::read( 'sesh', $request );
            
            if( $session_command ): // suggests user is using session

                // driver.php?mod=user&sesh=...

                if( !\Module\User\Session::exists() ): // if user session does not exist

                    switch( $session_command ):
                        case 'create':
                            \Module\User\Session::create( $params[0], $params[1] );
                            \Tools\Communicator::throw_result( $user_module_result[0] );
                            break;
                        default:
                            \Tools\Communicator::throw_error( $driver_error[4] ); // if no user session and token not requested
                            break;
                    endswitch;
                    
                else: // if user session exists
                    
                    switch( $session_command ):
                        case 'end':
                            \Module\User\Session::end();
                            \Tools\Communicator::throw_result( $user_module_result[1] );
                            break;
                        case 'run':
                            $command = \Tools\Request::read( 'cmd', $request );
                            if( !$command ):
                                \Tools\Communicator::throw_error( $driver_error[5] );
                            endif;
                            
                            $user = \Module\User\Session::load( $developer );
                            
                            switch ($command):
                                case 'data':
                                    switch( $params[0] ):
                                        case 'guard':
                                            \Tools\Communicator::throw_dump( $user->data->guard );
                                            break;
                                        case 'foro':
                                            if( $user->data->foro ):
                                                \Tools\Communicator::throw_dump( $user->data->foro );
                                            else:
                                                \Tools\Communicator::throw_error( $user_module_error[1] );
                                            endif;
                                            break;
                                        default:
                                            \Tools\Communicator::throw_error( $general_module_error[0] );
                                            break;
                                    endswitch;
                                    break;
                                case 'query':
                                    switch($params[0]):
                                        case 'identified':
                                            $gizmo_key = $params[1];
                                            if ( $user->data->guard['gizmo_key_1'] == $gizmo_key || $user->data->guard['gizmo_key_2'] == $gizmo_key ):
                                                \Tools\Communicator::throw_result( true );
                                            else:
                                                \Tools\Communicator::throw_result( false );
                                            endif;
                                            break;
                                        case 'admin':
                                            \Tools\Communicator::throw_result( $user->data->guard['admin'] );
                                            break;
                                        case 'developer':
                                            \Tools\Communicator::throw_result( $user->data->guard['developer'] );
                                            break;
                                        case 'banned':
                                            \Tools\Communicator::throw_result( $user->data->guard['banned'] );
                                            break;
                                    endswitch;
                                    break;
                                case 'bit.read':
                                    \Tools\Communicator::throw_result( $user->bit_read($params[0], $developer_key) );
                                    break;
                                case 'bit.update':
                                    $user->bit_update( $params[0], $params[1], $developer_key );
                                    \Tools\Communicator::throw_result( $user_module_result[3] );
                                    break;
                                case 'subscribe':
                                    $user->subscribe( $params[0] );
                                    \Tools\Communicator::throw_result( $user_module_result[4] );
                                    break;
                                case 'sub.access':
                                    \Tools\Communicator::throw_result( $user->sub_access( $params[0], $developer_key ) );
                                    break;
                                case 'credit':
                                    $action = \Tools\Request::read( 'act', $request );
                                    if( !$action ):
                                        \Tools\Communicator::throw_error( $user_module_error[2] ); // if action not specified
                                    endif;
                                    $secret = \Tools\Request::read( 'secret', $request );
                                    switch($action):
                                        case 'transfer':
                                            \Module\User\Credit::transfer( $user, $secret, $params[0], $params[1] );
                                            \Tools\Communicator::throw_result( $user_module_result[5] );
                                            break;
                                        default:
                                            \Tools\Communicator::throw_error( $driver_error[4] ); // invalid comman
                                            break;
                                    endswitch;
                                    break;
                                default:
                                    \Tools\Communicator::throw_error( $driver_error[4] ); // invalid command
                                    break;
                            endswitch;
                            break;
                        default:
                            \Tools\Communicator::throw_error( $driver_error[4] ); // invalid command
                            break;
                    endswitch;
                    
                endif;

            else: // suggests user is not using session

                // driver.php?mod=user&user=Veritas&phrase=...&cmd=...

                $username = \Tools\Request::read( 'user', $request );
                $phrase = \Tools\Request::read( 'phrase', $request );
                
                if( !$developer['admin'] ): // if developer is not admin, check for username + phrase
                    if( !$username || !$phrase ):
                        \Tools\Communicator::throw_error($general_module_error[0]);
                    endif;
                else: // if developer admin, only check for username
                    if( !$username ):
                        \Tools\Communicator::throw_error($general_module_error[0]);
                    endif;
                endif;
                
                $user = new \Module\User( $username, $phrase, null, $developer );

                $command = \Tools\Request::read( 'cmd', $request );
                if ( !$command ):
                    \Tools\Communicator::throw_error( $driver_error[5] );
                endif;
                
                switch ($command):
                    case 'data':
                        switch ( $params[0] ):
                            case 'guard':
                                \Tools\Communicator::throw_dump( $user->data->guard );
                                break;
                            case 'foro':
                                if ( $user->data->foro ):
                                    \Tools\Communicator::throw_dump( $user->data->foro );
                                else:
                                    \Tools\Communicator::throw_error( $user_module_error[1] );
                                endif;
                                break;
                            default:
                                \Tools\Communicator::throw_error( $general_module_error[0] );
                                break;
                        endswitch;
                    break;
                    case 'query':
                        switch($params[0]):
                            case 'identified':
                                $gizmo_key = $params[1];
                                if ( $user->data->guard['gizmo_key_1'] == $gizmo_key || $user->data->guard['gizmo_key_2'] == $gizmo_key ):
                                    \Tools\Communicator::throw_result( true );
                                else:
                                    \Tools\Communicator::throw_result( false );
                                endif;
                                break;
                            case 'admin':
                                \Tools\Communicator::throw_result( $user->data->guard['admin'] );
                                break;
                            case 'developer':
                                \Tools\Communicator::throw_result( $user->data->guard['developer'] );
                                break;
                            case 'banned':
                                \Tools\Communicator::throw_result( $user->data->guard['banned'] );
                                break;
                        endswitch;
                    break;
                    case 'bit.read':
                        \Tools\Communicator::throw_result( $user->bit_read( $params[0], $developer_key ) );
                        break;
                    case 'bit.update':
                        $user->bit_update( $params[0], $params[1], $developer_key );
                        \Tools\Communicator::throw_result( $user_module_result[3] );
                        break;
                    case 'subscribe':
                        $user->subscribe( $params[0] );
                        \Tools\Communicator::throw_result( $user_module_result[4] );
                        break;
                    case 'sub.access':
                        \Tools\Communicator::throw_result( $user->sub_access( $params[0], $developer_key ) );
                        break;
                    case 'credit':
                        $action = \Tools\Request::read('act', $request);
                        if (!$action):
                            \Tools\Communicator::throw_error($user_module_error[2]); // if action not specified
                        endif;
                        $secret = \Tools\Request::read( 'secret', $request );
                        switch ($action):
                            case 'transfer':
                                \Module\User\Credit::transfer( $user, $secret, $params[0], $params[1] );
                                \Tools\Communicator::throw_result( $user_module_result[5] );
                                break;
                        endswitch;
                        break;
                    default:
                        \Tools\Communicator::throw_error( $driver_error[4] ); // invalid command
                        break;
                endswitch;
            endif;
            break;
        case APP: // only accepts direct commands

            status_check(); // check if offline or maintenance
            
            // driver.php?mod=app&id=...&cmd=...

            $id = \Tools\Request::read( 'id', $request );
            if( !$id ):
                \Tools\Communicator::throw_error( $general_module_error[0] );
            endif;
            
            require_once ( get_include_path() . '/api/modules/app.php' );
            
            $app = new \Module\App( $id, $developer_key );

            $command = \Tools\Request::read( 'cmd', $request );
            if ( !$command ):
                \Tools\Communicator::throw_error( $driver_error[5] );
            endif;
            
            $params = catch_parameters();

            switch ($command):
                case 'data':
                    \Tools\Communicator::throw_dump( $app->data );
                    break;
                case 'read':
                    \Tools\Communicator::throw_result( $app->read( $params[0] ) );
                    break;
                case 'update':
                    $app->update( $params[0], $params[1] );
                    \Tools\Communicator::throw_result( $app_module_result[0] );
                    break;
				case 'delete':
					$app->delete( $params[0] );
					\Tools\Communicator::throw_result( $app_module_result[0] );
					break;
                default:
                    \Tools\Communicator::throw_error( $driver_error[4] ); // invalid command
                    break;
            endswitch;
            break;
        case SUBSCRIPTION: // only accepts direct commands

            status_check(); // check if offline or maintenance
            
            // driver.php?mod=subscription&id=...&cmd=...

            $id = \Tools\Request::read( 'id', $request );
            if( !$id ):
                \Tools\Communicator::throw_error( $general_module_error[0] );
            endif;
            
            require_once ( get_include_path() . '/api/modules/subscription.php' );
            
            $subscription = new \Module\Subscription( $id, $developer_key );

            $command = \Tools\Request::read( 'cmd', $request );
            if ( !isset($command) ):
                \Tools\Communicator::throw_error( $driver_error[5] );
            endif;
            
            $params = catch_parameters();
            
            switch ($command):
                case 'data':
                    \Tools\Communicator::throw_dump( $subscription->data );
                    break;
                case 'tag':
                    \Tools\Communicator::throw_result( $subscription->create_tag( $params[0], $params[1] ) );
                    break;
                case 'update':
                    $subscription->update( $params[0], $params[1] );
                    \Tools\Communicator::throw_result( $subscription_module_result[0] );
                    break;
                default:
                    \Tools\Communicator::throw_error( $driver_error[4] ); // invalid command
                    break;
            endswitch;
            break;
        case UTIL:

            $command = \Tools\Request::read( 'cmd', $request );
            if (!isset($command)):
                \Tools\Communicator::throw_error( $driver_error[5] );
            endif;
            
            $params = catch_parameters();
            
            switch ($command):
				case 'version':
					\Tools\Communicator::throw_result( \Module\Util::Version() );
					break;
                case 'online':
                    \Tools\Communicator::throw_result( \Module\Util::Online() );
                    break;
                case 'maintenance':
                    \Tools\Communicator::throw_result( \Module\Util::Maintenance() );
                    break;
                case 'beat':
                    \Tools\Communicator::throw_result( \Module\Util::Beat( $params[0] ) );
                    break;
                case 'blacklisted':
                    \Tools\Communicator::throw_result( \Module\Util::Blacklisted( $params[0] ) );
                    break;
                default:
                    \Tools\Communicator::throw_error( $driver_error[4] );
                    break;
            endswitch;
            break;
        default:
            \Tools\Communicator::throw_error( $driver_error[5] );
            break;
            
    endswitch;