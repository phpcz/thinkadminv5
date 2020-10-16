<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/20
 * Time: 10:39
 */


if (!function_exists('hideEmail')) {
    /**
     * 邮箱用*号隐藏
     * @param $email
     * @return string
     */
    function hideEmail($email)
    {
        $prefix_email = substr($email, 0, strrpos($email, '@'));
        $suffix_email = substr($email, strrpos($email, '@'));
        $count = strlen($prefix_email);
        if ($count > 3) {
            $start = substr($prefix_email, 0, 2);
            $end = substr($prefix_email, -1);
        } else {
            $start = substr($prefix_email, 0, 1);
            $end = '';
        }
        return $start . '****' . $end . $suffix_email;
    }
}

if (!function_exists('hidePhonae')) {
    /**
     * 手机用*号隐藏
     * @param $phone
     * @return mixed
     */
    function hidePhone($phone)
    {
        $phone = substr_replace($phone, '****', 3, 4);
        return $phone;
    }
}

if (!function_exists('splitStr')) {
    /**
     * 字符串中文截取
     * @param $str  需要截取的字符串
     * @param int $length 截取长度
     * @param string $code 字符编码格式
     * @return string   经过截取后的字符串
     */
    function splitStr($str, $length = 50, $code = "utf-8")
    {
        if (mb_strlen($str, $code) > $length) {
            return mb_substr($str, 0, $length, $code) . '...';
        } else {
            return $str;
        }
    }
}

if (!function_exists('strLine')) {
    /**
     * 把文本中的换行空格替换为<br> &nbsp
     * @param $str  需要替换的字符串
     * @return mixed  经过替换过得字符串
     */
    function strLine($str)
    {
        $str = str_replace(["\r\n", "\r", "\n"], '<br>', $str);
        $str = str_replace(' ', "&nbsp", $str);
        return $str;
    }
}

if (!function_exists('checkImages')) {
    /**
     * 检查图片地址是否正确
     * @param $images
     * @return mixed
     */
    function checkImage($images)
    {
        if (!is_array($images))
        {
            $images = explode('|',$images);
        }

        foreach ($images as $v)
        {
            if (!preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $v))
            {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('get_distance')) {
    /**
     * 计算两点地理坐标之间的距离
     * @param decimal $longitude1 起点经度
     * @param decimal $latitude1 起点纬度
     * @param decimal $longitude2 终点经度
     * @param decimal $latitude2 终点纬度
     * @param int $unit 单位 1:米 2:公里
     * @param int $decimal 精度 保留小数位数
     * @return $distance
     */
    function get_distance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2){
        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;
        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;
        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;
        if ($unit == 2) {
            $distance = $distance / 1000;
        }
        return round($distance, $decimal);
    }
}

if (!function_exists('getConfig')) {
    /**
     * 获取config配置
     * @param $name 配置名称
     * @param string $value 配置值
     * @return mixed
     */
    function getConfig($name,$value = '')
    {
        if ($value)
        {
            return config($name)[$value];
        }

        return config($name);
    }
}

if (!function_exists('setPagehtml'))
{
    /**
     * 设置分页输出格式
     * @param $db   model对象
     * @param $where    条件（url参数）
     * @param $page  分页
     * @param $num  int 每页数量
     * @return array
     */
    function setPagehtml($db,$where,$page,$num = 10)
    {
        $data = array();
        $data['pages'] = 1; //总页数
        $data['current'] = 1; //当前页
        $data['total'] = count($data); //总数据量
        $data['pagehtml'] = '';
        if ($page)
        {
            $query = array();
            if ($where) foreach ($where as $v) $query[$v[0]] = $v[2];
            $db = $db->paginate($num, false, ['query' => $query]);
            $data['pages'] = $db->lastPage(); //总页数
            $data['current'] = $page; //当前页
            $data['total'] = $db->total(); //总数据量
            $data['list'] = $db->toArray()['data'];
            $data['pagehtml'] = $db->render();
        }else{
            $list = $db->select()->toArray();
            $data['list'] = $list;
        }
        return $data;
    }
}

if (!function_exists('setUrlCode'))
{
    /**
     * 拼接url参数
     * @param $key
     * @param $value
     * @param $param
     * @return string
     */
    function setUrlCode($key,$value,$param)
    {
        if ($param)
        {
            $param[$key] = $value;
            if (isset($param['page']))
            {
                unset($param['page']);
            }
            return http_build_query($param);
        }
        return $key.'='.$value;
    }
}

if (!function_exists('format_bytes')) {
    /**
     * 格式化字节大小
     * @param  number $size 字节数
     * @param  string $delimiter 数字和单位分隔符
     * @return string  格式化后的带单位的大小
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('secToTime')) {
    /**
     * 将秒转为时分秒
     * @param $seconds
     * @return string
     */
    function secToTime($seconds)
    {
        if ($seconds > 3600) {
            $hours = intval($seconds / 3600);
            $minutes = $seconds % 3600;
            $time = $hours . ":" . gmstrftime('%M:%S', $minutes);
        } else {
            $time = gmstrftime('%M:%S', $seconds);
        }
        return $time;
    }
}

if (!function_exists('array_kv')) {
    /**
     * 将数组一个元素的值作为键
     * @param $data        要处理的数组
     * @param $key        要处理的键值
     * @return array
     */
    function array_kv($data, $key)
    {
        $data_new = [];
        foreach ($data as $k => $v) {
            $data_new[$v[$key]] = $v;
        }
        return $data_new;
    }
}

if (!function_exists('deep_in_array')) {
    /**
     * 判断二维数组是否存在某个值
     * @param $data 数组
     * @param $value   值
     * @return bool
     */
    function deep_in_array($data, $value)
    {
        foreach ($data as $item)
        {
            if(!is_array($item)) {
                if ($item == $value) {
                    return true;
                } else {
                    continue;
                }
            }else {
                if (in_array($value, $item)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('round_dp'))
{
    /**
     * 字节计算
     * @param $num
     * @param $dp
     * @return float|int
     */
    function round_dp($num , $dp)
    {
        $sh = pow(10 , $dp);
        return (round($num*$sh)/$sh);
    }

}

if (!function_exists('conversionSize'))
{
    /**
     * 自动计算文件大小单位换算
     * @param $byte  字节
     * @return float|int
     */
    function conversionSize($byte)
    {
        if($byte < 1024) {
            $unit="B";
        }
        else if($byte < 10240) {
            $byte=round_dp($byte/1024, 2);
            $unit="KB";
        }
        else if($byte < 102400) {
            $byte= round_dp($byte/1024, 2);
            $unit="KB";
        }
        else if($byte < 1048576) {
            $byte= round_dp($byte/1024, 2);
            $unit="KB";
        }
        else if ($byte < 10485760) {
            $byte= round_dp($byte/1048576, 2);
            $unit="MB";
        }
        else if ($byte < 104857600) {
            $byte= round_dp($byte/1048576,2);
            $unit="MB";
        }
        else if ($byte < 1073741824) {
            $byte= round_dp($byte/1048576, 2);
            $unit="MB";
        }
        else {
            $byte= round_dp($byte/1073741824, 2);
            $unit="GB";
        }

        $byte .= $unit;
        return $byte;
    }

}