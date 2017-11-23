<?php

namespace App\Models\Traits;

use Redis;
use Carbon\Carbon;

trait LastActivedAtHelper
{
    // 缓存相关
    protected $hash_prefix = 'larabbs_last_actived_at_';
    protected $field_prefix = 'user_';

    public function recordLastActivedAt()
    {
        // 获取今天的时间
        $today = Carbon::now()->toDateString();

        // Redis 哈希表的表名， 如larabbs_last_actived_at_2017_11_22
        $hash = $this->hash_prefix . $today;

        // 字段的名称
        $field = $this->field_prefix . $this->id;

        // 当前的时间
        $now = Carbon::now()->toDateTimeString();

        // 数据写入Redis， 字段已存在会被更新
        Redis::hSet($hash, $field, $now);
    }

    public function syncUserActivedAt()
    {
        // 获取昨天的时间
        $yesterday = Carbon::now()->subDay()->toDateString();

        $hash = $this->hash_prefix . $yesterday;

        //从Redis里获取所有哈希表的数据
        $dates = Redis::hGetAll($hash);

        // 遍历
        foreach ($dates as $user_id => $actived_at) {
            $user_id = str_replace($this->field_prefix, '', $user_id);

            if($user = $this->find($user_id)) {
                $user->last_actived_at = $actived_at;
                $user->save();
            }
        }

        // 以数据库为中心的存储，既已同步，即可删除
        Redis::del($hash);
    }

    public function getLastActivedAtAttribute($value)
    {
        // 获取今天的时间
        $today = Carbon::now()->toDateString();

        // Redis 哈希表的表名， 如larabbs_last_actived_at_2017_11_22
        $hash = $this->hash_prefix . $today;

        // 字段的名称
        $field = $this->field_prefix . $this->id;

        $datetime = Redis::hGet($hash, $field) ? : $value;

        if ($datetime) {
            return new Carbon($datetime);
        } else {
            return $this->created_at;
        }
    }

}