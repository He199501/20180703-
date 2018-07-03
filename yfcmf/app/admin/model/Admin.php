<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;
use app\common\validate\Common as Commonvalidate;
use app\common\model\User as UserModel;
use think\Db;

/**
 * 后台用户模型
 * @package app\admin\model
 */
class Admin extends Model
{

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public function groups()
    {
        return $this->belongsToMany('AuthGroup','auth_group_access','group_id','uid');
    }
    /**
     * 用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param bool $rememberme 记住登录
     * @return bool|mixed
     */
    public function login($username = '', $password = '', $rememberme = false)
    {
        $username = trim($username);
        $password = trim($password);
        $user = self::where('username',$username)->find();
        if (!$user) {
            $this->error = '管理员不存在';
        } else {
            if (encrypt_password($password,$user['pwd_salt'])!==$user['password']) {
                $this->error = '密码错误！';
            } else {
                $aid = $user['id'];
                // 更新登录信息
                $user['last_ip']   = request()->ip();
                $user['last_time'] = time();
				$user['logtimes']	   = $user['logtimes']+1;
                if ($user->save()) {
                    // 自动登录
                    $this->auto_login($user, $rememberme);
                }
                return $aid;
            }
        }
        return false;
    }

    /**
     * 自动登录
     * @param mixed $user 用户对象
     * @param bool $rememberme 是否记住登录，默认7天
     */
    public function auto_login($user, $rememberme = false)
    {
		// 记录登录
        $auth = array(
            'aid'             		 => $user->id,
            'uid'                    =>$user->uid,
            'avatar'    			 => $user->avatar,
            'last_change_pwd_time'   => $user->changepwd,
            'realname'       		 => $user->realname,
            'username'          	 => $user->username,
            'last_ip' 			     => $user->last_ip,
            'last_time'   		     => $user->last_time
        );
        session('admin_auth', $auth);
		session('admin_auth_sign', data_signature($auth));

        // 记住登录
        if ($rememberme) {
            $signin_token = $user->username.$user->id.$user->last_time;
            cookie('aid', $user->id, 24 * 3600 * 7);
            cookie('signin_token', data_signature($signin_token), 24 * 3600 * 7);
        }
        //根据需要决定是否记录前台登陆
        session('hid',$auth['uid']);
        cookie('yf_logged_user', jiami("{$auth['uid']}.{$auth['last_time']}"));
        $user=UserModel::where('id',$auth['uid'])->find();
        if($user) session('user',$user);
     }

    /**
     * 判断是否登录
     * @return int 0或用户id
     */
    public function is_login()
    {
        $user = session('admin_auth');
        if (empty($user)) {
            if (cookie('?aid') && cookie('?signin_token')) {
                $user = $this::get(cookie('aid'));
                if ($user) {
                    $signin_token = data_signature($user['username'].$user['id'].$user['last_time']);
                    if (cookie('signin_token') == $signin_token) {
                        $this->auto_login($user, true);
                        return $user['id'];
                    }
                }
            };
            return 0;
        }else{
            return session('admin_auth_sign') == data_signature($user) ? $user['aid'] : 0;
        }
    }
    /**
     * 增加管理员
     * @param string $username
     * @param string $pwd_salt
     * @param string $password
     * @param string $email
     * @param string $realname
     * @param int $group_id
     * @return mixed
     */
    public static function add($username,$pwd_salt='',$password,$email='',$realname='',$group_id=1)
    {
        $validate=new Commonvalidate();
        $sldata=array(
            'username'=>$username,
            'password'=>$password,
            'email'=>$email,
            'realname'=>$realname,
            'ip'=>request()->ip(),
            'create_time'=>time(),
            'changepwd'=>time(),
            'group_id'=>$group_id
        );
        if (!$validate->scene('admin')->check($sldata)) {
            return $validate->getError();
        }else{
            unset($sldata['group_id']);
            $pwd_salt=$pwd_salt?:random(10);
            $sldata['pwd_salt']=$pwd_salt;
            $sldata['password']=encrypt_password($password,$pwd_salt);
            // 启动事务
            Db::startTrans();
            try {
                //增加
                $admin=self::create($sldata);
                $aid=(int)$admin['id'];
                //关联添加管理组
                $admin->groups()->save($group_id);
                //增加会员
                $uid=UserModel::add($username,$pwd_salt,$password,$realname,$email,'',1,1);
                //回写admin的uid
                self::update(['id' =>$aid, 'uid' =>$uid]);
                // 提交事务
                Db::commit();
                return $aid;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return 0;
            }
        }
    }
    /**
     * 修改管理员
     * @param array
     * @return bool
     */
    public static function edit($data)
    {
        $admin=self::get($data['id'])->toArray();
        $admin['email']=$data['email'];
        $admin['realname']=$data['realname'];
        if($data['password']){
            $admin['pwd_salt']=random(10);
            $admin['password']=encrypt_password($data['password'],$admin['pwd_salt']);
            $admin['changepwd']=time();
        }
        $rst=self::update($admin,['id'=>$data['id']]);
        if($rst!==false){
            $access=new AuthGroupAccess;
            $access->where('uid',$data['id'])->update(['group_id'=>$data['group_id']]);
            return true;
        }else{
            return false;
        }
    }
}
