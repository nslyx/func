<?php
/**
 * User: zxtian
 * Date: 2019-10-20
 * Time: 11:37
 */

if (!function_exists('tree')) {
    /**
     * 生成 树形数组结构
     * @param array $items
     * @param bool $whole
     * @return array 树结构数组 0 起点的，其他的被丢弃 whole 为 true 时，返回完整结构
     */
    function tree(Array $items, $whole = false)
    {
        $items = array_column($items, null, 'id');
        foreach ($items as $id => &$item) {
            if (!isset($item['pid'])) {
                continue;
            }
            $items[$item['pid']]['children'][$item['id']] = &$item;
        }

        return $whole ? $items : (isset($items[0]) ? $items[0]['children'] : []);
    }
}

if (!function_exists('tile')) {
    /**
     * 树形结构数组所有节点平铺，并获取树形结构的信息
     * @param array $items 数结构数组
     * @param string $n
     * @param null $parent
     * @param int $level 从属级别， 表示有几层父级,  0 开始
     * @param int $path id 路径
     * @return mixed
     */
    function tile(Array $items, $n = 'name', $parent = null, $level = 0, $path = 0)
    {
        $rows = [];
        foreach ($items as $id => $item) {
            $item['level'] = $level;
            $item['path'] = $path.'-'.$id;
            $item['full'] = empty($parent) ? $item[$n] : $parent['full'].'_'.$item[$n];
            $item['parent'] = $parent;
            if (empty($item['children'])) {
                $rows[$id] = $item;
            } else {
                $children = $item['children'];
                // unset 保证每个元素不继续向上或向下关联多级别
                unset($item['children']);
                $rows[$id] = $item;

                unset($item['parent']);
                $cRows = tile($children, $n, $item, ++$level, $item['path']);
                // array_merge 会重新索引，键名就不对应 id 了
                // $rows = array_merge($rows, $cRows);
                foreach ($cRows as $id => $item) {
                    $rows[$id] = $item;
                }
            }
        }

        return $rows;
    }
}


if (!function_exists('array_pick')) {
    /**
     * 从数组中选取指定的成员 构成新数组并返回
     * @param array $array
     * @param $keys
     * @return array
     */
    function array_pick(Array $array, Array $keys)
    {
        $pick = [];
        foreach ($keys as $k) {
            isset($array[$k]) && ($pick[$k] = $array[$k]);
        }

        return $pick;
    }
}

if (!function_exists('multi_curl')) {
    /**
     * 并发 curl
     * 并获取对应的返回值
     *
     * !!! Attention:
     * curls 里面的成员都一定要添加超时时间，
     * 否则在并发执行中遇到无响应的地址会死循环
     *
     * @param array $curls
     * @return array
     */
    function multi_curl(Array $curls)
    {
        $mh = curl_multi_init();
        $chs = array_map(
            function ($ch) use ($mh) {
                curl_multi_add_handle($mh, $ch);

                return $ch;
            },
            $curls
        );

        $running = 0;
        do {
            curl_multi_exec($mh, $running);
            // 阻塞直到cURL批处理连接中有活动连接，避免 cpu 高占用
            curl_multi_select($mh);
        } while ($running > 0);

        $res = array_map(
            function ($ch) use ($mh) {
                curl_multi_remove_handle($mh, $ch);

                return curl_multi_getcontent($ch);
            },
            $chs
        );
        curl_multi_close($mh);

        return $res;
    }
}


if (!function_exists('rip')) {
    /**
     * Real ip
     * 取值优先次序
     * HTTP_X_REAL_IP
     * HTTP_CLIENT_IP
     * HTTP_X_FORWARDED_FOR
     * REMOTE_ADDR
     *
     */
    function rip()
    {
        $ip = 'unknown';
        !empty($_SERVER['REMOTE_ADDR']) && ip2long($_SERVER['REMOTE_ADDR']) && $ip = $_SERVER['REMOTE_ADDR'];
        $p = '/(((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|(\d))\.){3}((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|(\d))/';
        !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        && preg_match($p, $_SERVER['HTTP_X_FORWARDED_FOR'], $match) && ($ip = $match[0]);
        !empty($_SERVER['HTTP_CLIENT_IP']) && ip2long($_SERVER['HTTP_CLIENT_IP']) && $ip = $_SERVER['HTTP_CLIENT_IP'];
        !empty($_SERVER['HTTP_X_REAL_IP']) && ip2long($_SERVER['HTTP_X_REAL_IP']) && $ip = $_SERVER['HTTP_X_REAL_IP'];

        return $ip;
    }
}