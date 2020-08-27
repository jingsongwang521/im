<?php


namespace app\websocket\model;

use app\common\lib\traits\SingletonTrait;
use MongoDB\BSON\ObjectId;
use think\Db;

class UserModel
{
    use SingletonTrait;

    protected $table = 'zl_im_user';

    public function __construct()
    {

    }

    public function getUser($im_id)
    {
        $info = Db::table($this->table)->where('_id', $im_id)->findOrEmpty();
//        echo $this->mongo->table($this->table)->getLastSql();
        return $info;
    }

    public function getUserByIds(array $im_ids)
    {
        $list = Db::table($this->table)->where('_id', 'in', $im_ids)->select();
//        echo $this->mongo->table($this->table)->getLastSql();
        $arr = [];
        if(!empty($list)) {
            foreach ($list as $v) {
                $arr[(string)$v['_id']] = $v;
            }
        }
        return $arr;
    }

    public function getUserByUid($uid)
    {
        if($uid > 0) {
            $info = Db::table($this->table)->where('uid', $uid)->findOrEmpty();
        } else {
            $info = [];
        }
        return $info;
    }

    public function createUser($uid, $nickname, $avatar, $user_type)
    {
        $im_id = (string)new ObjectId();
        $data = [
            '_id' => $im_id,
            'uid' => $uid,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'user_type' => $user_type,
            'create_time' => $_SERVER['REQUEST_TIME'],
            'update_time' => $_SERVER['REQUEST_TIME'],
        ];
        $result = Db::table($this->table)->insert($data);
        //        echo Db::table($this->table)->getLastSql();
        if($result !== false) {
            return $im_id;
        } else {
            return false;
        }
    }

    public function updateUser($im_id, $uid, $nickname, $avatar, $user_type)
    {
        $data = [
            'uid' => $uid,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'user_type' => $user_type,
            'update_time' => $_SERVER['REQUEST_TIME'],
        ];
        $result = Db::table($this->table)->where('_id', $im_id)
            ->update($data);
        return $result === false ? false : true;
    }

}