<?php namespace Module;

    class Developer {
        
        public static function who($key) {
            $user = mysql_fetch_assoc( mysql_query( "SELECT * FROM `users` WHERE `dev_key`='$key'" ) );
            $developer; // initialize as null
            if( $user ):
                if( (int) $user['banned'] !== 1 ):   
                    $developer = array(
                        'user_id' => (int) $user['id'],
                        'username' => $user['username'],
                        'banned' => (int)$user['banned'] === 1?true:false,
                        'admin' => (int)$user['admin'] === 1?true:false
                    );
                endif;
            endif;
            return $developer;
        }
        
    }

?>
