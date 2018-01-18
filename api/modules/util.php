<?php namespace Module;

    class Util {
		
		public static function Version() {
			$config = simplexml_load_file('../guard.xml');
            return $config->settings->version;
		}
		
        public static function Online() {
            $config = simplexml_load_file('../guard.xml');
            return \Tools\Utility::str_to_bool( $config->settings->online );
        }

        public static function Maintenance() {
            $config = simplexml_load_file('../guard.xml');
            return \Tools\Utility::str_to_bool( $config->settings->maintenance );
        }

        public static function Beat($key) {
            return \Tools\Encoding::Encode( self::Online() === true?'online':'offline', $key );
        }

        public static function Blacklisted($gizmo_key) {
            return \Tools\SQL::fetch_row( "SELECT * FROM `black_list` WHERE `gizmo_key` = '$gizmo_key'" );
        }
        
        public static function Filtered() {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $result = \Tools\SQL::fetch_row( "SELECT * FROM `ip_filter` WHERE `ip` = '$ip_address'" );
            if( $result ):
                return true;
            else:
                return false;
            endif;
        }

    }

?>