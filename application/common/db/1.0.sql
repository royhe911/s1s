CREATE TABLE `s1s_merchant` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键，自增',
  `nickname` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '昵称',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(30) NOT NULL DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '手机号',
  `status` tinyint(4) NOT NULL DEFAULT '3' COMMENT '状态  1-启用 2-禁用 3-待审核 4-审核不通过',
  `balance_amount` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '余额（单位：元）',
  `frozen_amount` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '冻结金额（单位：元）',
  `overdraft_amount` decimal(11,2) DEFAULT '0.00' COMMENT '透支金额（单位：元）',
  `product_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '第几次发布产品',
  `card` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `card_name` varchar(50) NOT NULL DEFAULT '' COMMENT '持卡人姓名',
  `s_id` int(11) NOT NULL DEFAULT '0' COMMENT '业务员id',
  `wx_id` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '微信号',
  `open_id` varchar(128) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT '唯一ID',
  `addtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `reviewtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核通过时间',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='商户表';

CREATE TABLE `s1s_shop` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `m_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家id',
  `shop_name` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '店铺名称',
  `url` varchar(255) CHARACTER SET utf8mb4 NOT NULL COMMENT '店铺链接',
  `wangwang` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '店铺旺旺',
  `status` tinyint(4) NOT NULL COMMENT '店铺状态 1-启用 2-禁用 3-待审核 4-审核不通过',
  `reject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '驳回原因',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `reviewtime` int(11) NOT NULL DEFAULT '0' COMMENT '审核通过时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='商户店铺表';

ALTER TABLE `s1s_admin`
ADD COLUMN `realname` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名' AFTER `id`;