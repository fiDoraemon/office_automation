<?php

namespace app\common\model;

use think\Model;

class USUser extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'us_user';

    // 设置当前模型的数据库连接
    protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        'hostname'    => 'bdm245672555.my3w.com',
        // 数据库名
        'database'    => 'bdm245672555_db',
        // 数据库用户名
        'username'    => 'bdm245672555',
        // 数据库密码
        'password'    => 'Mindray99!',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'us_',
        // 数据库调试模式
        'debug'       => false,
    ];
}
