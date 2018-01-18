<?php namespace Module\User;
    
    class Utility {

        private static $user_error = array(
            0 => 'User Does Not Exist', 
            1 => 'User Authentication Failure',
            2 => 'Invalid User Token',
            3 => 'This User Is Banned From Guard Services',
            4 => 'User Login Timed Out',
            5 => 'User Is Currently Timed Out',
            6 => 'This Account Has Been Disabled',
            7 => 'Invalid Authentication Phrase'
        );
        
        public static function profile($handle) {
            if( is_numeric( $handle ) ): // handle as user id
                return \Tools\SQL::fetch_row( "SELECT * FROM `users` WHERE `id`='$handle'" );
            else: // handle as username
                return \Tools\SQL::fetch_row( "SELECT * FROM `users` WHERE `username`='$handle'" );
            endif;
        } // handle as identifier - user id or username
        
        public static function identify($username) {
            $user = self::profile( $username );
            if ( !$user ):
                \Tools\Communicator::throw_error( self::$user_error[0] );
            endif;
            return (int) $user['id'];
        }
        
        public static function log($handle, $event) {
            $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] === $_SERVER['SERVER_ADDR'] ? 'localhost' : $_SERVER['HTTP_CF_CONNECTING_IP']; // using cloudflare to find visitor ip
            if( is_numeric($handle) ):
                \Tools\SQL::query( "INSERT INTO `user_log` (`user_id`, `ip`, `event`, `time`) VALUES ('$handle', '$ip_address', '$event', CURRENT_TIMESTAMP)" ); // log event
            endif;
        } // handle as identifier - user id
        
        public static function authenticate($username, $phrase) {
            function timed_out($handle) {
                $timeout_data = \Tools\SQL::fetch_row( "SELECT * FROM `timeout` WHERE `time` = ( SELECT MAX(time) from `timeout` WHERE `user_id` = '$handle' )" );
                if ( !$timeout_data ):
                    return false; // assume never timed out
                endif;
                $timeout = $timeout_data['time'];
                return strtotime($timeout) + (60 * (5)) > time(); // 60 seconds * 5 = 5 minutes, if timed out within past 5 mins
            } // handle as user id

            function locked($handle) {
                $count = 0;
                $failed_logins = \Tools\SQL::query( "SELECT * FROM `failed_logins` WHERE `user_id`='$handle'" );
                while ( $row = \Tools\SQL::fetch_row( $failed_logins, true ) ):
                    $time = $row['time'];
                    if ( strtotime($time) + (60 * 5) > time() ): // 60 seconds * 5 = 5 minutes, if failed within the past 5 mins
                        $count++; // increment failed logins
                        if ($count >= 10): // if failed 10+ times, return false
                            \Tools\SQL::query( "INSERT INTO `timeout` ( `user_id`, `time` ) VALUES ( '$handle', CURRENT_TIMESTAMP )" );
                            return false;
                        endif;
                    endif;
                endwhile;
                return true;
            } // handle as user id

            $user = self::profile( $username );
            if (!$user):
                \Tools\Communicator::throw_error( self::$user_error[0] );
            endif;
            
            if ( strlen( $phrase ) !== 8 ): // phrase must be 8 characters long
                \Tools\Communicator::throw_error( self::$user_error[7] );
            endif;
            
            $disabled = (int) $user['disabled'] === 1 ? true : false;
            if ($disabled):
                \Tools\Communicator::throw_error( self::$user_error[6] );
            endif;
            
            $banned = (int) $user['banned'] === 1 ? true : false;
            if ($banned):
                \Tools\Communicator::throw_error( self::$user_error[3] );
            endif;

            $handle = (int) $user['id']; // handle as user id

            if ( !timed_out( $handle ) ):
                if ( !locked( $handle ) ):
                    \Tools\Communicator::throw_error( self::$user_error[4] );
                endif;
            else:
                \Tools\Communicator::throw_error( self::$user_error[5] );
            endif;

            $salt = $user['salt'];
            $phrase = \Tools\Encoding::Encode( $phrase . $salt );
            $pass = $user['auth_phrase'];
            
            $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] === $_SERVER['SERVER_ADDR'] ? 'localhost' : $_SERVER['HTTP_CF_CONNECTING_IP']; // using cloudflare to find visitor ip
            
            if ( $pass !== $phrase ):
                self::log($handle, 1); // log auth. fail
                \Tools\SQL::query( "INSERT INTO `failed_logins` (`user_id`, `ip`, `time`) VALUES ('$handle', '$ip_address', CURRENT_TIMESTAMP)" ); // log failed login
                return false;
            else:
                self::log($handle, 2); // log authentication
                \Tools\SQL::query( "UPDATE `users` SET `last_login` = CURRENT_TIMESTAMP WHERE `id` = '$handle'" ); // set last_login
                return true;
            endif;
        }

    }
    
    class Session {
        
        private static $general_error = array(
            0 => 'Insufficient Parameter(s)',
            1 => 'Server Cannot Be Reached',
            2 => 'An Internal Error Has Occurred'
        );
        
        private static $session_error = array(
            0 => 'User Authentication Failure',
            1 => 'Session Does Not Exist'
        );
        
        public static function get($sesh_id) {
            return \Tools\SQL::fetch_row( "SELECT * FROM `sessions` WHERE `id` = '$sesh_id'" );
        }
        
        public static function exists() {
            if ( $_COOKIE['GUARD_SESHID'] ):
                $sesh_id = $_COOKIE['GUARD_SESHID'];
                $session = self::get( $sesh_id ); // fetch session data
                if( !$session ):
                    return false; // no result found
                else:
                    if( strtotime( $session['created'] ) + ((60*60)*24) > time() ):
                        return true; // session exists and is valid
                    else: // session begun over 24 hours ago
                        self::end();
                        return false; // session ended
                    endif;
                endif;
            else:
                return false; // no active session found
            endif;
        }
        
        public static function create($username, $phrase) {
            if( \Module\User\Utility::authenticate( $username, $phrase ) ):
                $token = substr( str_shuffle( 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ), 0, 25 ); // generate unique token
                setcookie( 'GUARD_SESHID', $token, ( time() + ( 60*60 ) * 24 ) ); // create local cookie
                $handle = \Module\User\Utility::identify( $username ); // handle as user id
                \Tools\SQL::query( "INSERT INTO `sessions` (`id`, `user_id`, `created`) VALUES ('$token', '$handle', CURRENT_TIMESTAMP)" ); // create session
            else:
                \Tools\Communicator::throw_error( self::$session_error[0] ); // authentication failure
            endif;
        }
        
        public static function end() {
            $sesh_id = $_COOKIE['GUARD_SESHID'];
            if( $sesh_id ):
                \Tools\SQL::query( "UPDATE `sessions` SET `active` = 0 WHERE `id` = '$sesh_id'" ); // set active to false
                setcookie( 'GUARD_SESHID', null, -1 );
                $session = self::get( $sesh_id );
                $handle = $session['user_id']; // handle as user id
                return true;
            else:
                return false;
            endif;
        }
        
        public static function load($developer) {
            if( self::exists() ):
                return new \Module\User(null, null, true, $developer);
            else:
                \Tools\Communicator::throw_error( self::$session_error[1] ); // session does not exist
            endif;
        }
        
    }
    
    class Subscription {
        
        private static $subscription_error = array(
            0 => 'Subscription Identifier Not Matched'
        );
        
        public static function key($identifier) {
            $subscription = \Tools\SQL::fetch_row( "SELECT * FROM `subscriptions` WHERE `id`='$identifier'" );
            if( !$subscription ):
                \Tools\Communicator::throw_error( self::$subscription_error[0] );
            else:
                return $subscription['key'];
            endif;
        }
        
        public static function data($identifier) {
            $subscription = \Tools\SQL::fetch_row("SELECT * FROM `subscriptions` WHERE `id` = '$identifier'");
            if(!$subscription):
                \Tools\Communicator::throw_error( self::$subscription_error[0] );
            endif;
            $data = (array) json_decode( $subscription['data'] );
            return $data;
        }
        
    }
    
    class Credit {
        
        private static $credit_error = array(
            0 => 'User Authentication Failure'
        );
        
        public static function transfer($user, $secret, $credit, $recipient) {
            $handle = array(
                'guard' => $user->data->guard['username'],
                'foro' => $user->data->foro['username']
            ); // handle as username
            if( $secret !== $user->data->credit['secret'] ):
                \Tools\Communicator::throw_error( self::$credit_error[0] );
            endif;
            $foro_username = $handle['foro'];
            $result = json_decode( file_get_contents("https://forum.neetgroup.net/guard/api/driver.php?mod=user&cmd=credit&act=transfer&a=$foro_username&b=$secret&c=$credit&d=$recipient") );
            if( $result->error ):
                \Tools\Communicator::throw_error( $result->error );
            endif;
            $username = $handle['guard'];
            $user = \Module\User\Utility::profile( $username );
            $secret = \Tools\Encoding::Encode( substr( str_shuffle( 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ), 0, 12 ) . $user['salt'] ); // unique 8 character alpha-numeric key
            \Tools\SQL::query( "UPDATE `users` SET `cred_secret` = '$secret' WHERE `username` = '$username'" );// update secret
            if( \Module\User\Session::exists() ):
                \Module\User\Session::end(); // wipe session if exists
            endif;
            \Module\User\Utility::log( $username, '<API> CREDIT TRANSFER' ); // log transfer
            return true;
        }
        
    }

