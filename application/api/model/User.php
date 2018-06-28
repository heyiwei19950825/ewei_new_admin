<?php

namespace app\api\model;

use think\Model;
use think\Db;
use app\lib\exception\ParameterException;
class User extends BaseModel
{
    protected $autoWriteTimestamp = true;
//    protected $createTime = ;

    public function orders()
    {
        return $this->hasMany('Order', 'user_id', 'id');
    }

    public function address()
    {
        return $this->hasOne('UserAddress', 'user_id', 'id');
    }

    /**
     * 用户是否存在
     * 存在返回uid，不存在返回0
     */
    public static function getByOpenID($openid)
    {
        $user = User::field('id,s_id,nickname,username,sex,birthday,portrait,mobile,status,rank_id,integral,balance')->where('openid', '=', $openid)
            ->find();
        if($user){
            $user['portrait'] = strstr($user['portrait'],'http') == false? config('setting.domain').$user['portrait']:$user['portrait'];
            //会员信息
            if( $user['rank_id'] != 0){
                $user['rank'] = UserRank::where([
                    'id'=>$user['rank_id']
                ])->find();
            }else{
                $user['rank'] = [];
            }
        }

        return $user;
    }

    /**
     * 通过ID获取用户信息
     * @param $uid
     * @return array|false|mixed|\PDOStatement|string|Model
     */
    public static function getInfoById( $uid ){
        $user = Db::name('user')->where(['id'=>$uid])->find();
        if( empty( $user) ){
            return [];
        }
        return $user;
    }

    /**
     * 修改用户积分并记录日志
     * @param int $uid
     * @param int $integral
     * @param int $type
     * @return array
     */
    public static function updateUserIntegral($uid = -1,$integral=0,$type = 0,$note='积分购买商品消耗'){
        $user = Db::name('user')->where(['id'=>$uid])->find();
        if( $type == 0 ){
            $last_integral = $user['integral'] - $integral;
        }else{
            $last_integral = $user['integral'] + $integral;
        }

        $row = Db::name('user')->where(['id'=>$uid])->update(['integral'=>$last_integral]);

        $data = [
            'note' => $note,
            'u_id'=>$uid,
            'integral'=>$integral,
            'residue_integra'=>$last_integral,
            'time' => date('Y-m-d H:i:s',time())
        ];

        Db::name('user_integral_log')->insert($data);

        return $row;
    }

    /**
     * 检查用户是否认证过
     * @param int $id
     * @return bool
     * @throws ParameterException
     */
    public static  function verifyAttestation($id = 0 ){
        $info = Db::name('user')->field('is_authentication')->find([
            'id'=>$id
        ]);
        if($info['is_authentication'] == 1 ){
            return true;
        }else{
            throw new ParameterException(
                [
                    'msg' => '用户未认证，请先认证后再操作'
                ]);
        }
    }
}
