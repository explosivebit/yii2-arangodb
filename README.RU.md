Коннектор ArangoDb PHP для Yii 2
===========================

Это расширение [ArangoDB](http://www.arangodb.org/) обеспечивает интеграцию в фрэймворком Yii2.


Установка
------------

Это расширение требует [ArangoDB PHP Extension](https://github.com/triAGENS/ArangoDB-PHP)

Предпочтительный способ установки с помощью [composer](http://getcomposer.org/download/).

Или запустить команду

```
php composer.phar require --prefer-dist explosivebit/yii2-arangodb "*"
```

или добавить в файл "composer.json"

```
"explosivebit/yii2-arangodb": "*"
```


Использование
-------------

Чтобы использовать это расширение, просто добавьте следующий код в конфигурацию вашего приложения:

```php
return [
    //....
    'components' => [
        'arangodb' => [
            'class' => '\explosivebit\arangodb\Connection',
            'connectionOptions' => [
                triagens\ArangoDb\ConnectionOptions::OPTION_DATABASE => "mydatabase",
                triagens\ArangoDb\ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
                triagens\ArangoDb\ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
                //triagens\ArangoDb\ConnectionOptions::OPTION_AUTH_USER   => '',
                //triagens\ArangoDb\ConnectionOptions::OPTION_AUTH_PASSWD => '',
            ],
        ],
    ],
];
```

С помощью экземпляра подключения вы можете обращаться к базам данных, коллекциям и документам.

Для выполнения запросов «search» вы должны использовать следующий класс `\explosivebit\arangodb\Query`:

```php
use explosivebit\arangodb\Query;

$query = new Query;
// compose the query
$query->select(['name', 'status'])
    ->from('customer')
    ->limit(10);
// execute the query
$rows = $query->all();
```


Использование ActiveRecord ArangoDB
------------------------------

Этот клас обеспечивает аналогичное решение yii2 ActiveRecord, `\yii\db\ActiveRecord`.
Чтобы объявить класс ActiveRecord, вам необходимо расширить `\explosivebit\arangodb\ActiveRecord` и
реализовать методы `collectionName` и `attributes`:

```php
use explosivebit\arangodb\ActiveRecord;

class Customer extends ActiveRecord
{
    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'customer';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return ['_key', 'name', 'email', 'address', 'status'];
    }
}
```

Примечание: имя первичного ключа ('_key') любой коллекции должно всегда присутствовать и настроено как атрибут.

Вы можете использовать `\yii\data\ActiveDataProvider` c `\explosivebit\arangodb\Query` а также `\explosivebit\arangodb\ActiveQuery`:

```php
use yii\data\ActiveDataProvider;
use explosivebit\arangodb\Query;

$query = new Query;
$query->from('customer')->where(['status' => 2]);
$provider = new ActiveDataProvider([
    'query' => $query,
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```

```php
use yii\data\ActiveDataProvider;
use app\models\Customer;

$provider = new ActiveDataProvider([
    'query' => Customer::find(),
    'pagination' => [
        'pageSize' => 10,
    ]
]);
$models = $provider->getModels();
```


Использование миграций
----------------

ArangoDB миграции осуществляются через `explosivebit\arangodb\console\controllers\MigrateController`, который является аналогом стандартных миграций yii2
`\yii\console\controllers\MigrateController`.

Чтобы включить миграции в ваше приложение , вы должны настроить конфигурацию консольного приложения:

```php
return [
    // ...
    'controllerMap' => [
        'arangodb-migrate' => 'explosivebit\arangodb\console\controllers\MigrateController'
    ],
];
```

Ниже приведены примеры использования некоторых команд

```
# создание новой миграции user 'create_user_collection'
yii arangodb-migrate/create create_user_collection

# применить все новые миграции
yii arangodb-migrate

# применить 1у миграцию
yii arangodb-migrate/up 1

# откатить все миграции
yii arangodb-migrate/down

# откатить 1у миграцию
yii arangodb-migrate/down 1
```

После создания можно настроить миграции:

При запуске миграции как в примера ниже создается обычная `document` коллекция с именем миграции, для создания `edge` коллекции необходимо прописать дополнительный параметр `Type => 3`.
Пример такого запроса: `$this->createCollection('serices',['Type' => 3]);`. Если нужно создать  `document` коллекцию то удалите параметр `'Type' => 3` или вместо цифры 3 встаьте 2.

```
class m170413_210957_create_services_collection extends \explosivebit\arangodb\Migration
{
    public function up()
    {
        # При запуске миграции создается коллекция "services" с типом edge
        $this->createCollection('serices',['Type' => 3]);
    }

    public function down()
    {
        # При запуске отката миграции удаляется коллекция "services"
        $this->dropCollection('services');
    }
}
```

Использование Debug Panel
-----------------

Добавьте панель ArangoDb в вашу конфигурацию `yii\debug\Module`

```php
return [
    'bootstrap' => ['debug'],
    'modules' => [
        'debug' => 'yii\debug\Module',
        'panels' => [
            'arango' => [
                'class' => 'explosivebit\arangodb\panels\arangodb\ArangoDbPanel',
            ],
        ],
        ...
    ],
    ...
];
```
