<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;
use app\common\validate\Common as Commonvalidate;
use think\Db;

/**
 * 会员模型
 * @package app\admin\model
 */
class User extends Model
{
	public function news()
	{
		return $this->hasMany('News','author');
	}
	/**
	 * 增加会员
     * @param string $username
     * @param string $salt
     * @param string $password
     * @param string $nickname
     * @param string $email
     * @param string $mobile
     * @param int $open
     * @param int $status
     * @param int $province
     * @param int $city
     * @param int $town
     * @param int $sex
     * @param string $user_url
     * @param string $signature
	 * @return mixed 0或会员id或错误信息
	 */
	public static function add($username,$salt='',$password,$nickname='',$email='',$mobile='',$open=0,$status=0,$province=0,$city=0,$town=0,$sex=3,$user_url='',$signature='')
	{
        $validate=new Commonvalidate();
        $sldata=array(
            'username'=>$username,
            'password'=>$password,
            'email'=>$email,
            'mobile'=>$mobile
        );
        if (!$validate->scene('user')->check($sldata)) {
            return $validate->getError();
        }else{
            $salt=$salt?:random(10);
            $sldata['pwd_salt']=$salt;
            $sldata['password']=encrypt_password($password,$salt);
            $sldata['nickname']=$nickname;
            $sldata['open']=$open;
            $sldata['last_ip']=request()->ip();
            $sldata['create_time']=time();
            $sldata['last_time']=time();
            $sldata['status']=$status;
            $sldata['province']=$province;
            $sldata['city']=$city;
            $sldata['town']=$town;
            $sldata['sex']=$sex;
            $sldata['user_url']=$user_url;
            $sldata['signature']=$signature;
            $user=self::create($sldata);
            if($user){
                return intval($user['id']);
            }else{
                return 0;
            }
        }
	}
    /**
     * 增加会员
     * @param int $id
     * @return bool
     */
    public static function del($id){
        $user = self::get($id,'news');
        return $user ->together('news')->delete();
    }
}
