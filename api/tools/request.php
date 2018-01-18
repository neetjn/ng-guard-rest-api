<?php namespace Tools;

    class Request {
        
        public static function type($var) {
            if (!$_POST[$var] && !$_GET[$var]):
                return null; // return null by default
            elseif (!$_POST[$var]):
                return 'GET'; // return post if not get
            elseif (!$_GET[$var]):
                return 'POST'; // return get if not post
            endif;
        }
        
        public static function read($var, $request) {
            switch($request):
                
                case 'GET':
                    return $_GET[$var];
                case 'POST':
                     return $_POST[$var];
                default:
                    return null;
                
            endswitch;
        }

    }

?>