<?php
/**
 * 结果返回 Trait
 */
namespace app\common\lib\traits;

use app\common\lib\Error;
use app\common\lib\ResultRet;

trait RetTrait
{
    /**
     * 返回结果
     * @param $errNo
     * @param array $result
     * @param string $message
     * @return ResultRet
     */
    protected function ret($errNo, $result=[], $message="") {
        $ret = new ResultRet();
        return $ret->result($errNo, $result, $message);
    }

    /**
     * 成功返回结果
     * @param array $result
     * @param string $message
     * @return ResultRet
     */
    protected function retSuccess($result=[], $message='') {
        $ret = new ResultRet();
        return $ret->success($result, $message);
    }

    /**
     * 失败返回结果
     * @param string|array $message
     * @param int $errNo
     * @return ResultRet
     */
    protected function retError($errNo=Error::FAILED, $message="", $result=[]) {
        $ret = new ResultRet();
        return $ret->error($errNo, $message, $result);
    }
}
