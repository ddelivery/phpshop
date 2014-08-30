<?php
/**
 *
 * @package    DDelivery.Adapter
 *
 * @author  mrozk 
 */

namespace DDelivery\Adapter;

use DDelivery\Order\DDeliveryOrder;
use DDelivery\Order\DDeliveryProduct;
use DDelivery\Order\DDStatusProvider;

use DDelivery\Sdk\DDeliverySDK;

/**
 * Class DShopAdapter
 * @package DDelivery\Adapter
 */
abstract class DShopAdapter
{
    /**
     * Тип кеширования централизованый(забираются все точки с сервера)
     */
    const CACHING_TYPE_CENTRAL = 'central';

    /**
     * Тип кеширования локальный(забираются точки с сервера с фильтром по компаниям)
     */
    const CACHING_TYPE_INDIVIDUAL = 'individual';

    const SDK_VERSION = '2.0';
    /**
     * �?мя редактируется
     */
    const FIELD_EDIT_FIRST_NAME = 1;
    /**
     * �?мя обязательное
     */
    const FIELD_REQUIRED_FIRST_NAME = 2;
    /**
     * Фамилия редактируется
     */
    const FIELD_EDIT_SECOND_NAME = 4;
    /**
     * �?спользуй FIELD_EDIT_SECOND_NAME
     * @deprecated
     */
    const FIELD_EDIT_LAST_NAME = 4;
    /**
     * Фамилия обязательное
     */
    const FIELD_REQUIRED_SECOND_NAME = 8;
    /**
     * Телефон редактируется
     */
    const FIELD_EDIT_PHONE = 16;
    /**
     * Телефон обязательное
     */
    const FIELD_REQUIRED_PHONE = 32;
    /**
     * Адресс редактируется
     */
    const FIELD_EDIT_ADDRESS = 64;
    /**
     * Адресс обязательное
     */
    const FIELD_REQUIRED_ADDRESS = 128;
    /**
     * Адресс, дом редактируется
     */
    const FIELD_EDIT_ADDRESS_HOUSE = 256;
    /**
     * Адресс, дом обязательное
     */
    const FIELD_REQUIRED_ADDRESS_HOUSE = 512;
    /**
     * Адресс, корпус редактируется
     */
    const FIELD_EDIT_ADDRESS_HOUSING = 1024;
    /**
     * Адресс, корпус обязательное
     */
    const FIELD_REQUIRED_ADDRESS_HOUSING = 2048;
    /**
     * Адресс, квартира редактируется
     */
    const FIELD_EDIT_ADDRESS_FLAT = 4096;
    /**
     * Адресс, квартира обязательное
     */
    const FIELD_REQUIRED_ADDRESS_FLAT = 8192;

    /**
     * Кеш объекта
     * @var DDeliveryProduct[]
     */
    private $productsFromCart = null;

    const DB_MYSQL = 1;
    const DB_SQLITE = 2;

    /**
     * Сопоставление cтатуса заказов на стороне cms
     * 
     * Значение по умолчанию должно быть переопределено на локальные значения статусов.
     * В массиве должно 12 значений для сопоставления, они могут повторятся по несколько раз
     * в подряд и по порядку должны соответствовать значениям в $ddeliveryOrderStatus
     * Применяется для связывания статусов заказов на стороне ddelivery и на стороне клиента
     * 
     * @var array
     */

    protected  $cmsOrderStatus = array( DDStatusProvider::ORDER_IN_PROGRESS => 'В обработке',
                                        DDStatusProvider::ORDER_CONFIRMED => 'Подтверждена',
                                        DDStatusProvider::ORDER_IN_STOCK => 'На складе �?М',
                                        DDStatusProvider::ORDER_IN_WAY => 'Заказ в пути',
                                        DDStatusProvider::ORDER_DELIVERED => 'Заказ доставлен',
                                        DDStatusProvider::ORDER_RECEIVED => 'Заказ получен',
                                        DDStatusProvider::ORDER_RETURN => 'Возврат заказа',
                                        DDStatusProvider::ORDER_CUSTOMER_RETURNED => 'Клиент вернул заказ',
                                        DDStatusProvider::ORDER_PARTIAL_REFUND => 'Частичный возврат заказа',
                                        DDStatusProvider::ORDER_RETURNED_MI => 'Возвращен в �?М',
                                        DDStatusProvider::ORDER_WAITING => 'Ожидание',
                                        DDStatusProvider::ORDER_CANCEL => 'Отмена' );

