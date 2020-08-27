<?php
/**
 * 结果返回类
 */
namespace app\websocket\lib;

use app\websocket\lib\Error;

class ResultRet
{
    protected $errNo    = 0;
    protected $result   = [];
    protected $msg  = '';

    public function __construct($errNo=0, $result=[], $msg='')
    {
        $this->errNo = $errNo;
        $this->result = $result;
        $this->msg = $msg;
    }

    /**
     * 返回结果对象
     * @param int $errNo
     * @param array $result
     * @param string $msg
     * @return \zhulong\ResultRet
     */
    public static function result_return($errNo=0, $result=[], $msg='') {
        $ret = new static();
        return $ret->result($errNo, $result, $msg);
    }

    /**
     * 返回成功
     * @param int $errNo
     * @param array $result
     * @param string $msg
     * @return ResultRet
     */
    public static function result_success($result=[], $msg='') {
        $ret = new static();
        return $ret->success($result, $msg);
    }

    /**
     * 返回失败
     * @param $errNo
     * @param string $msg
     * @param array $result
     * @return ResultRet
     */
    public static function result_error($errNo, $msg='', $result=[]) {
        $ret = new static();
        return $ret->error($errNo, $msg, $result);
    }

    /**
     * 通过数组返回
     * @param $data
     * @return ResultRet
     */
    public static function result_from_data($data) {
        $ret = new static();
        $ret->errNo = isset($data['errNo']) ? $data['errNo'] : 0;
        $ret->result = isset($data['result']) ? $data['result'] : [];
        $ret->msg = isset($data['msg']) ? $data['msg'] : '';
        return $ret;
    }

    /**
     * 是否失败
     * @return bool
     */
    public function isError()
    {
        return $this->errNo == 0 ? false : true;
    }

    /**
     * 是否成功
     * @return bool
     */
    public function isSuccess()
    {
        return $this->errNo == 0 ? true : false;
    }

    /**
     * 错误码
     * @return int
     */
    public function getErrNo()
    {
        return $this->errNo;
    }

    /**
     * 结果数据
     * @param null|string $key
     * @return array|mixed
     */
    public function getResult($key=null, $default=null)
    {
        if(is_null($key)) {
            return $this->result;
        } else {
            if(isset($this->result[$key])) {
                return $this->result[$key];
            } else {
                return $default;
            }
        }
    }

    /**
     * 错误信息
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    public function result($errNo=0, $result=[], $msg='')
    {
        $this->errNo = $errNo;
        $this->result = $result;
        if($this->errNo > 0) {
            if(is_array($msg)) {
                $this->msg = Error::getMsg($errNo, $msg);
            } elseif($msg == '') {
                $this->msg = Error::getMsg($errNo);
            } else {
                $this->msg = $msg;
            }
        } else {
            $this->msg = $msg;
        }
        return $this;
    }

    /**
     * 返回成功
     * @param array $result
     * @param string $msg
     * @return $this
     */
    public function success($result=[], $msg='')
    {
        $this->errNo = 0;
        $this->result = $result;
        $this->msg = $msg;
        return $this;
    }

    /**
     * 返回失败
     * @param $errNo
     * @param string|array $msg
     * @param array $result
     * @return $this
     */
    public function error($errNo, $msg='', $result=[])
    {
        $this->errNo = $errNo;
        $this->result = $result;
        if($this->errNo > 0) {
            if(is_array($msg)) {
                $this->msg = Error::getMsg($errNo, $msg);
            } elseif($msg == '') {
                $this->msg = Error::getMsg($errNo);
            } else {
                $this->msg = $msg;
            }
        } else {
            $this->msg = $msg;
        }
        return $this;
    }

    /**
     * 转换成数组
     * @return array
     */
    public function toArray()
    {
        $arr = [
            'errNo'     => $this->errNo,
            'result'    => $this->result,
            'msg'   => $this->msg,
        ];
        return $arr;
    }

    /**
     * 转换成Json
     * @return string
     */
    public function toJson()
    {
        $arr = $this->toArray();
        if(empty($arr['result'])) {
            $arr['result'] = new \stdClass();
        }
        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }


}