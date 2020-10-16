<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 重庆乐湛科技有限公司
// +----------------------------------------------------------------------
// | 官方网站: http://library.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace library\tools;

/**
 * 快递100查询接口
 * Class Express
 * @package library\tools
 */
class Express
{
    /**
     * 查询物流信息
     * @param string $code 快递公司编辑
     * @param string $number 快递物流编号
     * @return array
     */
    public static function query($code, $number)
    {
        list($list, $cache) = [[], app()->cache->get($ckey = md5($code . $number))];
        if (!empty($cache)) return ['message' => 'ok', 'com' => $code, 'nu' => $number, 'data' => $cache];
        for ($i = 0; $i < 6; $i++) if (is_array($result = self::doExpress($code, $number))) {
            if (!empty($result['data']['info']['context'])) {
                foreach ($result['data']['info']['context'] as $vo) $list[] = [
                    'time' => date('Y-m-d H:i:s', $vo['time']), 'context' => $vo['desc'],
                ];
                app()->cache->set($ckey, $list, 10);
                return ['message' => 'ok', 'com' => $code, 'nu' => $number, 'data' => $list];
            }
        }
        return ['message' => 'ok', 'com' => $code, 'nu' => $number, 'data' => $list];
    }

    /**
     * 获取快递公司列表
     * @param array $data
     * @return array
     */
    public static function getExpressList($data = [])
    {
        if (preg_match('/"currentData":.*?\[(.*?)],/', self::getWapBaiduHtml(), $matches)) {
            foreach (json_decode("[{$matches['1']}]") as $item) $data[$item->value] = $item->text;
            unset($data['_auto']);
            return $data;
        } else {
            app()->cache->delete('express_kuaidi_html');
            return self::getExpressList();
        }
    }

    /**
     * 执行百度快递100应用查询请求
     * @param string $code 快递公司编号
     * @param string $number 快递单单号
     * @return mixed
     */
    private static function doExpress($code, $number)
    {
        list($api, $qid) = [self::getExpressQueryApi(), '7740' . Data::uniqidNumberCode(15)];
        $url = "{$api}&appid=4001&nu={$number}&com={$code}&qid={$qid}&new_need_di=1&source_xcx=0&vcode=&token=&sourceId=4155&cb=callback";
        return json_decode(str_replace('/**/callback(', '', trim(Http::get($url, [], self::getOption()), ')')), true);
    }

    /**
     * 获取快递查询接口
     * @return string
     */
    private static function getExpressQueryApi()
    {
        if (preg_match('/"expSearchApi":.*?"(.*?)",/', self::getWapBaiduHtml(), $matches)) {
            return str_replace('\\', '', $matches[1]);
        } else {
            app()->cache->delete('express_kuaidi_html');
            return self::getExpressQueryApi();
        }
    }

    /**
     * 获取百度WAP快递HTML
     * @return string
     */
    private static function getWapBaiduHtml()
    {
        $content = app()->cache->get('express_kuaidi_html');
        while (empty($content) || stripos($content, '"expSearchApi":') === -1) {
            $uniqid = str_replace('.', '', microtime(true));
            $content = Http::get("https://m.baidu.com/s?word=快递查询&rand={$uniqid}", [], self::getOption());
        }
        app()->cache->set('express_kuaidi_html', $content, 30);
        return $content;
    }

    /**
     * 获取HTTP请求配置
     * @return array
     */
    private static function getOption()
    {
        list($clentip, $cookies) = [request()->ip(), app()->getRuntimePath() . ".express.cookie"];
        $headers = ['Host:express.baidu.com', "CLIENT-IP:{$clentip}", "X-FORWARDED-FOR:{$clentip}"];
        return ['cookie_file' => $cookies, 'headers' => $headers];
    }

}
