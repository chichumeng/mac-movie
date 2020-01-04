<?php
/**
 * Created by PhpStorm.
 * User: thisgulu
 * Date: 2020/1/3
 * Time: 20:09
 */

namespace app\api\controller;
use think\Controller;
use think\Cache;
use think\Db;

class Videos extends Base
{
    // 视频列表
    public function index(){
        $condition = [];

        if(input('ids')){
            $condition['vod_id'] = ['in',is_array(input('ids')) ? input('ids') : explode(',',input('ids'))];
        }
        if(input('type_id')){
            $condition['type_id'] = input('type_id');
        }
        if(input('search_key')){
            $condition['vod_name'] = ['like', '%' . input('search_key') . '%'];
        }
        /*
        $where = [];
        if (!empty($this->_param['ids'])) {
            $where['vod_id'] = ['in', $this->_param['ids']];
        }
        if (!empty($GLOBALS['config']['api']['vod']['typefilter'])) {
            $where['type_id'] = ['in', $GLOBALS['config']['api']['vod']['typefilter']];
        }

        if (!empty($this->_param['t'])) {
            if (empty($GLOBALS['config']['api']['vod']['typefilter']) || strpos($GLOBALS['config']['api']['vod']['typefilter'], $this->_param['t']) !== false) {
                $where['type_id'] = $this->_param['t'];
            }
        }
        if (!empty($this->_param['h'])) {
            $todaydate = date('Y-m-d', strtotime('+1 days'));
            $tommdate = date('Y-m-d H:i:s', strtotime('-' . $this->_param['h'] . ' hours'));

            $todayunix = strtotime($todaydate);
            $tommunix = strtotime($tommdate);

            $where['vod_time'] = [['gt', $tommunix], ['lt', $todayunix]];
        }
        if (!empty($this->_param['wd'])) {
            $where['vod_name'] = ['like', '%' . $this->_param['wd'] . '%'];
        }

        if (empty($GLOBALS['config']['api']['vod']['from']) && !empty($this->_param['from'])) {
            $GLOBALS['config']['api']['vod']['from'] = $this->_param['from'];
        }
        if (!empty($GLOBALS['config']['api']['vod']['from'])) {
            $where['vod_play_from'] = ['like', '%' . $GLOBALS['config']['api']['vod']['from'] . '%'];
        }

        if (!empty($GLOBALS['config']['api']['vod']['datafilter'])) {
            $where['_string'] .= ' ' . $GLOBALS['config']['api']['vod']['datafilter'];
        }
        if (empty($this->_param['pg'])) {
            $this->_param['pg'] = 1;
        }

        $order = 'vod_time desc';
        $field = 'vod_id,vod_name,type_id,"" as type_name,vod_en,vod_time,vod_remarks,vod_play_from,vod_time';

        if ($this->_param['ac'] == 'videolist' || $this->_param['ac'] == 'detail') {
            $field = '*';
        }
        $res = model('vod')->listData($where, $order, $this->_param['pg'], $GLOBALS['config']['api']['vod']['pagesize'], 0, $field, 0);


        if ($this->_param['at'] == 'xml') {
            $html = $this->vod_xml($res);
        } else {
            $html = json_encode($this->vod_json($res));
        }

        if($cache_time>0) {
            Cache::set($cach_name, $html, $cache_time);
        }

        */

        header('content-type:application/json;charset=utf-8',true);

        $perPage = input('per_page',10);
        $page = input('page',1);
        $order = 'vod_time desc';

        $videos = Db::name('Vod')->field('vod_id,type_id,type_id_1,vod_class,vod_pic,vod_actor,vod_director,vod_director,vod_area,vod_lang,vod_year,vod_score')->where($condition)->order($order)->limit(sprintf('%s,%s',$perPage * ($page-1),$perPage))->select();

        $total = Db::name('Vod')->where($condition)->count();
        $resp = [
            'total'=>intval($total),
            'per_page'=>intval($perPage),
            'current_page'=>intval($page),
            'last_page'=>ceil($total/$perPage),
            'data'=>$videos
        ];

        echo json_encode([
            '_code'=>'1',
            '_error_msg'=>'',
            'videos'=>$resp
        ]);
        die;
    }

    public function videoJson($res)
    {
        $type_list = model('Type')->getCache('type_list');
        foreach($res['list'] as $k=>&$v){
            $type_info = $type_list[$v['type_id']];
            $v['type_name'] = $type_info['type_name'];
            $v['vod_time'] = date('Y-m-d H:i:s',$v['vod_time']);

            if(substr($v["vod_pic"],0,4)=="mac:"){
                $v["vod_pic"] = str_replace('mac:','http:',$v["vod_pic"]);
            }
            elseif(!empty($v["vod_pic"]) && substr($v["vod_pic"],0,4)!="http" && substr($v["vod_pic"],0,2)!="//"){
                $v["vod_pic"] = $GLOBALS['config']['api']['vod']['imgurl'] . $v["vod_pic"];
            }

            $arr_from = explode('$$$',$v['vod_play_from']);
            $arr_url = explode('$$$',$v['vod_play_url']);
            $arr_server = explode('$$$',$v['vod_play_server']);
            $arr_note = explode('$$$',$v['vod_play_note']);

            $key = array_search($GLOBALS['config']['api']['vod']['from'],$arr_from);
            $res['list'][$k]['vod_play_from'] = $GLOBALS['config']['api']['vod']['from'];
            $res['list'][$k]['vod_play_url'] = $arr_url[$key];
            $res['list'][$k]['vod_play_server'] = $arr_server[$key];
            $res['list'][$k]['vod_play_note'] = $arr_note[$key];

        }


        if($this->_param['ac']!='videolist' && $this->_param['ac']!='detail') {
            $class = [];
            $typefilter  = explode(',',$GLOBALS['config']['api']['vod']['typefilter']);

            foreach ($type_list as $k=>&$v) {

                if (!empty($GLOBALS['config']['api']['vod']['typefilter'])){
                    if(in_array($v['type_id'],$typefilter)) {
                        $class[] = ['type_id' => $v['type_id'], 'type_name' => $v['type_name']];
                    }
                }
                else {
                    $class[] = ['type_id' => $v['type_id'], 'type_name' => $v['type_name']];
                }
            }
            $res['class'] = $class;
        }
        return $res;
    }
}