
DROP TABLE IF EXISTS `ddelivery_module_system`;
CREATE TABLE IF NOT EXISTS `ddelivery_module_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL DEFAULT '',
  `rezhim` varchar(64) NOT NULL DEFAULT '',
  `declared` varchar(64) NOT NULL DEFAULT '',
  `width` varchar(64) NOT NULL DEFAULT '',
  `height` varchar(64) NOT NULL DEFAULT '',
  `api` varchar(120) NOT NULL,
  `length` varchar(64) NOT NULL,
  `weight` varchar(64) NOT NULL,
  `payment` varchar(64) NOT NULL,
  `status` varchar(64) NOT NULL,
  `famile` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `def_width` varchar(64) NOT NULL,
  `def_lenght` varchar(64) NOT NULL,
  `def_height` varchar(64) NOT NULL,
  `def_weight` varchar(64) NOT NULL,
  `pvz_companies` varchar(264) NOT NULL,
  `cur_companies` varchar(264) NOT NULL,
  `from1` varchar(64) NOT NULL,
  `to1` varchar(64) NOT NULL,
  `method1` varchar(64) NOT NULL,
  `from2` varchar(64) NOT NULL,
  `to2` varchar(64) NOT NULL,
  `method2` varchar(64) NOT NULL,
  `from3` varchar(64) NOT NULL,
  `to3` varchar(64) NOT NULL,
  `method3` varchar(64) NOT NULL,
  `okrugl` varchar(64) NOT NULL,
  `shag` varchar(64) NOT NULL,
  `zabor` varchar(64) NOT NULL,
  `city1` varchar(64) NOT NULL,
  `curprice1` varchar(64) NOT NULL,
  `city2` varchar(64) NOT NULL,
  `curprice2` varchar(64) NOT NULL,
  `city3` varchar(64) NOT NULL,
  `curprice3` varchar(64) NOT NULL,
  `custom_point` text NOT NULL,
  `methodval1` varchar(64) NOT NULL,
  `methodval2` varchar(64) NOT NULL,
  `methodval3` varchar(64) NOT NULL,
  `delivery_id` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=cp1251 ;


INSERT INTO `ddelivery_module_system` (`id`, `type`, `rezhim`, `declared`, `width`, `height`, `api`, `length`, `weight`, `payment`, `status`, `famile`, `name`, `def_width`, `def_lenght`, `def_height`, `def_weight`) VALUES(1, '0', '0', '100', 'option1', 'option3', '852af44bafef22e96d8277f3227f0998', 'option2', 'weight', '2', '23', 'famile', 'name', '10', '11', '10', '1');

DROP TABLE IF EXISTS `ddelivery_module_cache`;

CREATE TABLE IF NOT EXISTS `ddelivery_module_cache` (
  `id`  int NOT NULL AUTO_INCREMENT ,
  `sig`  varchar(255) NULL ,
  `data_container`  text NULL ,
  `expired`  datetime NULL ,
  PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS `ddelivery_module_orders`;

CREATE TABLE IF NOT EXISTS `ddelivery_module_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_variant` varchar(255) DEFAULT NULL,
  `shop_refnum` varchar (64) DEFAULT NULL,
  `local_status` int(11) DEFAULT NULL,
  `dd_status` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `amount` float(11,2) DEFAULT NULL,
  `products` text DEFAULT NULL,
  `to_city` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `ddeliveryorder_id` int(11) DEFAULT NULL,
  `point_id` int(11) DEFAULT NULL,
  `delivery_company` int(11) DEFAULT NULL,
  `dimension_side1` int(11) DEFAULT NULL,
  `dimension_side2` int(11) DEFAULT NULL,
  `dimension_side3` int(11) DEFAULT NULL,
  `confirmed` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `declared_price` int(11) DEFAULT NULL,
  `payment_price` int(11) DEFAULT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `to_phone` varchar(255) DEFAULT NULL,
  `goods_description` text DEFAULT NULL,
  `to_street` varchar(255) DEFAULT NULL,
  `to_house` varchar(255) DEFAULT NULL,
  `to_flat` varchar(255) DEFAULT NULL,
  `to_email` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `second_name` varchar(255) DEFAULT NULL,
  `serilize` text DEFAULT NULL,
  `point` text DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `city_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;
