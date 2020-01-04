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
use think\Response;
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

        $this->responseJson(1,['videos'=>$resp]);
    }


    public function categories(){
        $categories = Db::name('Type')->field('type_id,type_name,type_sort,type_logo')->select();
        $this->responseJson(1, ['categories'=>$categories]);
    }

    public function responseJson($code,$data,$errorMsg=''){

        $data['_code'] = $code;
        $data['_error_msg'] = $errorMsg;

        Response::create($data, 'json')->send();
    }
}