    /**
     * Настройки базы данных
     * @return array
     */
    public function getDbConfig()
    {
        return array(
            'type' => self::DB_SQLITE,
            'dbPath' => $this->getPathByDB(),
            'prefix' => '',
        );
        return array(
            'pdo' => new \PDO('mysql:host=localhost;dbname=ddelivery', 'root', '0', array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")),
            'prefix' => '',
        );
        return array(
            'type' => self::DB_MYSQL,
            'dsn' => 'mysql:host=localhost;dbname=ddelivery',
            'user' => 'root',
            'pass' => '0',
            'prefix' => '',
        );
    }

    /**
     *
     * Тип кеширования, для централизированого подхода и для индивидуального решения
     * разные
     *
     * @return string
     */
    public function getCachingFormat(){
        // return DShopAdapter::CACHING_TYPE_CENTRAL;
        return DShopAdapter::CACHING_TYPE_INDIVIDUAL;
    }

    /**
     * Получить название шаблона для сдк ( разные цветовые схемы )
     *
     * @return string
     */
    public function getTemplate(){
        return 'default';
    }
    /**
     * Возвращаем сервер для логгирования ошибок
     */
    public function getLogginServer(){
        return 'http://service.ddelivery.ru/loggin.php';
    }

    /**
     *
     * Перед возвратом точек самовывоза фильтровать их по определенным правилам
     *
     * @param $companyArray
     * @param DDeliveryOrder $order
     * @return mixed
     */
    public function finalFilterSelfCompanies( $companyArray, DDeliveryOrder $order ){
        return $companyArray;
    }

    /**
     *
     *  Перед возвратом компаний курьерок фильтровать их по определенным правилам
     *
     * @param $companyArray
     * @param DDeliveryOrder $order
     * @return mixed
     */
    public function finalFilterCourierCompanies( $companyArray, DDeliveryOrder $order ){
        return $companyArray;
    }

    /**
     * Получить доступные способы оплаты для Самовывоза ( можно анализировать содержимое order )
     * @param $order DDeliveryOrder
     * @return array
     */
    public function getSelfPaymentVariants( $order ){
        return array();
    }

    /**
     * Получить доступные способы оплаты для курьера ( можно анализировать содержимое order )
     * @param $order DDeliveryOrder
     * @return array
     */
    public function getCourierPaymentVariants($order){
        return array();
    }
    /**
     * Возвращает путь до файла базы данных sqlite, положите его в место не доступное по прямой ссылке
     * @return string
     */
    public function getPathByDB()
    {
        return '';
    }

    /**
     * Возвращает true если статус $cmsStatus равен
     * статусу в настройках
     *
     * @param $cmsStatus mixed
     * @return bool
     */
    public function isStatusToSendOrder( $cmsStatus ){
        return false;
    }

    /**
     * Возвращает время истечения кэша в минутах
     * @return int
     */
    public function getCacheExpired()
    {
        return 720; // 60*24
    }

    /**
     * Включить кэш
     * @return bool
     */
    public function isCacheEnabled()
    {
        return true;
    }

    /**
     * Возвращает товары находящиеся в корзине пользователя, будет вызван один раз, затем закеширован
     * @return DDeliveryProduct[]
     */
    protected abstract function _getProductsFromCart();
    
    
    /**
     * Меняет статус внутреннего заказа cms
     * 
     * @param $cmsOrderID - id заказа
     * @param $status - статус заказа для обновления 
     *  
     * @return bool
     */
    public abstract function setCmsOrderStatus( $cmsOrderID, $status );

    /**
     * Метод взамодейсвует с  настройками. Возвращает массив с ID заказов
     * со стороны CMS у которых статус заказа такой как указан в настройках
     *
     * @return array
     */
    public function getOrderIDsByStatus()
    {
        return array();
    }

    /**
     *
     * �?спользуется при отправке заявки на сервер DD для указания стартового статуса
     *
     * Если true то заявка в сервисе DDelivery будет выставлена в статус "Подтверждена",
     * если false то то заявка в сервисе DDelivery будет выставлена в статус "В обработке"
     *
     * @param mixed $localStatus
     *
     * @return bool
     */
    public function isConfirmedStatus( $localStatus ){
        return true;
    }

    /**
     * Получить статус cms по статусу DDelivery
     *
     * @param string $ddStatus
     * @return mixed;
     *
     */
    public function getLocalStatusByDD( $ddStatus  ){
        if( !empty($this->cmsOrderStatus[$ddStatus]) ){
            return $this->cmsOrderStatus[$ddStatus];
        }
        return 0;
    }


    /**
     *
     * Если корзина пуста, добавляем демо-данные
     *
     * @return array
     */
    public function getDemoCardData(){
        $products = array();

        $products[] = new DDeliveryProduct(
            1,	//	int $id id товара в системе и-нет магазина
            20,	//	float $width длинна
            13,	//	float $height высота
            25,	//	float $length ширина
            0.5,	//	float $weight вес кг
            1000,	//	float $price стоимостьв рублях
            1,	//	int $quantity количество товара
            'Веселый клоун'	//	string $name Название вещи
        );
        $products[] = new DDeliveryProduct(2, 10, 13, 15, 0.3, 1500, 2, 'Грустный клоун');
        return $products;
    }
    /**
     * Возвращает товары находящиеся в корзине пользователя, реализует кеширование getProductsFromCart
     * @return DDeliveryProduct[]
     */
    public final function getProductsFromCart()
    {
        if(!$this->productsFromCart) {
            $this->productsFromCart = $this->_getProductsFromCart();
            if( count( $this->productsFromCart ) < 1 ){
                $this->productsFromCart = $this->getDemoCardData();
            }
        }
        return $this->productsFromCart;
    }
    
    /**
     * Возвращает API ключ, вы можете получить его для Вашего приложения в личном кабинете
     * @return string
     */
    public abstract function getApiKey();

    /**
     * Должен вернуть url до каталога с статикой
     * @return string
     */
    public abstract function getStaticPath();

    /**
     * URL до скрипта где вызывается DDelivery::render
     * @return string
     */
    public abstract function getPhpScriptURL();

    /**
     * Верните true если нужно использовать тестовый(stage) сервер
     * @return bool
     */
    public function isTestMode()
    {
        return false;
    }

    /**
     * Если вы знаете имя покупателя, сделайте чтобы оно вернулось в этом методе
     * @return string|null
     */
    public function getClientFirstName() {
        return null;
    }

    /**
     * Если вы знаете фамилию покупателя, сделайте чтобы оно вернулось в этом методе
     * @return string|null
     */
    public function getClientLastName() {
        return null;
    }

    /**
     * Если вы знаете телефон покупателя, сделайте чтобы оно вернулось в этом методе. 11 символов, например 79211234567
     * @return string|null
     */
    public function getClientPhone() {
        return null;
    }

    /**
     * Верни массив Адрес, Дом, Корпус, Квартира. Если не можешь можно вернуть все в одном поле и настроить через get*RequiredFields
     * @return string[]
     */
    public function getClientAddress() {
        return array();
    }


    /**
     * Вызывается перед отображением цены точки самовывоза, можно что-то изменить
     *
     * @param DDeliveryPointSelf $ddeliveryPointSelf
     * @param DDeliveryOrder $order
     */
    public function preDisplaySelfPoint( DDeliveryPointSelf $ddeliveryPointSelf, DDeliveryOrder $order) {

    }

    /**
     * Срабатывает когда выбрана точка доставки
     *
     * @param DDeliveryAbstractPoint $point

    public function onChangePoint( DDeliveryAbstractPoint $point) {}
     */


    /**
     * Если есть необходимость искать точки на сервере ddelivery
     * 
     * @param \DDelivery\Order\DDeliveryOrder $order
     * 
     * @return boolean
     */
    public function preGoToFindPoints( $order ){
        return true;        	
    }
    
    /**
     * 
     * Есть ли необходимость отправлять заказ на сервер ddelivery
     * 
     * @param \DDelivery\Order\DDeliveryOrder $order
     * 
     * @return float
     */
    public function sendOrderToDDeliveryServer( $order ){
        return true;    	
    }

    /**
     *
     * Сумма к оплате на точке или курьеру
     *
     * Возвращает параметр payment_price для создания заказа
     * Параметр payment_price необходим для добавления заявки на заказ
     * По этому параметру в доках интегратору будет написан раздел
     *
     * @param \DDelivery\Order\DDeliveryOrder $order
     * @param float $orderPrice
     *
     * @return float
     */
    public function getPaymentPriceCourier( $order, $orderPrice ) {
    	return 0;
    }

    /**
     * Сумма к оплате на точке или курьеру
     *
     * Возвращает параметр payment_price для создания заказа
     * Параметр payment_price необходим для добавления заявки на заказ
     * По этому параметру в доках интегратору будет написан раздел
     *
     * @param \DDelivery\Order\DDeliveryOrder $order
     * @param float $orderPrice
     *
     * @return float
     */
    public function getPaymentPriceSelf( $order, $orderPrice ) {
    	return 0;
    }
    /**
     *
     * Получить список продуктов по id
     * @param int[]
     * 
     * @return array DDeliveryProduct[]
     */
    public function getProductsByID( $productIDs )
    {
        return array();
    }

    /**
     * Верните id города в системе DDelivery
     * @return int
     */
    public function getClientCityId() {
        if(isset($_COOKIE['ddCityId'])){
            return $_COOKIE['ddCityId'];
        }
        return 0;
    }


    /**
     * Возвращает стоимоть заказа
     * @return float
     */
    public function getAmount()
    {
        $amount = 0.;
        foreach($this->getProductsFromCart() as $product) {
            $amount .= $product->getPrice() * $product->getQuantity();
        }
        return $amount;
    }
    
    
    /**
     * Возвращает оценочную цену для товаров в послыке
     * 
     * @param \DDelivery\Order\DDeliveryOrder $order
     * 
     * @return float
     */
    abstract public function getDeclaredPrice( $order );

    /**
     * Возвращает поддерживаемые магазином способы доставки
     * @return array
     */
    public function getSupportedType()
    {
        return array(DDeliverySDK::TYPE_COURIER, DDeliverySDK::TYPE_SELF);
    }

    /**
     * Возвращает бинарную маску обязательных полей для курьера
     * Если редактирование не включено, но есть обязательность то поле появится
     * Если редактируемых полей не будет то пропустим шаг
     * @return int
     */
    public function getCourierRequiredFields()
    {
        // ВВести все обязательно, кроме корпуса
        return self::FIELD_EDIT_FIRST_NAME | self::FIELD_REQUIRED_FIRST_NAME | self::FIELD_EDIT_SECOND_NAME | self::FIELD_REQUIRED_SECOND_NAME
            | self::FIELD_EDIT_PHONE | self::FIELD_REQUIRED_PHONE
            | self::FIELD_EDIT_ADDRESS | self::FIELD_REQUIRED_ADDRESS
            | self::FIELD_EDIT_ADDRESS_HOUSE | self::FIELD_REQUIRED_ADDRESS_HOUSE
            | self::FIELD_EDIT_ADDRESS_HOUSING
            | self::FIELD_EDIT_ADDRESS_FLAT | self::FIELD_REQUIRED_ADDRESS_FLAT;
    }

    /**
     * Возвращает бинарную маску обязательных полей для пунктов самовывоза
     * Если редактирование не включено, но есть обязательность то поле появится
     * Если редактируемых полей не будет то пропустим шаг
     * @return int
     */
    public function getSelfRequiredFields()
    {
        // �?мя, фамилия, мобилка
        return self::FIELD_EDIT_FIRST_NAME | self::FIELD_REQUIRED_FIRST_NAME
            | self::FIELD_EDIT_SECOND_NAME | self::FIELD_REQUIRED_SECOND_NAME
            | self::FIELD_EDIT_PHONE | self::FIELD_REQUIRED_PHONE;
    }


    /**
     * Метод будет вызван когда пользователь закончит выбор способа доставки
     *
     * @param DDeliveryOrder $order
     * @return bool
     */
    abstract public function onFinishChange( DDeliveryOrder $order);

    /**
     * Обработка цены перед отдачей в методе getClientPrice
     *
     * @param DDeliveryOrder $order
     * @param $price
     * @return mixed
     */
    public function  processClientPrice( DDeliveryOrder $order, $price ){
        return $price;
    }

    /**
     * Возможность что - нибудь добавить к информации
     * при окончании оформления заказа
     *
     * @param $order DDeliveryOrder
     * @param $resultArray
     */
    public function onFinishResultReturn( $order, $resultArray ){
        return $resultArray;
    }
}