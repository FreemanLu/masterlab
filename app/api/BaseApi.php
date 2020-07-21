<?php

namespace main\app\api;

use main\app\classes\B2bcrypt;
use main\app\classes\Sign;
use main\app\model\user\UserModel;
use main\lib\phpcurl\Curl;

/**
 * api 基类， 提供对外接口的接入操作
 *
 * @author jesen
 *
 */
class BaseApi
{

    /**
     * 允许的请求方式
     * */
    protected static $method_type = array('get', 'post', 'put', 'patch', 'delete');

    protected $requestMethod = null;
    protected $masterUid = 0;

    /**
     * 参数处理
     */
    public function __construct()
    {
        $this->requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $userModel = new UserModel();
        $user = $userModel->getByUsername('master');
        $this->masterUid = $user['uid'];
    }

    protected static function returnHandler($msg = '', $body = [], $code = Constants::HTTP_OK)
    {
        $ret = [];
        $ret['msg'] = $msg;
        $ret['code'] = $code;
        $ret['body'] = $body;

        return $ret;
    }

    /**
     * 模拟PATCH请求方法
     * @return array
     */
    protected static function _PATCH()
    {
        $reqDataArr = [];
        $reqData = file_get_contents('php://input');
        parse_str($reqData, $reqDataArr);
        return $reqDataArr;
    }

    protected function validateRestfulHandler( )
    {
        foreach( self::$method_type as $method ) {
            if(  !method_exists( $this,$method . 'Handler') ) {
                throw new \Exception( 'Restful '.$method . 'Handler not exists',500 );
            }
        }
    }

}
