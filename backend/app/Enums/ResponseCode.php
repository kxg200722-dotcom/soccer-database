<?php

namespace App\Enums;

/**
 * 统一响应码（码段见后端手册）：
 * 0        成功
 * 100xx    参数/校验类
 * 200xx    认证/权限类
 * 300xx    资源不存在
 * 400xx    业务规则类
 * 500xx    第三方/外部服务类
 * 600xx    数据存储类
 * 900xx    系统未知异常
 */
enum ResponseCode: int
{
    case SUCCESS = 0;

    // 100xx 参数校验
    case PARAM_INVALID = 10001;
    case FILE_TYPE_INVALID = 10006;
    case FILE_SIZE_EXCEEDED = 10007;

    // 200xx 认证/权限
    case UNAUTHENTICATED = 20001;
    case CREDENTIALS_INVALID = 20002;
    case FORBIDDEN = 20004;

    // 300xx 资源
    case NOT_FOUND = 30001;

    // 400xx 业务规则
    case OPTIMISTIC_LOCK_CONFLICT = 9;
    case MATCH_FINISHED_LOCKED = 40002;
    case DUPLICATE_SUBMIT = 40009;

    // 500xx 外部服务
    case FACE_VERIFY_FAILED = 50008;

    // 600xx 存储
    case DB_ERROR = 60001;

    // 900xx 系统
    case SYSTEM_ERROR = 90001;

    public function message(): string
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::PARAM_INVALID => '参数校验失败',
            self::FILE_TYPE_INVALID => '不支持的文件格式',
            self::FILE_SIZE_EXCEEDED => '文件大小超出限制',
            self::UNAUTHENTICATED => '未登录或登录已过期',
            self::CREDENTIALS_INVALID => '账号或密码错误',
            self::FORBIDDEN => '无操作权限',
            self::NOT_FOUND => '资源不存在',
            self::OPTIMISTIC_LOCK_CONFLICT => '数据已被他人修改，请刷新后重试',
            self::MATCH_FINISHED_LOCKED => '比赛已结束，禁止修改',
            self::DUPLICATE_SUBMIT => '请勿重复提交',
            self::FACE_VERIFY_FAILED => '人脸核验服务异常',
            self::DB_ERROR => '数据存储异常',
            self::SYSTEM_ERROR => '系统繁忙，请稍后重试',
        };
    }
}
