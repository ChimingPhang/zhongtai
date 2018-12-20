<?php
return	array(	
	'system'=>array('name'=>'平台','child'=>array(
			array('name' => '设置','child' => array(
					array('name'=>'商城设置','act'=>'index','op'=>'System'),
					//array('name'=>'支付方式','act'=>'index1','op'=>'System'),

//						array('name'=>'地区&配送','act'=>'region','op'=>'Tools'),
//						array('name'=>'地区&配送','act'=>'region','op'=>'Tools'),
					array('name'=>'短信模板','act'=>'index','op'=>'SmsTemplate'),
					//array('name'=>'接口对接','act'=>'index3','op'=>'System'),
					//array('name'=>'验证码设置','act'=>'index4','op'=>'System'),
					 array('name'=>'自定义导航栏','act'=>'navigationList','op'=>'System'),
					// array('name'=>'首页管理','act'=>'floorList','op'=>'Web'),
					//array('name'=>'友情链接','act'=>'linkList','op'=>'Article'),
					array('name'=>'菜单分页','act'=>'index','op'=>'Menu'),
					array('name'=>'底部导航','act'=>'index','op'=>'Footer'),
					array('name'=>'积分商城导航','act'=>'index','op'=>'Integral'),
					array('name'=>'清除缓存','act'=>'cleanCache','op'=>'System')
			)),
			array('name' => '会员','child'=>array(
					array('name'=>'会员列表','act'=>'index','op'=>'User'),
//						array('name'=>'会员等级','act'=>'levelList','op'=>'User'),
					// array('name'=>'充值记录','act'=>'recharge','op'=>'User'),
					//array('name'=>'提现申请','act'=>'withdrawals','op'=>'User'),
					 // array('name'=>'会员签到','act'=>'signList','op'=>'User'),
			)),
			array('name' => '广告','child' => array(
					array('name'=>'广告列表','act'=>'adList','op'=>'Ad'),
					array('name'=>'广告位置','act'=>'positionList','op'=>'Ad'),
			)),
			array('name' => '文章','child'=>array(
					array('name' => '文章列表', 'act'=>'articleList', 'op'=>'Article'),
					 //array('name' => '文章分类', 'act'=>'categoryList', 'op'=>'Article'),
//						array('name' => '帮助管理', 'act'=>'help_list', 'op'=>'Article'),
//						array('name'=>'友情链接','act'=>'linkList','op'=>'Article'),
//						array('name' => '公告管理', 'act'=>'notice_list', 'op'=>'Article'),
//						 array('name' => '专题列表', 'act'=>'topicList', 'op'=>'Topic'),
			)),
			array('name' => '权限','child'=>array(
					array('name' => '管理员列表', 'act'=>'index', 'op'=>'Admin'),
					array('name' => '角色管理', 'act'=>'role', 'op'=>'Admin'),
					array('name'=>'权限资源列表','act'=>'right_list','op'=>'System'),
					array('name' => '管理员日志', 'act'=>'log', 'op'=>'Admin'),
					//array('name' => '供应商列表', 'act'=>'supplier', 'op'=>'Admin'),
			)),

			// array('name' => '模板','child'=>array(
			// 		array('name' => '模板设置', 'act'=>'templateList', 'op'=>'Template'),
			// )),
			//array('name' => '数据','child'=>array(
			//		array('name' => '数据备份', 'act'=>'index', 'op'=>'Tools'),
			//		array('name' => '数据恢复', 'act'=>'restore', 'op'=>'Tools'),
			//))
	)),
		
	'shop'=>array('name'=>'商城','child'=>array(
				array('name' => '商品','child' => array(
                    array('name' => '汽车列表', 'act'=>'carList&pageStatus=1', 'op'=>'Car'),
                    array('name' => '配件列表', 'act'=>'partsList&pageStatus=1', 'op'=>'Parts'),
					array('name' => '车系', 'act'=>'categoryList', 'op'=>'Goods'),

//					array('name' => '车型标签', 'act'=>'goods_label', 'op'=>'Goods'),
				array('name' => '库存日志', 'act'=>'stock_list', 'op'=>'Goods'),
				array('name' => '配件模型', 'act'=>'goodsTypeList', 'op'=>'Goods'),
				array('name' => '配件规格', 'act' =>'specList', 'op' => 'Goods'),
//					array('name' => '品牌列表', 'act'=>'brandList', 'op'=>'Goods'),
//					array('name' => '快递公司', 'act'=>'index', 'op'=>'Shipping'),
				array('name' => '配件分类', 'act'=>'category', 'op'=>'Accessories'),
//				array('name' => '热卖车型', 'act'=>'top', 'op'=>'Car'),
				// array('name' => '库存日志', 'act'=>'brandList', 'op'=>'Goods'),
			)),
			
			array('name' => '经销商','child'=>array(
				array('name' => '经销商', 'act'=>'store_list', 'op'=>'Dealers'),
//					array('name' => '店铺列表', 'act'=>'store_list', 'op'=>'Store'),
//					array('name' => '店铺等级', 'act'=>'store_grade', 'op'=>'Store'),
//					array('name' => '店铺分类', 'act'=>'store_class', 'op'=>'Store'),
//					array('name' => '自营店铺', 'act'=>'store_own_list', 'op'=>'Store'),
//					array('name' => '经营类目审核', 'act'=>'apply_class_list', 'op'=>'Store'),
// 					array('name' => '二级域名', 'act'=>'domain_list', 'op'=>'Store'),
//					array('name' => '店铺满意度', 'act'=>'satisfaction', 'op'=>'Store'),
//					array('name' => '店铺帮助', 'act'=>'helpList', 'op'=>'Article'),
			)),
			array('name' => '订单','child'=>array(

					array('name' => '配件订单', 'act'=>'index', 'op'=>'Order'),
					// array('name' => '配件发货列表订单', 'act'=>'delivery_list', 'op'=>'Order'),
					array('name' => '车型订单', 'act'=>'index', 'op'=>'Carorder'),

					//array('name' => '拼团列表', 'act'=>' ', 'op'=>'Team'),
					//array('name' => '拼团订单', 'act'=>'order_list', 'op'=>'Team'),
					//array('name' => '汽车退款单', 'act'=>'refund_order_list', 'op'=>'Order'),
					array('name' => '汽车退款单', 'act'=>'refund_list', 'op'=>'Service'),
					array('name' => '配件退款单', 'act'=>'access_refund_order_list', 'op'=>'Order'),
					//array('name' => '换货维修', 'act'=>'return_list', 'op'=>'Service'),
					//array('name' => '售后退货', 'act'=>'refund_list', 'op'=>'Service'),
					array('name' => '订单日志', 'act'=>'order_log', 'op'=>'Order'),
					array('name' => '商品评论','act'=>'index','op'=>'Comment'),
					//array('name' => '商品咨询','act'=>'ask_list','op'=>'Comment'),
					//array('name' => '投诉管理','act'=>'complain_list', 'op'=>'Service'),
					//array('name' => '举报管理','act'=>'expose_list', 'op'=>'Service'),
                    //array('name' => '发票管理','act'=>'index', 'op'=>'Invoice'),
			)),

			array('name' => '物流','child'=>array(
				array('name'=>'地区&配送','act'=>'region','op'=>'Tools'),
				array('name' => '快递公司', 'act'=>'index', 'op'=>'Shipping'),
				array('name' => '运费模板', 'act'=>'index', 'op'=>'Freight'),
			)),
			array('name' => '活动','child'=>array(
                array('name'=>'拍卖商品','act'=>'auctionList&pageStatus=1','op'=>'Auction'),
                array('name'=>'保证金','act'=>'signUpList&pageStatus=1','op'=>'Auction'),
                array('name'=>'预约','act'=>'bookingsList&pageStatus=1','op'=>'Auction'),
				array('name' => '秒杀', 'act'=>'auctionList', 'op'=>'Kill'),
			)),
			array('name' => '预约试驾','child'=>array(
				array('name'=>'报名列表','act'=>'index','op'=>'Drive'),
			)),
//			array('name' => '促销','child' => array(
//					array('name' => '抢购管理', 'act'=>'flash_sale', 'op'=>'Promotion'),
//					array('name' => '团购管理', 'act'=>'group_buy_list', 'op'=>'Promotion'),
//					array('name' => '优惠促销', 'act'=>'prom_goods_list', 'op'=>'Promotion'),
//					array('name' => '订单促销', 'act'=>'prom_order_list', 'op'=>'Promotion'),
//					array('name' => '店铺优惠券','act'=>'index', 'op'=>'Coupon'),
//					array('name' => '拼团管理','act'=>'index', 'op'=>'Team'),
//			)),
			
//            array('name' => '分销','child' => array(
//					array('name' => '分销商品列表', 'act'=>'goods_list', 'op'=>'Distribut'),
//					array('name' => '分销商列表', 'act'=>'distributor_list', 'op'=>'Distribut'),
//					array('name' => '分销关系', 'act'=>'tree', 'op'=>'Distribut'),
//					array('name' => '分销设置', 'act'=>'setting', 'op'=>'Distribut'),
//            		array('name' => '分销商等级', 'act'=>'grade_list', 'op'=>'Distribut'),
//					array('name' => '分成日志', 'act'=>'rebate_log', 'op'=>'Distribut'),
//			)),
    	    array('name' => '微信','child' => array(
    	        array('name' => '公众号配置', 'act'=>'index', 'op'=>'Wechat'),
    	        array('name' => '微信菜单管理', 'act'=>'menu', 'op'=>'Wechat'),
    	        array('name' => '自动回复', 'act'=>'auto_reply', 'op'=>'Wechat'),
//                array('name' => '粉丝列表', 'act'=>'fans_list', 'op'=>'Wechat'),
//                array('name' => '模板消息', 'act'=>'template_msg', 'op'=>'Wechat'),
                array('name' => '素材管理', 'act'=>'materials', 'op'=>'Wechat'),
            )),

	       array('name' => '运营','child' => array(
					//array('name' => '商家提现申请', 'act'=>'store_withdrawals', 'op'=>'Finance'),
					//array('name' => '商家转款列表', 'act'=>'store_remittance', 'op'=>'Finance'),
					//array('name' => '会员提现申请', 'act'=>'withdrawals', 'op'=>'Finance'),
					//array('name' => '会员转款列表', 'act'=>'remittance', 'op'=>'Finance'),
					//array('name' => '经销商结算记录', 'act'=>'order_statis', 'op'=>'Operating'),
//			   		array('name' => '经销商退款记录', 'act'=>'return_goods', 'op'=>'Operating'),
	       			array('name' => '平台支出记录', 'act'=>'expense_log', 'op'=>'Operating'),
			)),
			
			array('name' => '统计','child' => array(
					array('name' => '销售概况', 'act'=>'index', 'op'=>'Report'),
					array('name' => '销售排行', 'act'=>'saleTop', 'op'=>'Report'),
					array('name' => '会员排行', 'act'=>'userTop', 'op'=>'Report'),
					array('name' => '销售明细', 'act'=>'saleList', 'op'=>'Report'),
					array('name' => '会员统计', 'act'=>'user', 'op'=>'Report'),
					array('name' => '运营概览', 'act'=>'finance', 'op'=>'Report'),
			)),
	)),
		
	'mobile'=>array('name'=>'手机端','child'=>array(
			array('name' => '设置','child' => array(
					array('name' => '模板设置', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '手机支付', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '微信二维码', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '第三方登录', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '导航管理', 'act'=>'finance', 'op'=>'Report'),
					array('name' => '广告管理', 'act'=>'finance', 'op'=>'Report'),
					array('name' => '广告位管理', 'act'=>'finance', 'op'=>'Report'),
			)),
	)),
		
	'resource'=>array('name'=>'资源','child'=>array(
			array('name' => '云服务','child' => array(
				array('name' => '插件库', 'act'=>'index', 'op'=>'Plugin'),
				//array('name' => '数据备份', 'act'=>'index', 'op'=>'Tools'),
				//array('name' => '数据还原', 'act'=>'restore', 'op'=>'Tools'),
			)),
//            array('name' => 'App','child' => array(
//                array('name' => 'APP基础设置', 'act'=>'basic', 'op'=>'MobileApp'),
//				array('name' => '安卓APP管理', 'act'=>'index', 'op'=>'MobileApp'),
//                array('name' => '苹果APP管理', 'act'=>'ios_audit', 'op'=>'MobileApp'),
//			))
	)),
);