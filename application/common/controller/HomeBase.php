<?php
namespace app\common\controller;

use think\Cache;
use think\Controller;
use think\Db;

class HomeBase extends Controller
{

    protected function _initialize()
    {
        parent::_initialize();
//        $this->getSystem();
        $this->getNav();
        $this->getSlide();
        $system = Db::name('system')->field('pc_template')->find();
        config('template.view_path','./themes/home/'.$system['pc_template'].'/');
    }

    /**
     * 获取前端导航列表
     */
    protected function getNav()
    {
        if (Cache::has('nav')) {
            $nav = Cache::get('nav');
        } else {
            $nav = Db::name('nav')->where(['status' => 1])->order(['sort' => 'ASC'])->select();
            $nav = !empty($nav) ? array2tree($nav) : [];
            if (!empty($nav)) {
                Cache::set('nav', $nav);
            }
        }

        $this->assign('nav', $nav);
    }

    /**
     * 获取前端轮播图
     */
    protected function getSlide()
    {
        if (Cache::has('slide')) {
            $slide = Cache::get('slide');
        } else {
            $slide = Db::name('slide')->where(['status' => 1, 'cid' => 1])->order(['sort' => 'DESC'])->select();
            if (!empty($slide)) {
                Cache::set('slide', $slide);
            }
        }

        $this->assign('slide', $slide);
    }
}