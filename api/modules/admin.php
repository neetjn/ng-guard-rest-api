<?php namespace Module;

    class Admin {

        private static $general_error = array(
            0 => 'Insufficient Parameter(s)',
            1 => 'Server Cannot Be Reached',
            2 => 'An Internal Error Has Occurred'
        );

        private static $admin_error = array(
            0 => 'User Does Not Exist',
            1 => 'User Is Already Banned',
            2 => 'User Is Already Blacklisted',
            3 => 'Invalid Server Status Input',
            4 => 'Invalid Filter Input',
            5 => 'Address Already Filtered'
        );

        public static function Ban($handle) {
            $user = \Tools\SQL::fetch_row( "SELECT * FROM `users` WHERE `username` = '$handle'" );
            if ( !$user ):
                \Tools\Communicator::throw_error( self::$admin_error[0] ); // user does not exist
            endif;
            $banned = (int) $user['banned'] === 1 ? true : false;
            if ( $banned ):
                \Tools\Communicator::throw_error( self::$admin_error[1] ); // user is already banned
            endif;
            \Tools\SQL::query( "UPDATE `users` SET `banned` = '1' WHERE `username` = '$handle'" ); // ban user
        } // handle as username

        public static function Blacklist($input, $user = false) {
            if( $user ): // search for user's keys and ban
                $profile = \Tools\SQL::fetch_row("SELECT * FROM `users` WHERE `username` = '$input'");
                if ( !$profile ):
                    \Tools\Communicator::throw_error( self::$admin_error[0] ); // user does not exist
                endif;

                $key_1 = $profile['key_1'];
                if( $key_1 !== '0' ):
                    $check = \Tools\SQL::fetch_row( "SELECT * FROM `black_list` WHERE `gizmo_key` = '$key_1'" );
                    if ( !$check ):
                        \Tools\SQL::query( "INSERT INTO `black_list` (`gizmo_key`) VALUES ('$key_1')" ); // add key to blacklist
                    endif;
                endif;
                $key_2 = $profile['key_2'];
                if( $key_2 !== '0' ):
                    $check = \Tools\SQL::fetch_row( "SELECT * FROM `black_list` WHERE `gizmo_key` = '$key_2'" );
                    if ( !$check ):
                        \Tools\SQL::query( "INSERT INTO `black_list` (`gizmo_key`) VALUES ('$key_2')" ); // add key to blacklist
                    endif;
                endif;
            else: // ban specific gizmo key
                $check = \Tools\SQL::fetch_row( "SELECT * FROM `black_list` WHERE `gizmo_key` = '$input'" );
                if (!$check):
                    \Tools\SQL::query( "INSERT INTO `black_list` (`gizmo_key`) VALUES ('$input')" ); // add key to blacklist
                else:
                    \Tools\Communicator::throw_error( self::$admin_error[2] ); // already blacklisted
                endif;
            endif;
        }
        
        public static function Filter($ip_address) {
            if( !$ip_address ):
                $ip_address = $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'] ? false : $_SERVER['REMOTE_ADDR'];
                if( !$ip_address ):
                    Tools\Communicator::throw_error( self::$general_error[2] );
                else:
                    \Tools\SQL::query( "INSERT INTO `ip_filter` (`ip_address`,`time`) VALUES ('$ip_address',CURRENT_TIMESTAMP)" );
                endif;
            else:
                if( $ip_address !== $_SERVER['SERVER_ADDR'] ):
                    \Tools\SQL::query( "INSERT INTO `ip_filter` (`ip_address`,`time`) VALUES ('$ip_address',CURRENT_TIMESTAMP)" );
                else:
                    \Tools\Communicator::throw_error( self::$admin_error[4] );
                endif;
            endif;
        }

        public static function Server($status) {
            if($status !== 'on' && $status !== 'off'):
                \Tools\Communicator::throw_error( self::$admin_error[3] );
            endif;

            $config = simplexml_load_string('../guard.xml');
            switch($status):
                case 'on':
                    $config->setting->online = 'true';
                    break;
                case 'off':
                    $config->setting->online = 'false';
                    break;
            endswitch;
            $config->asXml('../guard.xml');
        }
    }

?>