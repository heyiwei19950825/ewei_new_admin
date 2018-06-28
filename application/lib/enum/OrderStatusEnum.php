<?php
/**
 * Created by Ewei.
 * Author: Ewei.
 * 微信公号: 眉山同城

 * Date: 2017/3/7
 * Time: 16:10
 */

namespace app\lib\enum;


class OrderStatusEnum
{
    // 待支付
    const UNPAID = 0;

    // 已支付
    const PAID = 1;

    // 已发货
    const DELIVERED = 3;

    // 已支付，但库存不足
    const PAID_BUT_OUT_OF = 4;

    // 已处理PAID_BUT_OUT_OF
    const HANDLED_OUT_OF = 5;
}