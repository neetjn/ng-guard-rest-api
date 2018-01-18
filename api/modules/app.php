<?php namespace Module;

    class App {

        private $handle, $developer;
        
        public $data;
        
        private static $general_error = array(
            0 => 'Insufficient Parameter(s)',
            1 => 'Server Cannot Be Reached',
            2 => 'An Internal Error Has Occurred'
        );
        
        private static $app_error = array(
            0 => 'App Does Not Exist',
            1 => 'Specified Field Cannot Be Written To'
        );

        private static $developer_error = array(
            0 => 'Developer Access Not Authorized'
        );

        public function __construct($identifier, $key) {
            $this->handle = $identifier;
            
            $app = \Tools\SQL::fetch_row( "SELECT * FROM `apps` WHERE `id`='$this->handle'" );
            if( !$app ):
                \Tools\Communicator::throw_error( self::$app_error[0] ); // app does not exist
            endif;

            $data = (array) json_decode( $app['data'] );
            $this->data = $data;
            
            $this->developer = $key == $app['key'];
        } // app key as developer key, handle as app identifier

        public function read($field) {
            /*
             * when creating new app, set default data value to
             * {"title":&title}
             */
            return $this->data[strtolower( $field )];
        }

        public function update($field, $update) {
            if( !$this->developer ):
                \Tools\Communicator::throw_error( self::$developer_error[0] ); // if developer does not own project
            endif;
            if( !$field || \Tools\Utility::str_has_special_char( $field ) || strpos($field, ' ') || strlen($field) <= 2 ):
                \Tools\Communicator::throw_error( self::$general_error[0] );
            endif;
            $field = strtolower( $field );
            if( $field !== 'title' ):
                $this->data[$field] = $update;
                $data = json_encode( $this->data );
                \Tools\SQL::query( "UPDATE `apps` SET `data` = '$data' WHERE `id` = '$this->handle'" );
            else:
                \Tools\Communicator::throw_error( self::$app_error[1] ); // developer cannot update title field
            endif;
        }
		
		public function delete($field) {
            if( !$this->developer ):
                \Tools\Communicator::throw_error( self::$developer_error[0] ); // if developer does not own project
            endif;
            $field = strtolower( $field );
            if( $field !== 'title' ):
                unset( $this->data[$field] );
                $data = json_encode( $this->data );
                \Tools\SQL::query( "UPDATE `apps` SET `data` = '$data' WHERE `id` = '$this->handle'" );
            else:
                \Tools\Communicator::throw_error( self::$app_error[1] ); // developer cannot delete title field
            endif;
        }

    }

?>