<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Cache;
use think\Db;

/**
 * 系统配置
 * Class System
 * @package app\admin\controller
 */
class System extends AdminBase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 站点配置
     */
    public function siteConfig()
    {
        $site_config = Db::name('system')->where(['s_id'=>1])->find();
        $site_config['x_inform'] = json_decode($site_config['x_inform'],true);
        return $this->fetch('site_config', ['site_config' => $site_config]);
    }

    /**
     * 更新配置
     */
    public function updateSiteConfig()
    {
        if ($this->request->isPost()) {
            $site_config                = $this->request->post();
            foreach ($site_config as $k=>&$v){
                if( $v === '' ){
                    unset($site_config[$k]);
                }
            }
            if (Db::name('system')->where(['s_id'=>1])->update($site_config) !== false) {
                $this->success('提交成功');
            } else {
                $this->error('提交失败');
            }
        }
    }

    /**
     * 清除缓存
     */
    public function clear()
    {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            $this->success('清除缓存成功');
        } else {
            $this->error('清除缓存失败');
        }
    }

    /**
     * 系统常规参数配置
     */
    public function sysConfig(){
        $config = Db::name('constant_value')->select()->toArray();
        $configList = [];
        foreach ($config as $k=>$v){
            if($v['key'] == 'commitment'){
                $commitment = json_decode($v['value'],true);
            }
            $configList[$v['key']] = array_merge(json_decode($v['value'],true),['name'=>$v['name']]);
        }
        return $this->fetch('sys_config',['configList'=>$configList,'commitment'=>$commitment]);
    }

    public function updateSysConfig(){
        $data = $this->request->param();
        foreach($data as $k=>$v){
            $value = json_encode($v);
            Db::name('constant_value')->where(['key'=>$k])->update(['value'=>$value]);
        }

        $this->success('修改成功');
    }
}