?>

<?php namespace Module;
    
    use \stdClass;
    
    class User {
        
        private static $general_error = array(
            0 => 'Insufficient Parameter(s)',
            1 => 'Server Cannot Be Reached',
            2 => 'An Internal Error Has Occurred'
        );

        private static $user_error = array(
            0 => 'User Does Not Exist', 
            1 => 'User Authentication Failure',
            2 => 'Invalid User Token',
            3 => 'This User Is Banned From Guard Services',
            4 => 'User Login Timed Out',
            5 => 'User Is Currently Timed Out',
            6 => 'Invalid Authentication Phrase'
        );

        private static $bit_error = array(
            0 => 'Bit Class Does Not Exist',
            1 => 'Bit Class Key Does Not Match'
        );
        
        private static $subscription_error = array(
            0 => 'Subscription Identifier Not Matched',
            1 => 'Subscription Key Not Matched',
            2 => 'Subscription Nomenclature Not Matched',
            3 => 'User Not Subscribed',
            4 => 'User Already Subscribed',
            5 => 'Tag Does Not Exist',
        );
        
        public $handle, $data;

        public function __construct($username, $phrase, $session, $developer) {
            if( !$username && !$phrase && !$session ):
                \Tools\Communicator::throw_error( self::$general_error[0] );
            else:
                if( !$session ):
                    if( !$username && !$phrase ):
                        \Tools\Communicator::throw_error( self::$general_error[0] );
                    endif;
                endif;
            endif;
            
            if( !$session ):
                if( !$developer['admin'] ): // admin permission not found, authenticate credentials
                    if( !\Module\User\Utility::authenticate( $username, $phrase ) ):
                        \Tools\Communicator::throw_error( self::$user_error[1] ); // authentication failure
                    endif;
                    $this->handle = \Module\User\Utility::identify( $username );
                    $this->fetch_data();
                else: // admin permission found, do not authenticate credentials
                    $this->handle = \Module\User\Utility::identify( $username );
                    $this->fetch_data();
                endif;
            else:
                $sesh_id = $_COOKIE['GUARD_SESHID'];
                $session = \Module\User\Session::get( $sesh_id ); // fetch session data
                $this->handle = (int) $session['user_id'];
                $this->fetch_data();
                if( $this->data->guard['banned'] ):
                    \Module\User\Session::end();
                    \Tools\Communicator::throw_error( self::$user_error[3] );    
                endif; // user is banned, end session
            endif;
        }

        public function fetch_data() {
            $user = \Module\User\Utility::profile( $this->handle );

            $guard_data = array(
                'username' => $user['username'],
                'alias' => $user['alias'],
                'last_login' => (int) strtotime( $user['last_login'] ),
                'gizmo_key_1' => $user['key_1'],
                'gizmo_key_1_updated' => (int) strtotime( $user['key_1_updated'] ),
                'gizmo_key_2' => $user['key_2'],
                'gizmo_key_2_updated' => (int) strtotime( $user['key_2_updated'] ),
                'admin' => (int) $user['admin'] === 1?true:false,
                'developer' => (int) $user['developer'] === 1?true:false,
                'banned' => (int) $user['banned'] === 1?true:false,
                'use_email' => (int) $user['use_email'] === 1 ? true:false
            );
            
            $credit_data = array(
                'secret' => str_replace( $user['salt'], null, \Tools\Encoding::Decode( $user['cred_secret'] ) )  
            );
            
            $bits = (array) json_decode( $user['bit_data'] );
            
            $subs = (array) json_decode( $user['sub_data'] );
            
            $foro_handle = (int) $user['f_id'];
            $foro_data; // initialize foro data at null

            if( $foro_handle !== 0 ): // only load forum data, if id is valid

                $result = json_decode( file_get_contents( "https://forum.neetgroup.net/guard/api/driver.php?mod=user&cmd=data&a=$foro_handle" ) );
                $foro_data = array(
                    'avatar' => $result->avatar,
                    'username' => $result->username,
                    'email' => $result->email,
                    'message_count' => $result->message_count,
                    'conversations_unread' => $result->conversations_unread,
                    'alerts_unread' => $result->alerts_unread,
                    'register_date' => $result->register_date,
                    'last_activity' => $result->last_activity,
                    'staff_member' => $result->staff_member,
                    'moderator' => $result->moderator,
                    'administrator' => $result->administrator,
                    'warning_points' => $result->warning_points,
                    'trophy_points' => $result->trophy_points,
                    'credits' => $result->credits
                );
                
            endif;
            
            $this->data = new stdClass();
            $this->data->guard = $guard_data;
            $this->data->credit = $credit_data;
            $this->data->bit = $bits;
            $this->data->subscriptions = $subs;
            $this->data->foro = $foro_data;
        }

        public function bit_read($field, $key) {
            $field = strtolower( $field );
            
            return $this->data->bit[$key]->$field; // return null if not found
        } // bit key as developer key

        public function bit_update($field, $update, $key) {
            $field = strtolower($field);
            
            $this->data->bit[$key]->$field = $update;
            $bits = json_encode( $this->data->bit );
            
            \Tools\SQL::query( "UPDATE `users` SET `bit_data` = '$bits' WHERE `id`='$this->handle'" );
        } // bit key as developer key
        
        public function subscribe($tag) {
            $tag = \Tools\SQL::fetch_row("SELECT * FROM `tags` WHERE `tag` = '$tag'");
            if( !$tag ):
                \Tools\Communicator::throw_error( self::$subscription_error[5] ); // tag does not exist
            else:
                $nomenclature;
                
                foreach( str_split( $tag['tag'] ) as $c ):
                    if( $c !== '_' ):
                        $nomenclature .= $c;
                    else:
                        break;
                    endif;
                endforeach;
                
                $subscription;
    
                $subscriptions = \Tools\SQL::query( "SELECT * FROM `subscriptions`" ); // query all subscriptions
                while( $row = \Tools\SQL::fetch_row( $subscriptions, true ) ):
                    $s = (array) json_decode( $row['data'] );
                    if( $s['nomenclature'] !== $nomenclature ):
                        continue;
                    else:
                        $subscription = $row;
                        break;
                    endif;
                endwhile;
                
                if( !$subscription ):
                    \Tools\Communicator::throw_error( self::$subscription_error[2] );
                else:
                    if( !$this->data->subscriptions[ $subscription['id'] ] OR !$this->data->subscriptions[ $subscription['id'] ]->access ):
                        $data = (array)json_decode( $subscription['data'] );
                        $n;
                        if( $data['expires'] ):
                            $expires_on = time() + ( $tag['exp_days'] * ( 60*60*24 ) );
                            $n = array(
                                'access' => true,
                                'expires_on' => $expires_on
                            );
                        else: // subscription does not expire
                            $n = array(
                                'access' => true
                            );
                        endif;
                        $this->data->subscriptions[ $subscription['id'] ] = $n;
                        $e =  json_encode( $this->data->subscriptions );
                        \Tools\SQL::query("UPDATE `users` SET `sub_data` = '$e' WHERE `id` = '$this->handle'"); // set subscription status
                        $t = $tag['tag'];
                        \Tools\SQL::query("DELETE FROM `tags` WHERE `tag` = '$t'"); // delete tag
                        if( $this->data->guard['use_email'] && $this->data->foro ):
                            $to = $this->data->foro['email'];
                            
                            $secure_id = str_split( $subscription['id'] );
                            for( $i=0;$i<15;$i++ ):
                                $secure_id[$i] = '#';
                            endfor;
                            $secure_id = implode( $secure_id );
                            
                            $subject = "Subscription Confirmation - $secure_id";
                            
                            $username = $this->data->guard['username'];
                            
                            $message = "
                            <center>
                            	<div class='body' style='display:block; background-color:#fff; width:600px; overflow:auto; margin:0; padding:0;'>
                            		<img src='http://s28.postimg.org/av4fsnya5/wrapper.png' style='display:block; margin:0; padding:0;' />
                            		<div class='wrapper' style='cursor:default; display:block; margin:0; padding:0;'>
                            			<p class='message' style='display:block; margin:0; padding:15px; text-align:left; border-bottom:2px solid #3b3d3e; border-left:3px solid #3b3d3e; border-right:3px solid #3b3d3e;'>
                            			Hello `<b><u>$username</u></b>`,<br />
                            			We&#39;re sending you this message as confirmation that you have subscribed to subscription <i>$secure_id</i> ; <b>$t</b>.
                            			</p>
                            		</div>
                            	</div>
                            </center>
                            ";
                            
                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                            $headers .= 'From: do-not-reply@neetgroup.net' . "\r\n";
                            $headers .= "Cc: $to" . "\r\n";
                            
                            mail($to, $subject, $message, $headers);
                        endif;
                    else:
                        \Tools\Communicator::throw_error( self::$subscription_error[4] ); // user already subscribed
                    endif;
                endif;
            endif;
        }
        
        public function sub_access($identifier, $key) {
            if( \Module\User\Subscription::key( $identifier ) !== $key ):
                \Tools\Communicator::throw_error( self::$subscription_error[1] ); // invalid developer credentials
            else:
                $subscription = \Module\User\Subscription::data( $identifier );
                if( !$this->data->subscriptions[$identifier] ):
                    \Tools\Communicator::throw_error( self::$subscription_error[3] );
                else:
                    $data = $this->data->subscriptions[$identifier];
                    /*
                     *
                     
                     $data =
                     {
                        "access" : true || false,
                        "expires_on" : timestamp
                     }
                     
                     */
                     
                    if( !$data ):
                        return false;
                    else:
                        if($data->access):
                            if( (int) $data->expires_on < time() ):
                                $subs = $this->data->subscriptions;
                                $subs[$identifier]->access = false;
                                unset( $subs[$identifier]->expires_on );
                                $subs = json_encode( $subs );
                                \Tools\SQL::query( "UPDATE `users` SET `sub_data` = '$subs' WHERE `id`='$this->handle'" );
                                
                                return false;
                            else:
                                return true;
                            endif;
                        else:
                            return false;
                        endif;
                    endif;
                endif;
            endif;
        } // key as developer key
        
    }
    
?>