<?php
/**
*
* @package    DDelivery
*
* @author  mrozk
*/
namespace DDelivery;
use DDelivery\Order\DDStatusProvider;
use DDelivery\Adapter\DShopAdapter;
use DDelivery\DataBase\City;
use DDelivery\DataBase\Order;
use DDelivery\DataBase\SQLite;
use DDelivery\Point\DDeliveryPointSelf;
use DDelivery\Sdk\DCache;
use DDelivery\Sdk\DDeliverySDK;
use DDelivery\Order\DDeliveryOrder;
use DDelivery\Adapter\DShopAdapterImpl;
use DDelivery\Point\DDeliveryInfo;
use DDelivery\Point\DDeliveryAbstractPoint;
use DDelivery\Point\DDeliveryPointCourier;
use DDelivery\Sdk\Messager;


/**
 * DDeliveryUI - Обертка рабочих классов, для взаимодействия
 * с системой DDelivery
 *
 * @package  DDelivery
 */
class DDeliveryUI
{
    /**
     * Поддерживаемые способы доставки
     * @var int[]
     */
    public $supportedTypes;
    /**
     * @var int
     */
    public $deliveryType = 0;

    /**
	 * Api обращения к серверу ddelivery
	 *
	 * @var DDeliverySDK
	 */
    public  $sdk;

    /**
     * Адаптер магазина CMS
     * @var DShopAdapter
     */
    private $shop;

    /**
     * Заказ DDelivery
     * @var DDeliveryOrder
     */
    private $order;
    
    /**
     * печаталка сообщений про ошибку
     * @var string
     */
    private $messager;

    /**
     *  Кэш
     *  @var DCache
     */
    private $cache;

    /**
     * @var /PDO бд
     */
    private $pdo;
    /**
     * @var string префикс таблицы
     */
    private $pdoTablePrefix;

    /**
     * Запускает движок SDK
     *
     * @param DShopAdapter $dShopAdapter адаптер интегратора
     * @param bool $skipOrder запустить движок без инициализации заказа  из корзины
     * @throws DDeliveryException
     */
    public function __construct(DShopAdapter $dShopAdapter, $skipOrder = false)
    {
        $this->shop = $dShopAdapter;

        $this->sdk = new Sdk\DDeliverySDK($dShopAdapter->getApiKey(), $this->shop->isTestMode());

        // �?нициализируем работу с БД
        $this->_initDb($dShopAdapter);

        // Формируем объект заказа
        if(!$skipOrder)
        {
            $productList = $this->shop->getProductsFromCart();
            $this->order = new DDeliveryOrder( $productList );
            $this->order->amount = $this->shop->getAmount();
        }
        $this->cache = new DCache( $this, $this->shop->getCacheExpired(), $this->shop->isCacheEnabled(), $this->pdo, $this->pdoTablePrefix );
    }

