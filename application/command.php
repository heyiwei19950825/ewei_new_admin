<?php
//获取商品详情
        if ($goodsId == 0) {//购物车购买
            $goodsPrice = $orderTotalPrice = $actualPrice = $freightPrice = $couponPrice = $couponId = $rankDiscount = $userCouponList = $couponPrice = $shipping_company_id = $order_type = $give_point = $collectiveId = 0;
            $cartList = CartModel::getCartAll($this->uid, true);

            $freight = $goodsInfo = $checkedCoupon = [];
            //优惠券 总数
            if ($couponId != 0) {
                //选中的优惠券信息
                $checkedCoupon = Coupon::getInfoById($this->uid, $couponId);
                //检测优惠券是否可用
                if (empty($checkedCoupon)) {
                    $row['errmsg'] = '优惠券信息有误';
                    $row['errno'] = 10005;
                    return $row;
                }
                $couponPrice = $checkedCoupon['money'] * 100;
            }

            //计算价格
            foreach ($cartList as $item) {
                //商品总价格
                $goodsPrice += $item['vip_price'] == 0 ? ($item['price'] * 100) * $item['num'] : ($item['vip_price'] * 100) * $item['num'];

                $goodsInfo = GoodsModel::getProductDetail($item['goods_id'], 'eid,give_integral');
                //判断是否是VIP订单
                if ($item['vip_price'] != 0) {
                    $is_vip = 1;
                }
                if (!array_key_exists($goodsInfo['eid'], $freight)) {
                    //运费
                    $express = Express::getDetail($goodsInfo['eid'], 'cost,company_name,id');
                    $freight[$goodsInfo['eid']] = array(
                        'name' => $express['company_name'],
                        'cost' => $express['cost'] * 100,
                    );
                }
                $give_point += $item['give_point'] * $item['num'];
            }

            //计算运费
            foreach ($freight as $item) {
                $freightPrice += $item['cost'] + 0;
            }

            //计算 积分等级获得优惠价格折扣
//            if( $userInfo['rank_id'] != 0){
//                $rankDiscount = $goodsPrice*100 - ($goodsPrice* ($userInfo['rank_discount'])*10);
//            }
//            dump($rankDiscount);die;
            $rankDiscount = 0;
            //最后商品订单价格   商品总价格 + 运费 - 会员折扣
            $actualPrice = $goodsPrice - ($rankDiscount / 100) + $freightPrice - $couponPrice;

            $order_type = 0;
            $goodsPrice = $goodsPrice / 100;
            $actualPrice = round($actualPrice / 100, 2);
            $coupon_money = $couponPrice;
            $coupon_id = $couponId;
        } else {//直接购买
            $goodsPrice = $coupon_id = $coupon_money = 0;
            $product = GoodsModel::getProductDetail($goodsId, '');
            $shopInfo = Shop::getShopInfoById($product['s_id'], 'shop_name,id');
            $shopId = $shopInfo['id'];
            $shopName = $shopInfo['shop_name'];

            $product['thumb'] = self::prefixDomain($product['thumb']);
            //运费
            $express = Express::getDetail($product['eid'], 'cost,company_name,id');
            $freightPrice = !empty($express['cost']) ? $express['cost'] : 0;

            //判断是否团购或者是积分购买
            if ($type == 'integral') {
                $payment_type = 0;//积分支付
                if ($product['is_integral'] == 0) {
                    $row['errmsg'] = '非法请求';
                    $row['errno'] = 10001;
                    return $row;
                }
                $goodsIntegral = $product['sp_integral'] * $num;
                if ($userInfo['integral'] < $goodsIntegral) {
                    $row = ['errmsg' => '积分不足', 'errno' => 1, 'data' => []];

                    return $row;
                }
                $order_status = 0;
                if ($freightPrice == 0) {
                    $integralRow = User::updateUserIntegral($this->uid, $goodsIntegral, 0);
                    if ($integralRow == 1) {
                        $finish_time = time();
                        $pay_time = time();
                        $order_status = 1;
                        //修改销售状况
                        GoodsModel::where('id', '=', $goodsId)
                            ->setDec('sp_inventory', $num);
                        GoodsModel::where('id', '=', $goodsId)
                            ->setInc('sp_market', $num);

                    } else {
                        $row['errmsg'] = '网络异常';
                        $row['errno'] = 10001;
                        return $row;
                    }
                }

                $order_type = 2;
                $goodsPrice = ($product['sp_price']) * $num;
                $give_point = 0;
                $coupon_money = 0;

            } elseif ($type == 'collective') {
                if ($product['is_collective'] == 0) {
                    $row['errmsg'] = '非法请求';
                    $row['errno'] = 10001;
                    return $row;
                }

                //团购信息
                $product['collective'] = GoodsCollectiveModel::getCollectiveByGid($product['id']);
                $goodsPrice = $product['collective']['goods_price'] * $num;
                $order_type = 1;
                $give_point = (int)$product['give_integral'] * $num;
                $coupon_money = 0;
            } else {

                if ($virtual == 1 && $product['need_rank'] < $userInfo['rank_id']) {//虚拟货物验证需求等级
                    $row['errmsg'] = '您的会员等级不够，无法购买';
                    $row['errno'] = 10005;
                    return $row;
                }

                $couponId = $this->request->param('couponId');//使用的优惠券ID
                $couponPrice = 0;//优惠券价格
                //优惠券 总数
                if ($couponId != 0) {
                    //选中的优惠券信息
                    $checkedCoupon = Coupon::getInfoById($this->uid, $couponId);
                    //检测优惠券是否可用
                    if (empty($checkedCoupon)) {
                        $row['errmsg'] = '优惠券信息有误';
                        $row['errno'] = 10005;
                        return $row;
                    }
                    $couponPrice = $checkedCoupon['money'] * 100;
                    Coupon::updateCoupon($couponId);
                }
                $coupon_money = $couponPrice;
                $coupon_id = $couponId;

                //判断是否是vip用户 并 判断是否是VIP订单
                if ($userInfo['is_vip'] == 2 && $product['sp_vip_price'] != 0) {
                    $goodsPrice = ($product['sp_vip_price']) * $num;
                    $is_vip = 1;
                    $promotion_money = ($product['sp_price']) * $num - ($product['sp_vip_price']) * $num;
                } else {
                    $goodsPrice = ($product['sp_price']) * $num;
                }

                $order_type = 0;
                $give_point = (int)$product['give_integral'] * $num;
            }
            //最终价格   商品价格 + 运费 - 优惠券金额
            $actualPrice = $goodsPrice + ($freightPrice + 0) - $coupon_money / 100;
        }