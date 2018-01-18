<?php namespace Module;

    class Subscription {

        private $handle, $developer;
        
        public $data;

        private static $subscription_error = array(
            0 => 'Subscription Does Not Exist',
            1 => 'Subscription Nomenclature Not Found',
            2 => 'Specified Field Does Not Exist',
            3 => 'Customer Does Not Exist'
        );

        private static $developer_error = array(
            0 => 'Developer Access Not Authorized'
        );

        private static $update_error = array(
            0 => '---', // reserved
            1 => 'Invalid Access Parameter',
            2 => 'Invalid Expiration Parameter',
            3 => 'Invalid Nomenclature Parameter',
            4 => 'Nomenclature Already In Use'
        );

        public function __construct($identifier, $key) {
            $this->handle = $identifier;
            
            $subscription = \Tools\SQL::fetch_row( "SELECT * FROM `subscriptions` WHERE `id`='$this->handle'" );
            if( !$subscription ):
                \Tools\Communicator::throw_error( self::$subscription_error[0] );
            endif;
            
            $data = (array) json_decode( $subscription['data'] );
            $this->data = $data;
            
            $this->developer = $key == $subscription['key'];
        } // subscription key as developer key, handle as subscription identifier

        public function create_tag($expiration, $customer) {
            if( !$this->developer ):
                \Tools\Communicator::throw_error( self::$developer_error[0] );
            endif;
            
            $customer = \Tools\SQL::fetch_row( "SELECT * FROM `users` WHERE `username` = '$customer'" );
            if( !$customer ):
                \Tools\Communicator::throw_error( self::$subscription_error[3] );
            endif;
            
            $expiration = (int) $expiration; // cast to integer

            if( !$this->data['expires'] ):
                $expiration = 0;
            else:
                if( $expiration <= 0 ):
                    $expiration = 1;
                endif;
            endif;

            if( !$this->data['nomenclature'] ):
                \Tools\Communicator::throw_error( self::$subscription_error[1] );
            else:
                $nomenclature = $this->data['nomenclature']; // nomenclature must be a min of three chars
            endif;

            $tag = $nomenclature . '_' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 10); // generate 10 char unique alphanumeric tag
            
            $subscription = $this->handle;
            
            $user_handle = $customer['id'];
            
            \Tools\SQL::query( "INSERT INTO `tags` (`tag`, `subscription`, `exp_days`, `customer`) VALUES ('$tag', '$subscription', '$expiration', '$user_handle')" ); // create tag
            
            $send_email = (int) $customer['use_email'] === 1?true:false;
			if( $send_email ):
	            $foro_handle = (int) $customer['f_id'];
	            if( $foro_handle !== 0 ):
	                $result = json_decode( file_get_contents( "http://forum.neetgroup.net/guard/api/driver.php?mod=user&cmd=data&a=$foro_handle" ) );
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
	                
	                $to = $foro_data['email'];
	                
	                $secure_id = str_split( $this->handle );
	                for( $i=0;$i<15;$i++ ):
	                    $secure_id[$i] = '#';
	                endfor;
	                $secure_id = implode( $secure_id );
	                
	                $subject = "Package Information - $secure_id";
	                
	                $username = $customer['username'];
	                            
	                $message = "
	                <center>
	                    <div class='body' style='display:block; background-color:#fff; width:600px; overflow:auto; margin:0; padding:0;'>
	                        <img src  ='http://s28.postimg.org/av4fsnya5/wrapper.png' style='display:block; margin:0; padding:0;' />
	                        <div class='wrapper' style='cursor:default; display:block; margin:0; padding:0;'>
	                            <p class  ='message' style='display:block; margin:0; padding:15px; text-align:left; border-bottom:2px solid #3b3d3e; border-left:3px solid #3b3d3e; border-right:3px solid #3b3d3e;'>
	                            Hello `<b><u>$username</u></b>`,<br />
	                            We&#39;re sending you this message as confirmation of your newly generated subscription tag, <b>$tag</b>.
	                            This tag is linked to subscription - <i>$secure_id</i>.
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
	            endif;
			endif;

            return $tag;
        }

        public function update($field, $update) {
            if( !$this->developer ):
                \Tools\Communicator::throw_error( self::$developer_error[0] );
            endif;

            switch($field):
                case 'public':
                    $update = (int) $update;
                    if( $update !== 0 && $update !== 1 ):
                        \Tools\Communicator::throw_error( self::$update_error[1] );
                    endif;
                    $this->data['public'] = $update === 1?true:false; // change public status
                    $data = json_encode( $this->data );
                    \Tools\SQL::query( "UPDATE `subscriptions` SET `data` = '$data' WHERE `id` = '$this->handle'" );
                    break;
                case 'expires':
                    $update = (int) $update;
                    if( $update !== 0 && $update !== 1 ):
                        \Tools\Communicator::throw_error( self::$update_error[2] );
                    endif;
                    $this->data['expires'] = $update === 1?true:false; // change expire status
                    $data = json_encode( $this->data );
                    \Tools\SQL::query( "UPDATE `subscriptions` SET `data` = '$data' WHERE `id` = '$this->handle'" );
                    break;
                default:
                    \Tools\Communicator::throw_error( self::$subscription_error[2] );
                    break;
            endswitch;
        } // field expected as `public` || `expires` || `nomenclature`

    }

?>