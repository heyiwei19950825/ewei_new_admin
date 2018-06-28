<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/2/19
 * Time: 11:28
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Category as CategoryModel;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;
use app\lib\exception\CategoryException;

class Category extends BaseController
{
    /**
     * 获取全部类目列表，但不包含类目下的商品
     * Request 演示依赖注入Request对象
     * @url /category/all
     * @return array of Categories
     * @throws MissException
     */
    public function getAllCategories( $id = -1 )
    {

        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $cateNavList = CategoryModel::all(function($query){
            $query->where(['pid'=>0,'is_hide'=>0,'s_id'=>1])->field('id,name')->order('sort', 'asc');
        });
        if(empty($cateNavList)){
           return $row;
        }

        $id = $id == -1 ?$cateNavList[0]['id']:$id;

        $field = 'id,name,thumb,alias';
        //查询当前分类的详情数据
        $categoryInfo = CategoryModel::getCategory($id,$field)->toArray();
        $categoryInfo['thumb'] = self::prefixDomain($categoryInfo['thumb']);

        //查询当前分类下的所有子分类
        $brotherCategory['subCategoryList'] = CategoryModel::childCategory($id,$field)->toArray();
        $brotherCategory['subCategoryList'] = self::prefixDomainToArray('thumb',$brotherCategory['subCategoryList']);

        $brotherCategory = array_merge($categoryInfo,$brotherCategory);
        $row['data'] = [
            'categoryList' => $cateNavList,
            'currentCategory' => $brotherCategory
        ];
        return $row;
    }



    /**
     * 这里没有返回类目的关联属性比如类目图片
     * 只返回了类目基本属性和类目下的所有商品
     * 返回什么，返回多少应该根据团队情况来考虑
     * 为了接口通用性可以返回大量的无用数据
     * 也可以只返回客户端需要的数据，但这会造成有大量重复接口
     * 接口应当和业务绑定还是和实体绑定需要团队自己抉择
     * 此接口主要是为了返回分类下面的products，请对比products中的
     * 接口，这是一种不好的接口设计
     * @url /category/:id/products
     * @return Category single
     * @throws MissException
     */
    public function getCategory($id)
    {
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        $category = CategoryModel::getCategory($id);
        if(empty($category)){
            throw new MissException([
                'msg' => 'category not found'
            ]);
        }
        return $category;
    }

    /**
     * 通过商品分类ID获取
     * @url /category/:id
     * @param int $id 商品ID
     * @return Row
     * @throws CategoryException
     */
    public function getCategoryByCId( $id = -1 ){
        (new IDMustBePositiveInt())->goCheck();
        $field = 'id,name,alias,pid';
        $categoryInfo = [];
        //查询当前分类
        if( $id != 9999 ){
            $categoryInfo = CategoryModel::getCategory( $id, $field );
            if( !$categoryInfo ){
                throw new CategoryException();
            }
            $categoryInfo = $categoryInfo->toArray();
        }else{
            $categoryInfo['pid'] = 0;
        }

        //查询当前分类下的所有子分类
        $brotherCategory = CategoryModel::childCategory($categoryInfo['pid'],$field);

        $data = [
            'brotherCategory' => $brotherCategory,
            'currentCategory' => $categoryInfo, //当前分类信息
        ];
        $row = [
            'errno' => 0,
            'errmsg' => '',
            'data' => $data
        ];

        return $row;
    }

    /**
     * 通过ID获取当前级别分类
     * @param int $id
     * @return array
     */
    public function getCategoryByPId( $id = -1 ){

        $data = CategoryModel::getCategoryByPId( $id );
        $row = [
            'errno' => 0,
            'errmsg' => '',
            'data' => $data
        ];

        return $row;
    }

}