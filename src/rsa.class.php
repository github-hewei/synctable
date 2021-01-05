<?php  //CODE BY ZMZ
// RSA 加解密类

class RSA{
    private $pi_key;
    private $pu_key;
    public function __construct( $pi_key, $pu_key) {
        $this->pi_key = openssl_pkey_get_private( $pi_key );
        $this->pu_key = openssl_pkey_get_public( $pu_key );
    }
    //------------------------------------------------------------------------------------
    //加密
    public function Encrypt( $src, $type='public' ) {
        $ret = '';
        $blocksize = 110;
        $curidx = 0;
        if( $type == 'public' ) {
            $key = $this->pu_key;
            $fnc = 'openssl_public_encrypt';
        } else if( $type == 'private' ) {
            $key = $this->pi_key;
            $fnc = 'openssl_private_encrypt';
        }
        $len = strlen( $src );
        $arr = array();
        do {
            $arr[] = substr( $src, $curidx, $blocksize );
            $curidx += $blocksize;
        } while( $curidx < $len );
        for( $i=0; $i<count( $arr ); $i++ ) {
            $_tmp = '';
            $fnc( $arr[$i], $_tmp, $key );
            if( $i!= 0 ) $ret .= "\n";
            $ret .= base64_encode( $_tmp );
        }
        return $ret;
    }
    //------------------------------------------------------------------------------------
    //解密
    public function Decrypt( $src, $type='private' ) {
        $arr = explode( "\n", $src );
        if( $type == 'public' ) {
            $key = $this->pu_key;
            $fnc = 'openssl_public_decrypt';
        } else if( $type == 'private' ) {
            $key = $this->pi_key;
            $fnc = 'openssl_private_decrypt';
        }
        $ret = '';
        foreach( $arr as $line ) {
            $_tmp = '';
            if( trim( $line ) != '' ) {
                $fnc( base64_decode( $line ), $_tmp, $key );
                $ret .= $_tmp;
            }
        }
        return $ret;
    }

}


?>