    /**
     *
     * Залоггировать ошибку
     *
     * @param \Exception $e
     * @return mixed
     */
    public function logMessage( \Exception $e ){
        $logginUrl = $this->shop->getLogginServer();
        if( !is_null( $logginUrl ) ){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_URL, $logginUrl);
            curl_setopt($curl, CURLOPT_POST, true);
            $params = array('message' => $e->getMessage() . ', версия SDK -' . DShopAdapter::SDK_VERSION . ', '
                            . $e->getFile() . ', '
                            . $e->getLine() . ', ' . date("Y-m-d H:i:s"), 'url' => $_SERVER['SERVER_NAME'],
                            'apikey' => $this->shop->getApiKey(),
                            'testmode' => (int)$this->shop->isTestMode());
            $urlSuffix = '';
            foreach($params as $key => $value) {
                $urlSuffix .= urlencode($key).'='.urlencode($value) . '&';
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $urlSuffix);
            $answer = curl_exec($curl);
            curl_close($curl);
            return $answer;
        }
    }
    public function createTables()
    {
        $cache = new DataBase\Cache($this->pdo, $this->pdoTablePrefix);
        $cache->createTable();
        $order = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
        $order->createTable();
    }

    /**
     * Чистим кэш
     */
    public function cleanCache()
    {
        $this->cache->clean();
    }

    /**
     *
     * Получить статус заказа cms по статусу DD
     *
     * @param $ddStatus
     *
     * @return mixed
     */
    public function getLocalStatusByDD( $ddStatus )
    {
        return $this->shop->getLocalStatusByDD( $ddStatus );
    }


    /**
     * Получить все пользовательские поля по ID в БД SQLite
     *
     * Необходимо для того чтобы выставлять заглушки в полях
     * CMS если были заполнены поля DD формы. При обращении Нужно
     * будет конвертировать в json и отправлять
     *
     * @param int $localOrderID
     *
     * @return array
     */
    public function getDDUserInfo( $localOrderID )
    {
        $ids = array( (int)$localOrderID );
        $orderArr = $this->initOrder($ids);
        $order = $orderArr[0];
        return array('firstName' => $order->firstName, 'secondName' => $order->secondName,
                     'toPhone' => $order->toPhone, 'toEmail' => $order->toEmail,
                     'toStreet' => $order->toStreet, 'toHouse' => $order->toHouse,
                     'toFlat' => $order->toFlat
        );
    }

    /**
     * Функция вызывается при изменении статуса внутри cms для отправки
     *
     * @param $cmsID
     * @param $cmsStatus
     *
     * @return int|false
     */
    public function onCmsChangeStatus( $cmsID, $cmsStatus )
    {
        $order = $this->getOrderByCmsID( $cmsID );
        if( $order )
        {
            $order->localStatus = $cmsStatus;
            if( $this->shop->isStatusToSendOrder($cmsStatus) && $order->ddeliveryID == 0 )
            {
                if($order->type == DDeliverySDK::TYPE_SELF)
                {
                    return $this->createSelfOrder($order);
                }
                elseif( $order->type == DDeliverySDK::TYPE_COURIER )
                {
                    return $this->createCourierOrder($order);
                }
            }
        }
        return false;
    }

    /**
     * Отправить order в DD
     * @param DDeliveryOrder $order
     * @param string $cmsID
     * @param int $paymentType
     * @return bool|int
     */
    public function sendOrderToDD($order, $cmsID, $paymentType)
    {
        if(!$order)
            return false;
        $order->shopRefnum = $cmsID;
        $order->paymentVariant = $paymentType;
        if($order->type == DDeliverySDK::TYPE_SELF)
        {
            return $this->createSelfOrder($order);
        }
        elseif( $order->type == DDeliverySDK::TYPE_COURIER )
        {
            return $this->createCourierOrder($order);
        }
        return false;
    }

    /**
     *
     * Получить заказы которые еще не окончили обработку
     * @return DDeliveryOrder[]
     *
     */
    public function getUnfinishedOrders()
    {
        $orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
        $data = $orderDB->getNotFinishedOrders();
        $orderIDs = array();
        $orders = array();
        if(count( $data ))
        {
            foreach( $data as $item )
            {
                $orderIDs[] = $item->id;
            }

            $orders = $this->initOrder( $orderIDs );
        }
        return $orders;
    }
    /**
     * Создать пул заявок по заказам которые еще не закончены
     * и на  которых заявки не созданы
     *
     * @return array
     */
    public function createPullOrders()
    {
        $orderIDs = $this->shop->getOrderIDsByStatus();

        if(is_array( $orderIDs ) && count($orderIDs))
        {
            $result = array();
            foreach( $orderIDs as $el )
            {
                $item = $this->getOrderByCmsID($el);

                if( $item && !$item->ddeliveryID )
                {

                        $item->localStatus = $this->shop->getStatusToSendOrder();

                        if( $item->type == DDeliverySDK::TYPE_SELF)
                        {
                            $ddId = $this->createSelfOrder($item);
                        }
                        else if( $item->type == DDeliverySDK::TYPE_COURIER )
                        {
                            $ddId = $this->createCourierOrder($item);
                        }

                        $result[] = array('ddId' => $ddId, 'localID' => $item->shopRefnum);

                }
            }
            return $result;
        }
    }
    /**
     * Получить статусы для пула заказов которые еще не закончены
     *
     * @return array
     */
    public function getPullOrdersStatus()
    {
        $orders = $this->getUnfinishedOrders();
        $statusReport = array();
        if( count( $orders ) )
        {
            foreach ( $orders as $item)
            {
                $rep = $this->changeOrderStatus( $item );
                if( count( $rep ) )
                {
                    $statusReport[] = $rep;
                }
            }
        }
        return $statusReport;
    }

    /**
     *
     * Получить стоимость доставки по ID заказа
     *
     * @param $localOrderID
     *
     * @throws DDeliveryException
     *
     * @return float
     */
    public function getDeliveryPrice( $localOrderID )
    {
        $ids = array( (int)$localOrderID );
        $orderArr = $this->initOrder($ids);
        $order = $orderArr[0];
        if( $order->getPoint() == null )
        {
            throw new DDeliveryException('Точка не найдена');
        }
        $this->shop->filterSelfInfo( array($order->getPoint()->getDeliveryInfo()) );
        return $order->getPoint()->getDeliveryInfo()->clientPrice;
    }

    /**
     *
     * Получить объект заказа из БД SQLite по его ID в CMS
     *
     * @param int $cmsOrderID id заказа в cms
     *
     * @return DDeliveryOrder
     *
     */
    function getOrderByCmsID( $cmsOrderID )
    {
        $orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
        $data = $orderDB->getOrderByCmsOrderID( $cmsOrderID );

        if( count($data) )
        {
            $ids = array( $data[0]->id );
            $orderArr = $this->initOrder($ids);
            return $orderArr[0];
        }
        else
        {
            return null;
        }
    }

    /**
     *
     * Обработчик изменения статуса заказа
     *
     * @param DDeliveryOrder $order  заказа в cms
     *
     * @return array
     *
     */
    public function changeOrderStatus( $order )
    {
        if( $order )
        {
            if( $order->ddeliveryID == 0 )
            {
                return array();
            }
            $ddStatus = $this->getDDOrderStatus($order->ddeliveryID);

            if( $ddStatus == 0 )
            {
                return array();
            }
            $order->ddStatus = $ddStatus;
            $order->localStatus = $this->shop->getLocalStatusByDD( $order->ddStatus );
            $this->saveFullOrder($order);
            $this->shop->setCmsOrderStatus($order->shopRefnum, $order->localStatus);
            return array('cms_order_id' => $order->shopRefnum, 'ddStatus' => $order->ddStatus,
                         'localStatus' => $order->localStatus );
        }
        else
        {
            return array();
        }
    }

    /**
     *
     * Получает статус заказа на сервере DD
     *
     * @param $ddeliveryOrderID
     *
     * @return int
     */
    public function getDDOrderStatus( $ddeliveryOrderID )
    {   
    	try
    	{
            $response = $this->sdk->getOrderStatus($ddeliveryOrderID);
    	}
    	catch (DDeliveryException $e)
    	{   
    		$this->messager->pushMessage( $e->getMessage() );
    		return 0;
    	}
    	return $response->response['status'];
    }

    /**
     * После окончания оформления заказа вызывается в cms и передает заказ на обработку в DDelivery
     *
     * @param int $id id заказа в локальной БД SQLLite
     * @param string $shopOrderID id заказа в CMS
     * @param int $status выбираются интегратором произвольные
     * @param int $payment выбираются интегратором произвольные
     * @throws DDeliveryException
     *
     * @return bool
     */
    public function onCmsOrderFinish( $id, $shopOrderID, $status, $payment)
    {
        $orders = $this->initOrder( array($id) );
        if(!count($orders))
        {
            return false;
        }
        $order = $orders[0];
        $order->paymentVariant = $payment;
        $order->shopRefnum = $shopOrderID;
        $order->localStatus = $status;

        $id = $this->saveFullOrder($order);
        return (bool)$id;
    }
    
    
    
    /**
     * Устанавливаем для заказа в таблице Orders SQLLite id заказа в CMS
     *
     * @param int $id id локальной БД SQLLite
     * @param int $shopOrderID id заказа в CMS
     * @param string $paymentVariant  вариант оплаты в CMS
     * @param string $status статус заказа
     * 
     * @return bool
     */
    public function setShopOrderID( $id, $paymentVariant, $status, $shopOrderID )
    {
    	$orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
    	return $orderDB->setShopOrderID($id, $paymentVariant, $status, $shopOrderID);
    }



    /**
     * �?нициализирует массив заказов из массива id заказов локальной БД
     *
     * @param int[] $ids массив с id заказов
     *
     * @throws DDeliveryException
     *
     * @return DDeliveryOrder[]
     */
    public function initOrder( $ids )
    {   
    	$orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
        $orderList = array();
        if(!count($ids))
        	throw new DDeliveryException('Пустой массив для инициализации заказа');
        $orders = $orderDB->getOrderList($ids);
       
        if(count($orders))
        {
            foreach ( $orders as $item)
            {   
            	$productList = unserialize( $item->products );
                $currentOrder = new DDeliveryOrder( $productList );
                $this->_initOrderInfo( $currentOrder, $item );
            	$orderList[] = $currentOrder;
            }    
        }
        else 
        {
        	throw new DDeliveryException('Заказ DD в локальной БД не найден');
        }
        return $orderList;
    }


    /**
     * Получить город по ip адресу
     * @var string $ip
     *
     * @return array|null;
     */
    public function getCityByIp( $ip )
    {
        try{
            // Ошибка с падением geoIp не критичная, можем работать дальше
            $response = $this->sdk->getCityByIp( $ip );
        }catch (DDeliveryException $e){
            return null;
        }
    	if( $response->success )
    	{
    		return $response->response;
    	}
    	else
    	{
    		return null;
    	}

    }

    /**
     * Получить объект заказа
     * @var string $ip
     *
     * @return DDeliveryOrder;
     */
    public function getOrder( )
    {
        return $this->order;
    }

    /**
     * Проверяем на валидность $order для получение точек доставки
     *
     * @param DDeliveryOrder $order
     *
     * @return bool
     */
    public function _validateOrderToGetPoints( DDeliveryOrder $order )
    {
        if( count($order->getProducts()) > 0 && $order->city )
        {
            return true;
        }
        return false;
    }

    /**
     * Получить курьерские точки для города
     *
     * @param DDeliveryOrder $order
     * @throws DDeliveryException
     * @return array DDeliveryPointCourier[]
     */
    public function getCourierPointsForCity( DDeliveryOrder $order )
    {
        if(!$this->_validateOrderToGetPoints($order))
            throw new DDeliveryException('Для получения списка необходимо корректный order');
        $points = array();
    	// Есть ли необходимость искать точки на сервере ddelivery
    	if( $this->shop->preGoToFindPoints( $this->order ))
    	{
            $response = $this->getCourierDeliveryInfoForCity($order);

            if( count( $response ) )
            {
                foreach ($response as $p)
                {
                    $point = new \DDelivery\Point\DDeliveryPointCourier( false );
                    $deliveryInfo = new \DDelivery\Point\DDeliveryInfo( $p );
                    $point->setDeliveryInfo($deliveryInfo);
                    $point->pointID = $deliveryInfo->get('delivery_company');
                    $points[] = $point;
                }
    		    usort($points, function($a, $b){
                    /**
                     * @var DDeliveryPointCourier $a
                     * @var DDeliveryPointCourier $b
                     */
                    return $a->delivery_price - $b->delivery_price;
                });
            }
    	}

        $points = $this->shop->filterPointsCourier( $points, $order);
        return $points;
    }

    /**
     * Получить компании самовывоза для города
     * @param DDeliveryOrder $order
     * @throws DDeliveryException
     * @return array;
     */
    public function getCourierDeliveryInfoForCity( DDeliveryOrder $order )
    {
        if(!$this->_validateOrderToGetPoints($order))
            throw new DDeliveryException('Для получения списка необходимо корректный order');

        $declared_price = $this->shop->getDeclaredPrice($order);
        $params = array(
            $order->city, $order->getDimensionSide1(),  $order->getDimensionSide2(),
            $order->getDimensionSide3(), $order->getWeight(), $declared_price
        );

        $sig = 'DDeliverySDK::calculatorCourier:' . implode(':', $params);

        $response = $this->cache->getCache($sig);
        if(!$response){
            $response = $this->sdk->calculatorCourier( $params[0], $params[1], $params[2], $params[3], $params[4], $params[5] );
            $this->cache->setCache($sig, $response, 90);
        }

        if( $response->success )
    	{
    		return $response->response;
    	}
    	else
    	{
    		return array();
    	}
    }

    /**
     *
     * Получить пользовательскую точку по ID
     *
     * @param $pointID
     * @param DDeliveryOrder $order
     *
     * @throws DDeliveryException
     *
     * @return DDeliveryAbstractPoint
     *
     */
    public function getUserPointByID( $pointID, $order )
    {
        $userPoint = null;
        if( $order->type = DDeliverySDK::TYPE_COURIER )
        {
            $points = $this->shop->getUserCourierPoints( $order );
        }
        else if( $order->type = DDeliverySDK::TYPE_SELF )
        {
            $points = $this->shop->getUserSelfPoints( $order );
        }
        if( count($points) )
        {
            foreach( $points as $p )
            {
                if($p->pointID = $pointID)
                {
                    $userPoint = $p;
                    break;
                }
            }
        }

        if( $userPoint == null )
        {
            throw new DDeliveryException('Точка не найдена');
        }
        return $userPoint;
    }

    /**
     *  Получить курьерскую точку по id компании
     *
     * @param $companyID
     * @param $order
     *
     * @return DDeliveryPointCourier|null
     * @throws DDeliveryException
     */
    public function getCourierPointByCompanyID( $companyID, $order )
    {
        $deliveryInfo = $this->getCourierDeliveryInfoForCity($order);
        $courierPoint = null;
        if(count( $deliveryInfo ))
        {
            foreach( $deliveryInfo as $di )
            {
                if ( $di['delivery_company'] == $companyID )
                {
                    $courierPoint = new DDeliveryPointCourier(false);
                    $courierPoint->setDeliveryInfo( new DDeliveryInfo($di) );
                    break;
                }
            }
        }
        if( $courierPoint == null )
        {
            throw new DDeliveryException('Точка не найдена');
        }

        $this->shop->filterPointsCourier(array($courierPoint), $this->getOrder());
        return $courierPoint;
    }

    /**
     *
     * Получить всю информацию по точке по ее ID
     *
     * @param int $pointId id точки
     * @param DDeliveryOrder $order
     *
     * @return DDeliveryPointSelf
     * @throws DDeliveryException
     */
    public function getSelfPointByID( $pointId, $order )
    {
        if(!$this->_validateOrderToGetPoints( $order))
            throw new DDeliveryException('Not valid order');
        //$points = $this->cache->render( 'getSelfPointsDetail', array( $order->city ) );

        $points = $this->getSelfPointsDetail( $order->city );

        $selfPoint = null;
        if(count($points))
        {
            foreach( $points AS $p )
            {
                if( $p->_id == $pointId )
                {
                    $selfPoint = $p;
                    break;
                }
            }
        }
        if( $selfPoint == null )
        {
            throw new DDeliveryException('Point not found');
        }
        /**
         * @var DDeliveryPointSelf $selfPoint
         */
        $deliveryInfo = $this->getDeliveryInfoForPointID( $pointId, $order );
        $selfPoint->setDeliveryInfo($deliveryInfo);
        return $selfPoint;
    }

    /**
     * Получить компании самовывоза  для города с их полным описанием, и координатами их филиалов
     * @param DDeliveryOrder $order
     * @throws DDeliveryException
     * @return DDeliveryPointSelf[]
     */
    public function getSelfPoints( DDeliveryOrder $order )
    {
        if(!$this->_validateOrderToGetPoints( $order))
            throw new DDeliveryException('Not valid order');
        // Есть ли необходимость искать точки на сервере ddelivery
        $result_points = array();
        if( $this->shop->preGoToFindPoints( $order ))
        {
            // $points = $this->cache->render( 'getSelfPointsDetail', array( $order->city ) ); /** cache **/
            $points = $this->getSelfPointsDetail( $order->city ); /** cache **/

            $companyInfo = $this->getSelfDeliveryInfoForCity( $order );

            $deliveryInfo = $this->_getOrderedDeliveryInfo( $companyInfo );

            if( count( $points ) )
            {
                foreach ( $points as $item )
                {
                    $companyID = $item->get('company_id');

                    if( array_key_exists( $companyID, $deliveryInfo ) )
                    {
                        $item->setDeliveryInfo( $deliveryInfo[$companyID] );
                        $item->pointID = $item->get('_id');
                        $result_points[] = $item;
                    }
                }
            }
        }
        $points = $this->shop->filterPointsSelf( $result_points , $order, $order->city );

        return $points;

    }


    /**
     * Получить компании самовывоза для города
     *
     * @param DDeliveryOrder $order
     * @throws DDeliveryException
     *
     * @return array
     */
    public function getSelfDeliveryInfoForCity( DDeliveryOrder $order )
    {
        $declared_price = $this->shop->getDeclaredPrice($order);
        $params = array(
            $order->city, $order->getDimensionSide1(), $order->getDimensionSide2(),
            $order->getDimensionSide3(), $order->getWeight(), $declared_price
        );

        $sig = 'DDeliverySDK::calculatorPickupForCity:' . implode(':', $params);

        $response = $this->cache->getCache($sig);
        if(!$response){
            $response = $this->sdk->calculatorPickupForCity( $params[0], $params[1], $params[2], $params[3], $params[4], $params[5]);
            $this->cache->setCache($sig, $response, 90);
        }
    	if( $response->success )
    	{
    		return $response->response;
    	}
        return 0;
    }


    /**
     * Получить информацию о самовывозе для точки
     *
     * @param int $pointID
     * @param DDeliveryOrder $order
     *
     * @return DDeliveryInfo
     */
    public function getDeliveryInfoForPointID( $pointID, DDeliveryOrder $order )
    {

        $declared_price = $this->shop->getDeclaredPrice($order);
    	$response = $this->sdk->calculatorPickupForPoint( $pointID, $order->getDimensionSide1(),
                                                          $order->getDimensionSide2(),
                                                          $order->getDimensionSide3(),
                                                          $order->getWeight(), $declared_price );
    	if( $response->success )
    	{
    		return new Point\DDeliveryInfo( reset($response->response) );
    	}
    	else
    	{
    		return null;
    	}
    }

    /**
     * Для удобства перебора сортируем массив объектов deliveryInfo
     *
     * @param array $companyInfo
     * @return Point\DDeliveryInfo[]
     */
    private function _getOrderedDeliveryInfo( $companyInfo )
    {
    	$deliveryInfo = array();
    	foreach ( $companyInfo as $c )
    	{
    		$id = $c['delivery_company'];
    		$deliveryInfo[$id] = new Point\DDeliveryInfo( $c );
    	}
    	return $deliveryInfo;
    }

    /**
     *
     * Здесь проверяется заполнение всех данных для заказа
     *
     * @param DDeliveryOrder $order заказ ddelivery
     * @throws DDeliveryException
     * @return bool
     */
    public function checkOrderCourierValues( $order )
    {

    	$errors = array();
    	$point = $order->getPoint();

        if( $point == null )
        {
        	$errors[] = "Укажите пожалуйста точку";
        }
        if(!strlen( $order->getToName() ))
        {
        	$errors[] = "Укажите пожалуйста Ф�?О";
        }
        if(!$this->isValidPhone( $order->toPhone ))
        {
        	$errors[] = "Укажите пожалуйста телефон в верном формате";
        }
        if( $order->type != DDeliverySDK::TYPE_COURIER )
        {
        	$errors[] = "Не верный тип доставки";
        }
        if( !strlen( $order->toStreet ) )
        {
        	$errors[] = "Укажите пожалуйста улицу";
        }
        if(!strlen( $order->toHouse ))
        {
        	$errors[] = "Укажите пожалуйста дом";
        }
        if(!$order->city)
        {
        	$errors[] = "Город не определен";
        }
        if( !strlen( $order->toFlat ) )
        {
        	$errors[] = "Укажите пожалуйста квартиру";
        }
        if(!empty($order->toEmail))
        {
            if(!$this->isValidEmail($order->toEmail))
            {
            	$errors[] = "Укажите пожалуйста email в верном формате";
            }
        }

        if( empty( $order->paymentVariant ) )
        {
        		$errors[] = "Не указан способ оплаты в CMS";
        }

        if( empty( $order->localStatus ) )
        {
        	$errors[] = "Не указан статус заказа в CMS";
        }

        if( ! $order->shopRefnum )
        {
        	$errors[] = "Не найден id заказа в CMS";
        }

        if(count($errors))
        {
            throw new DDeliveryException(implode(', ', $errors));
        }
    }

    /**
     *
     * Перед отправкой заказа самовывоза на сервер DDelivery проверяется
     * заполнение всех данных для заказа
     *
     * @param DDeliveryOrder $order заказ ddelivery
     *
     * @throws DDeliveryException
     * @return bool
     */

    public function checkOrderSelfValues( $order )
    {
    	$errors = array();
    	$point = $order->getPoint();

        if( $point == null )
        {
        	$errors[] = "Укажите пожалуйста точку";
        }
        if(!strlen( $order->getToName() ))
        {
        	$errors[] = "Укажите пожалуйста Ф�?О";
        }
        if(!$this->isValidPhone( $order->toPhone ))
        {
        	$errors[] = "Укажите пожалуйста телефон в верном формате";
        }
        if( $order->type != DDeliverySDK::TYPE_SELF )
        {
        	$errors[] = "Не верный тип доставки";
        }

        if( empty( $order->paymentVariant ) )
        {
        	$errors[] = "Не указан способ оплаты в CMS";
        }

        if( empty( $order->localStatus ) )
        {
        	$errors[] = "Не указан статус заказа в CMS";
        }

        if( ! $order->shopRefnum )
        {
        	$errors[] = "Не найден id заказа в CMS";
        }

        if(count($errors))
        {
        	throw new DDeliveryException(implode(', ', $errors));
        }
        return true;
    }

    /**
     *
     * Сохранить в локальную БД заказ
     *
     * @param DDeliveryOrder $order заказ ddelivery
     *
     * @return int
     */
    public function saveFullOrder( DDeliveryOrder $order )
    {
    	$orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
    	$id = $orderDB->saveFullOrder( $order );
    	return $id;
    }

    /**
     *
     * отправить заказ на курьерку
     *
     * @param DDeliveryOrder $order
     *
     * @return int
     */
    public function createCourierOrder( $order )
    {
    	/** @var DDeliveryPointCourier $point */

        $order->toPhone = $this->formatPhone( $order->toPhone );
        $cv = $this->checkOrderCourierValues( $order );
        if( !$cv )
            return false;

    	$ddeliveryOrderID = 0;

    	if( $this->shop->sendOrderToDDeliveryServer($order) )
    	{
    	    $point = $order->getPoint();

    	    $to_city = $order->city;
    	    $delivery_company = $point->getDeliveryInfo()->get('delivery_company');

    	    $dimensionSide1 = $order->getDimensionSide1();
    	    $dimensionSide2 = $order->getDimensionSide2();
    	    $dimensionSide3 = $order->getDimensionSide3();

    	    $goods_description = $order->getGoodsDescription();
    	    $weight = $order->getWeight();
    	    $confirmed = $this->shop->isConfirmedStatus($order->localStatus);

    	    $to_name = $order->getToName();
    	    $to_phone = $order->getToPhone();

    	    $orderPrice = $point->getDeliveryInfo()->clientPrice;

    	    $declaredPrice = $this->shop->getDeclaredPrice( $order );
    	    $paymentPrice = $this->shop->getPaymentPriceCourier( $order, $orderPrice );

    	    $to_street = $order->toStreet;
    	    $to_house = $order->toHouse;
    	    $to_flat = $order->toFlat;
    	    $shop_refnum = $order->shopRefnum;

            try
            {
    	        $response = $this->sdk->addCourierOrder( $to_city, $delivery_company, $dimensionSide1, $dimensionSide2,
    			                                         $dimensionSide3, $shop_refnum, $confirmed, $weight,
    	        		                                 $to_name, $to_phone, $goods_description, $declaredPrice,
    	          	                                     $paymentPrice, $to_street, $to_house, $to_flat );

            }
            catch ( DDeliveryException $e )
            {
                $this->messager->pushMessage( $e->getMessage());
                return 0;
            }
    	    $ddeliveryOrderID = $response->response['order'];
    	}
    	$order->ddeliveryID = $ddeliveryOrderID;
        if( $confirmed )
        {
            $order->ddStatus = DDStatusProvider::ORDER_CONFIRMED;
        }
        else
        {
            $order->ddStatus = DDStatusProvider::ORDER_IN_PROGRESS;
        }
    	$this->saveFullOrder( $order );

    	return $ddeliveryOrderID;
    }


    /**
     *
     * отправить заказ на самовывоз
     *
     * @param DDeliveryOrder $order
     *
     * @return int
     *
     */
    public function createSelfOrder( $order )
    {
        /** @var DDeliveryPointSelf $point */
        $order->toPhone = $this->formatPhone( $order->toPhone );
        $cv = $this->checkOrderSelfValues( $order );
        if( !$cv )
            return false;

    	if(! $this->shop->sendOrderToDDeliveryServer($order) ) {
            return 0;
        } else {
    	    $point = $order->getPoint();
    	    $pointID = $point->get('_id');
    	    $dimensionSide1 = $order->getDimensionSide1();
    	    $dimensionSide2 = $order->getDimensionSide2();
    	    $dimensionSide3 = $order->getDimensionSide3();
    	    $goods_description = $order->getGoodsDescription();
    	    $weight = $order->getWeight();
    	    $confirmed = $this->shop->isConfirmedStatus($order->localStatus);
    	    $to_name = $order->getToName();
    	    $to_phone = $order->getToPhone();
    	    $orderPrice = $point->getDeliveryInfo()->clientPrice;
    	    $declaredPrice = $this->shop->getDeclaredPrice( $order );
    	    $paymentPrice = $this->shop->getPaymentPriceSelf( $order, $orderPrice );
    	    $shop_refnum = $order->shopRefnum;
    	    try
    	    {
    	        $response = $this->sdk->addSelfOrder( $pointID, $dimensionSide1, $dimensionSide2,
    				                                  $dimensionSide3, $confirmed, $weight, $to_name,
    				                                  $to_phone, $goods_description, $declaredPrice,
    				                                  $paymentPrice, $shop_refnum );
    	    }
    	    catch ( DDeliveryException $e )
    	    {
    	    	$this->messager->pushMessage( $e->getMessage() );
    	    	return 0;
    	    }
    	    $ddeliveryOrderID = $response->response['order'];
    	}
    	$order->ddeliveryID = $ddeliveryOrderID;
        if( $confirmed )
        {
            $order->ddStatus = DDStatusProvider::ORDER_CONFIRMED;
        }
        else
        {
            $order->ddStatus = DDStatusProvider::ORDER_IN_PROGRESS;
        }

        $this->saveFullOrder( $order );

    	return $ddeliveryOrderID;
    }
    /**
     * Весь список заказов
     *
     */
    public function getAllOrders()
    {
    	$orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
    	return $orderDB->selectAll();
    }




    /**
     * Получить информацию о точке самовывоза по ее ID  и по ID города
     *
     * @param mixed $cityID
     * @param mixed $companyIDs
     *
     * @return DDeliveryPointSelf[]
     */
    public function getSelfPointsDetail( $cityID, $companyIDs = null )
    {

    	$points = array();

    	$response = $this->sdk->getSelfDeliveryPoints( $companyIDs, $cityID );

    	if( $response->success )
    	{
    		foreach ( $response->response as $p )
    		{
    			$point = new DDeliveryPointSelf( false );
                $point->init( $p );
                $points[] = $point;
    		}
    	}


    	return $points;
    }
    /**
     * Проверяем правильность Email
     *
     * @param string $email
     *
     * @return boolean
     */
    public function isValidEmail( $email )
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL))
        {
        	return true;
        }
        return false;
    }

    /**
     * Вырезаем из номера телефона ненужные символы
     *
     * @param string $phone
     *
     * @return string
     */
    public function formatPhone( $phone )
    {
        return preg_replace( array('/-/', '/\(/', '/\)/', '/\+7/', '/\s\s+/'), '', $phone );
    }

    /**
     * Проверяем правильность телефона
     *
     * @param string $phone
     *
     * @return boolean
     */
    public function isValidPhone( $phone )
    {
    	if( preg_match('/^[0-9]{10}$/', $phone) )
    	{
    		return true;
    	}
    	return false;
    }

    /**
     * Назначить точку доставки
     *
     */
    public function setOrderPoint( $point )
    {
    	$this->order->setPoint( $point );
    }

    /**
     * Назначить номер телефона доставки
     *
     */
    public function setOrderToPhone( $phone )
    {
    	$this->order->toPhone = trim( strip_tags( $phone ) );
    }

    /**
     * Назначить Ф�?О доставки
     *
     */
    public function setOrderToName( $name )
    {
    	$this->order->toName = trim( strip_tags( $name ) );
    }

    /**
     * Назначить квартиру доставки
     *
     */
    public function setOrderToFlat( $flat )
    {
    	$this->order->toFlat = trim( strip_tags( $flat ) );
    }

    /**
     * Назначить дом для доставки
     *
     */
    public function setOrderToHouse( $house )
    {
    	$this->order->toHouse = trim( strip_tags( $house ) );
    }

    /**
     * Назначить email для доставки
     *
     */
    public function setOrderToEmail( $email )
    {
    	$this->order->toEmail = trim( strip_tags( $email ) );
    }

    /**
     * Возвращает id текущего города или пытается определить его
     * @return int
     */
    protected function getCityId()
    {
        if($this->order->city) {
            return $this->order->city;
        }

        $cityId = (int)$this->shop->getClientCityId();

        if(!$cityId){
            $cityRaw = $this->getCityByIp($_SERVER['REMOTE_ADDR']);
            if($cityRaw && $cityRaw['city_id']) {
                $cityId = (int)$cityRaw['city_id'];
            }
            if(!$cityId) {
                $topCityId = $this->sdk->getTopCityId();
                $cityId = reset($topCityId); // Самый большой город
            }
        }
        return $cityId;
    }

    /**
     * Вызывается для рендера текущей странички
     * @param array $request
     * @throws DDeliveryException
     */
    public function render($request)
    {
        if(!empty($request['order_id'])) {
            $orders =  $this->initOrder( array($request['order_id']) );
            $this->order = $orders[0];
        }
        if(!empty($request['city_alias'])) {
            $this->order->cityName = strip_tags( $request['city_alias'] );
        }
        if(isset($request['action'])) {
            switch($request['action']) {
                case 'searchCity':
                case 'searchCityMap':
                    if(isset($request['name']) && mb_strlen($request['name']) >= 3){
                        $cityList = $this->sdk->getAutoCompleteCity($request['name']);

                        $cityList = $cityList->response;
                        foreach($cityList as $key => $city){
                            $cityList[$key]['name'] = Utils::firstWordLiterUppercase($city['name']);
                        }

                        $cityId = $this->order->city;
                        $displayData = array();
                        $content = '';
                        if($request['action'] == 'searchCity'){
                            ob_start();
                            include(__DIR__ . '/../../templates/cityHelper.php');
                            $content = ob_get_contents();
                            ob_end_clean();
                        }else{ // searchCityMap
                            foreach($cityList as $cityData){
                                $displayDataCur = array(
                                    'id'=>$cityData['_id'],
                                    'name'=>$cityData['type'].'. '.$cityData['name'],
                                );

                                if($cityData['name'] != $cityData['region']) {
                                    $displayDataCur['name'] .= ', '.$cityData['region'].' обл.';
                                }
                                $displayData[] = $displayDataCur;
                            }
                        }

                        echo json_encode(array(
                            'html'=>$content,
                            'displayData'=>$displayData,
                            'request'=>array(
                                'name'=>$request['name'],
                                'action'=>'searchCity'
                            )
                        ));
                    }
                    return;
                case 'mapGetPoint':
                    if(!empty($request['id'])) {
                        if(isset($request['custom']) && $request['custom']) {
                            $points = $this->shop->filterPointsSelf(array(), $this->getOrder());
                            $pointSelf = false;
                            foreach($points as $point) {
                                if($point->_id == $request['id']) {
                                    $pointSelf = $point;
                                    break;
                                }
                            }
                        }else{
                            $pointSelf = $this->getSelfPointByID((int)$request['id'], $this->order);
                        }
                        if(empty($pointSelf)) {
                            echo json_encode(array('point'=>array()));
                            return;
                        }
                        if(empty($pointSelf->is_custom)) {
                            $selfCompanyList = $this->shop->filterSelfInfo(array($pointSelf->getDeliveryInfo()));
                            if(empty($selfCompanyList)){
                                echo json_encode(array('point'=>array()));
                                return;
                            }
                        }
                        $pointSelf->description_in = iconv('UTF-8','CP1251', $pointSelf->description_in);
                        echo json_encode(array(
                            'point'=>array(
                                'description_in' => $pointSelf->description_in,
                                'description_out' => $pointSelf->description_out,
                                'indoor_place' => $pointSelf->indoor_place,
                                'metro' => trim($pointSelf->metro),
                                'schedule' => $pointSelf->schedule,
                                'total_price' => $pointSelf->getDeliveryInfo()->clientPrice,
                                'delivery_time_min' => $pointSelf->getDeliveryInfo()->delivery_time_min,
                                'delivery_time_min_str' => Utils::plural($pointSelf->getDeliveryInfo()->delivery_time_min, 'дня', 'дней', 'дней', 'дней', false),
                            ),
                        ));
                    }
                    return;
            }
        }

        if(isset($request['iframe'])) {
            $staticURL = $this->shop->getStaticPath();
            $scriptURL = $this->shop->getPhpScriptURL();
            $version = DShopAdapter::SDK_VERSION;
            include(__DIR__ . '/../../templates/iframe.php');
            return;
        }

        if(!empty($request['city_id'])) {
            $this->order->city = $request['city_id'];
        }

        if(!$this->order->city ) {
            $this->order->city = $this->getCityId();
        }
        if(!empty($request['point']) && isset($request['type'])) {
            if ( $request['type'] == DDeliverySDK::TYPE_SELF ) {
                if(isset($request['custom']) && $request['custom']) {
                    $points = $this->shop->filterPointsSelf(array(), $this->getOrder());
                    $pointSelf = false;
                    foreach($points as $point) {
                        if($point->_id == $request['point']) {
                            $pointSelf = $point;
                            break;
                        }
                    }
                }else{
                    $pointSelf = $this->getSelfPointByID((int)$request['point'], $this->order);
                }
                $this->order->setPoint($pointSelf);
            }elseif($request['type'] == DDeliverySDK::TYPE_COURIER){
                $this->order->setPoint($this->getCourierPointByCompanyID($request['point'], $this->order));
            }
        }
        if(!empty($request['contact_form']) && is_array($request['contact_form'])) {
            if(!empty($request['contact_form'])) {
                foreach($request['contact_form'] as $row) {
                    switch($row['name']){
                        case 'second_name':
                            $this->order->secondName = $row['value'];
                            break;
                        case 'first_name':
                            $this->order->firstName = $row['value'];
                            break;
                        case 'phone':
                            $this->order->toPhone = $row['value'];
                            break;
                        case 'address':
                            $this->order->toStreet = $row['value'];
                            break;
                        case 'address_house':
                            $this->order->toHouse = $row['value'];
                            break;
                        case 'address_housing':
                            $this->order->toHousing = $row['value'];
                            break;
                        case 'address_flat':
                            $this->order->toFlat = $row['value'];
                            break;
                        case 'comment':
                            //@todo Комента нет
                            //$this->order->toHousing = $row['value'];
                            break;
                    }
                }
            }
        }


        $supportedTypes = $this->shop->getSupportedType();

        if(!is_array($supportedTypes))
            $supportedTypes = array($supportedTypes);

        $this->supportedTypes = $supportedTypes;

        if(empty($request['action'])) {

            $deliveryType = (int) (isset($request['type']) ? $request['type'] : 0);
            // Проверяем поддерживаем ли мы этот тип доставки
            if($deliveryType && !in_array($deliveryType, $supportedTypes)) {
                $deliveryType = 0;
            }

            // Неизвестно какой экшен, выбираем
            if(count($supportedTypes) > 1 && !$deliveryType) {
                $request['action'] = 'typeForm';
            }else{
                if(!$deliveryType)
                    $deliveryType = reset($supportedTypes);
                $this->deliveryType = $deliveryType;

                if($deliveryType == DDeliverySDK::TYPE_SELF){
                    $request['action'] = 'map';
                }elseif($deliveryType == DDeliverySDK::TYPE_COURIER){
                    $request['action'] = 'courier';
                }else{
                    throw new DDeliveryException('Not support delivery type');
                }
            }
        }

        $this->order->localId = $this->saveFullOrder($this->order);

        switch($request['action']) {
            case 'map':
                echo $this->renderMap();
                break;
            case 'mapDataOnly':
                echo $this->renderMap(true);
                break;
            case 'courier':
                echo $this->renderCourier();
                break;
            case 'typeForm':
                echo $this->renderDeliveryTypeForm();
                break;
            case 'typeFormDataOnly':
                echo $this->renderDeliveryTypeForm(true);
                break;
            case 'contactForm':
                echo $this->renderContactForm();
                break;
            case 'change':
                echo $this->renderChange();
                break;
            default:
                throw new DDeliveryException('Not support action');
                break;
        }
    }

    private function renderChange()
    {
        $comment = '';
        $point = $this->order->getPoint();
        if ($point instanceof DDeliveryPointSelf) {
            $comment = 'Самовывоз, '.$point->address;
            $point = $this->getSelfPointByID($point->_id, $this->order);
            $this->shop->filterSelfInfo(array($point->getDeliveryInfo()));
        } elseif($point instanceof DDeliveryPointCourier)    {
            $comment = 'Доставка курьером по адресу '.$this->order->getFullAddress();
            $this->getCourierPointByCompanyID($point->getDeliveryInfo()->delivery_company, $this->order);
        }
        $this->saveFullOrder($this->order);

        $this->shop->onFinishChange($this->order->localId, $this->order, $point);

        $returnArray = array(
                        'html'=>'',
                        'js'=>'change',
                        'comment'=>htmlspecialchars($comment),
                        'orderId' => $this->order->localId,
                        'clientPrice'=>$point->getDeliveryInfo()->clientPrice,
                        'userInfo' => $this->getDDUserInfo($this->order->localId),
                        );
        $returnArray = $this->shop->onFinishResultReturn( $this->order, $returnArray );
        return json_encode( $returnArray );
    }

    /**
     * Получаем массив городов для отображения на странцие
     * @param $cityId
     * @return array
     */
    protected function getCityByDisplay($cityId)
    {
        $cityDB = new City($this->pdo, $this->pdoTablePrefix);
        $cityList = $cityDB->getTopCityList();
        // Складываем массивы получаем текущий город наверху, потом его и выберем
        if(isset($cityList[$cityId])){
            $cityData = $cityList[$cityId];
            unset($cityList[$cityId]);
            array_unshift($cityList, $cityData);
        }
        $avalibleCities = array();
        foreach($cityList as &$cityData){
            // Костыль, на сервере города начинаются с маленькой буквы
            $cityData['name'] = Utils::firstWordLiterUppercase($cityData['name']);

            //Собирает строчку с названием города для отображения
            $displayCityName = $cityData['type'].'. '.$cityData['name'];
            if($cityData['region'] != $cityData['name']) {
                $displayCityName .= ', '.$cityData['region'].' обл.';
            }

            $cityData['display_name'] = $displayCityName;
            $avalibleCities[] = $cityData['_id'];
        }
        if( !in_array($cityId, $avalibleCities) ){
           $topCity = array('_id' => $cityId, 'display_name' => $this->order->cityName );
           array_unshift($cityList, $topCity);
        }

        return $cityList;
    }

    /**
     * Страница с картой
     *
     * @param bool $dataOnly ajax
     * @return string
     */
    protected function renderMap($dataOnly = false)
    {
        $this->getOrder()->type = DDeliverySDK::TYPE_SELF;
        $cityId = $this->order->city;

        $points = $this->getSelfPoints($this->order);
        $this->saveFullOrder($this->getOrder());
        $pointsJs = array();

        foreach($points as $point) {
            $pointsJs[] = $point->toJson();
        }
        $staticURL = $this->shop->getStaticPath();
        $selfCompanyList = $this->getSelfDeliveryInfoForCity( $this->order );
        $selfCompanyList = $this->_getOrderedDeliveryInfo( $selfCompanyList );
        $selfCompanyList = $this->shop->filterSelfInfo($selfCompanyList);

        if($dataOnly) {
            ob_start();
            include(__DIR__ . '/../../templates/mapCompanyHelper.php');
            $content = ob_get_contents();
            ob_end_clean();
            $dataFromHeader = $this->getDataFromHeader();

            return json_encode(array('html'=>$content, 'points' => $pointsJs, 'orderId' => $this->order->localId, 'headerData' => $dataFromHeader));
        } else {
            $cityList = $this->getCityByDisplay($cityId);
            $headerData = $this->getDataFromHeader();
            ob_start();
            include(__DIR__ . '/../../templates/map.php');
            $content = ob_get_contents();
            ob_end_clean();
            return json_encode(array('html'=>$content, 'js'=>'map', 'points' => $pointsJs, 'orderId' => $this->order->localId, 'type'=>DDeliverySDK::TYPE_SELF));
        }
    }

    protected function getDataFromHeader()
    {
        $cityId = $this->order->city;

        $order = $this->order;
        $order->city = $cityId;
        $data = array(
            'self' => array(
                'minPrice' => 0,
                'minTime' => 0,
                'timeStr' => '',
                'disabled' => true,
            ),
            'courier' => array(
                'minPrice' => 0,
                'minTime' => 0,
                'timeStr' => '',
                'disabled' => true
            ),
        );

        if(in_array(Sdk\DDeliverySDK::TYPE_SELF, $this->supportedTypes)) {
            $selfCompanyList = $this->getSelfDeliveryInfoForCity( $this->order );
            if(!empty($selfCompanyList)){
                $selfCompanyList = $this->_getOrderedDeliveryInfo( $selfCompanyList );
                $selfCompanyList = $this->shop->filterSelfInfo($selfCompanyList);
                if(!empty($selfCompanyList)) {
                    $minPrice = PHP_INT_MAX;
                    $minTime = PHP_INT_MAX;
                    foreach($selfCompanyList as $selfCompany) {
                        if($minPrice > $selfCompany->clientPrice){
                            $minPrice = $selfCompany->clientPrice;
                        }
                        if($minTime > $selfCompany->delivery_time_min){
                            $minTime = $selfCompany->delivery_time_min;
                        }
                    }
                    $data['self'] = array(
                        'minPrice' => $minPrice,
                        'minTime' => $minTime,
                        'timeStr' => Utils::plural($minTime, 'дня', 'дней', 'дней', 'дней', false),
                        'disabled' => false
                    );
                }
            }
        }
        if(in_array(Sdk\DDeliverySDK::TYPE_COURIER, $this->supportedTypes)) {
            $courierCompanyList = $this->getCourierPointsForCity($this->order);
            if(!empty($courierCompanyList)){
                $minPrice = PHP_INT_MAX;
                $minTime = PHP_INT_MAX;

                foreach($courierCompanyList as $courierCompany){
                    $deliveryInfo = $courierCompany->getDeliveryInfo();
                    if($minPrice > $deliveryInfo->clientPrice) {
                        $minPrice = $deliveryInfo->clientPrice;
                    }
                    if($minTime > $deliveryInfo->delivery_time_min) {
                        $minTime = $deliveryInfo->delivery_time_min;
                    }
                }
                $data['courier'] = array(
                    'minPrice' => $minPrice,
                    'minTime' => $minTime,
                    'timeStr' => Utils::plural($minTime, 'дня', 'дней', 'дней', 'дней', false),
                    'disabled' => false
                );

            }
        }
        return $data;
    }

    /**
     * Возвращает страницу с формой выбора способа доставки
     * @param bool $dataOnly если передать true, то отдаст данные для обновления верстки через js
     * @return string
     */
    protected function renderDeliveryTypeForm( $dataOnly = false )
    {
        $staticURL = $this->shop->getStaticPath();
        $cityId = $this->order->city;

        $order = $this->order;
        $order->declaredPrice = $this->shop->getDeclaredPrice($order);
        $order->city = $cityId;

        $data = $this->getDataFromHeader();

        if(!$dataOnly) {
            // Рендер html
            $cityList = $this->getCityByDisplay($cityId);

            ob_start();
            include(__DIR__.'/../../templates/typeForm.php');
            $content = ob_get_contents();
            ob_end_clean();

            return json_encode(array('html'=>$content, 'js'=>'typeForm', 'orderId' => $this->order->localId, 'typeData' => $data));
        }else{
            return json_encode(array('typeData' => $data));
        }
    }

    /**
     * @return string
     */
    protected function renderCourier()
    {
        $this->getOrder()->type = DDeliverySDK::TYPE_COURIER;
        $this->saveFullOrder($this->getOrder());
        $cityId = $this->order->city;
        $cityList = $this->getCityByDisplay($cityId);
        $companies = $this->getCompanySubInfo();
        $courierCompanyList = $this->getCourierPointsForCity($this->order);

        $staticURL = $this->shop->getStaticPath();
        // Ресетаем ключи.
        $courierCompanyList = array_values($courierCompanyList);
        $headerData = $this->getDataFromHeader();

        ob_start();
        include(__DIR__.'/../../templates/couriers.php');
        $content = ob_get_contents();
        ob_end_clean();

        return json_encode(array('html'=>$content, 'js'=>'courier', 'orderId' => $this->order->localId,
            'type'=>DDeliverySDK::TYPE_COURIER, 'typeData' => $headerData));
    }

    /**
     * @return string
     */
    private function renderContactForm()
    {
        $point = $this->getOrder()->getPoint();
        if(!$point){
            return '';
        }

        $cityDB = new City($this->pdo, $this->pdoTablePrefix);
        // $currentCity = $cityDB->getCityById($this->getOrder()->city);

        //Собирает строчку с названием города для отображения
        /*
        $displayCityName = $currentCity['type'].'. '.$currentCity['name'];
        if($currentCity['region'] != $currentCity['name']) {
            $displayCityName .= ', '.$currentCity['region'].' обл.';
        }
        */
        $displayCityName = $this->order->cityName;
        $type = $this->getOrder()->type;
        if($this->getOrder()->type == DDeliverySDK::TYPE_COURIER) {
            $displayCityName.=', '.$point->getDeliveryInfo()->delivery_company_name;
            $requiredFieldMask = $this->shop->getCourierRequiredFields();
        }elseif($this->getOrder()->type == DDeliverySDK::TYPE_SELF) {
            $displayCityName.=' '. $point->address;
            $requiredFieldMask = $this->shop->getSelfRequiredFields();
        }else{
            return '';
        }
        if($requiredFieldMask == 0){
            return $this->renderChange();
        }

        $deliveryType = $this->getOrder()->type;

        $order = $this->order;
        $order->declaredPrice = $this->shop->getDeclaredPrice($order);

        $fieldValue = $order->firstName;
        if(!$fieldValue)
            $order->firstName = $this->shop->getClientFirstName();


        $fieldValue = $order->secondName;
        if(!$fieldValue)
            $order->secondName = $this->shop->getClientLastName();

        $fieldValue = $order->getToPhone();
        if(!$fieldValue)
            $order->setToPhone($this->shop->getClientPhone());

        $fieldValue = $order->getToStreet();
        if(!$fieldValue){
            $address = $this->shop->getClientAddress();
            if(!is_array($address))
                $address = array($address);
            if(isset($address[0]))
                $order->setToStreet($address[0]);
            if(isset($address[1]))
                $order->setToHouse($address[1]);
            if(isset($address[2]))
                $order->setToHousing($address[2]);
            if(isset($address[3]))
                $order->setToFlat($address[3]);
        }


        ob_start();
        include(__DIR__.'/../../templates/contactForm.php');
        $content = ob_get_contents();
        ob_end_clean();

        return json_encode(array('html'=>$content, 'js'=>'contactForm', 'orderId' => $this->order->localId, 'type'=>DDeliverySDK::TYPE_COURIER));
    }

    /**
     * Возвращает дополнительную информацию по компаниям доставки
     * @return array
     */
    static public function getCompanySubInfo()
    {
        // pack забита для тех у кого нет иконки
        return array(
            1 => array('name' => 'PickPoint', 'ico' => 'pickpoint'),
            3 => array('name' => 'Logibox', 'ico' => 'logibox'),
            4 => array('name' => 'Boxberry', 'ico' => 'boxberry'),
            6 => array('name' => 'СДЭК забор', 'ico' => 'cdek'),
            7 => array('name' => 'QIWI Post', 'ico' => 'qiwi'),
            11 => array('name' => 'Hermes', 'ico' => 'hermes'),
            13 => array('name' => 'КТС', 'ico' => 'pack'),
            14 => array('name' => 'Maxima Express', 'ico' => 'pack'),
            16 => array('name' => 'IMLogistics Пушкинская', 'ico' => 'imlogistics'),
            17 => array('name' => 'IMLogistics', 'ico' => 'imlogistics'),
            18 => array('name' => 'Сам Заберу', 'ico' => 'pack'),
            20 => array('name' => 'DPD Parcel', 'ico' => 'dpd'),
            21 => array('name' => 'Boxberry Express', 'ico' => 'boxberry'),
            22 => array('name' => 'IMLogistics Экспресс', 'ico' => 'imlogistics'),
            23 => array('name' => 'DPD Consumer', 'ico' => 'dpd'),
            24 => array('name' => 'Сити Курьер', 'ico' => 'pack'),
            25 => array('name' => 'СДЭК Посылка Самовывоз', 'ico' => 'cdek'),
            26 => array('name' => 'СДЭК Посылка до двери', 'ico' => 'cdek'),
            27 => array('name' => 'DPD ECONOMY', 'ico' => 'dpd'),
            28 => array('name' => 'DPD Express', 'ico' => 'dpd'),
            29 => array('name' => 'DPD Classic', 'ico' => 'dpd'),
            30 => array('name' => 'EMS', 'ico' => 'ems'),
            31 => array('name' => 'Grastin', 'ico' => 'pack'),
        );
    }

    /**
     *
     * �?нициализирует свойства объекта DDeliveryOrder из stdClass полученный из
     * запроса БД SQLite
     *
     * @param DDeliveryOrder $currentOrder
     * @param \stdClass $item
     */
    public function _initOrderInfo($currentOrder, $item)
    {
        $currentOrder->type = $item->type;
        $currentOrder->paymentVariant = $item->payment_variant;
        $currentOrder->localId = $item->id;
        $currentOrder->confirmed = $item->confirmed;
        $currentOrder->amount = $item->amount;
        $currentOrder->city = $item->to_city;
        $currentOrder->localStatus = $item->local_status;
        $currentOrder->ddStatus = $item->dd_status;
        $currentOrder->shopRefnum = $item->shop_refnum;
        $currentOrder->ddeliveryID = $item->ddeliveryorder_id;
        if ($item->point != null) {
            $currentOrder->setPoint(unserialize($item->point));
        }
        $currentOrder->firstName = $item->first_name;
        $currentOrder->secondName = $item->second_name;
        $currentOrder->shopRefnum = $item->shop_refnum;
        $currentOrder->declared_price = $item->declared_price;
        $currentOrder->paymentPrice = $item->payment_price;
        $currentOrder->toName = $item->to_name;
        $currentOrder->toPhone = $item->to_phone;
        $currentOrder->goodsDescription = $item->goods_description;
        $currentOrder->toStreet = $item->to_street;
        $currentOrder->toHouse = $item->to_house;
        $currentOrder->toFlat = $item->to_flat;
        $currentOrder->toEmail = $item->to_email;
        $currentOrder->comment = $item->comment;
        $currentOrder->cityName = $item->city_name;
    }

    /**
     * Удалить все заказы
     * @return bool
     */
    public function deleteAllOrders()
    {
        $orderDB = new DataBase\Order($this->pdo, $this->pdoTablePrefix);
        return $orderDB->cleanOrders();
    }

    /**
     * Получить описание статуса на DDelivery
     *
     * @param $ddStatus код статуса на DDeivery
     *
     * @return string
     */
    public function getDDStatusDescription( $ddStatus )
    {
       $statusProvider = new DDStatusProvider();
       return $statusProvider->getOrderDescription( $ddStatus );
    }

    /**
     * @param DShopAdapter $dShopAdapter
     * @throws DDeliveryException
     */
    public function _initDb(DShopAdapter $dShopAdapter)
    {
        $dbConfig = $dShopAdapter->getDbConfig();
        if (isset($dbConfig['pdo']) && $dbConfig['pdo'] instanceof \PDO) {
            $this->pdo = $dbConfig['pdo'];
        } elseif ($dbConfig['type'] == DShopAdapter::DB_SQLITE) {
            if (!$dbConfig['dbPath'])
                throw new DDeliveryException('SQLite db is empty');

            $dbDir = dirname($dbConfig['dbPath']);
            if ((!is_writable($dbDir)) || (!is_writable($dbConfig['dbPath'])) || (!is_dir($dbDir))) {
                throw new DDeliveryException('SQLite database does not exist or is not writable');
            }

            $this->pdo = new \PDO('sqlite:' . $dbConfig['dbPath']);
            $this->pdo->exec('PRAGMA journal_mode=WAL;');
        } elseif ($dbConfig['type'] == DShopAdapter::DB_MYSQL) {
            $this->pdo = new \PDO($dbConfig['dsn'], $dbConfig['user'], $dbConfig['pass']);
            $this->pdo->exec('SET NAMES utf8');
        } else {
            throw new DDeliveryException('Not support database type');
        }
        $this->pdoTablePrefix = isset($dbConfig['prefix']) ? $dbConfig['prefix'] : '';
    }


}