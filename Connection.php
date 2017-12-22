<?php

namespace explosivebit\arangodb;

use ArangoDBClient\EdgeHandler;
use ArangoDBClient\Export;
use ArangoDBClient\GraphHandler;
use Yii;
use ArangoDBClient\CollectionHandler;
use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\Document;
use ArangoDBClient\DocumentHandler;
use ArangoDBClient\Statement;
use ArangoDBClient\UpdatePolicy;

use yii\base\BaseObject;

class Connection extends BaseObject
{
    private $connection = null;

    public $connectionOptions = [
        // server endpoint to connect to
        ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.1:8529',
        // authorization type to use (currently supported: 'Basic')
        ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
        // user for basic authorization
        ConnectionOptions::OPTION_AUTH_USER => 'root',
        // password for basic authorization
        ConnectionOptions::OPTION_AUTH_PASSWD => '',
        // connection persistence on server. can use either 'Close'
        // (one-time connections) or 'Keep-Alive' (re-used connections)
        ConnectionOptions::OPTION_CONNECTION => 'Close',
        // connect timeout in seconds
        ConnectionOptions::OPTION_TIMEOUT => 3,
        // whether or not to reconnect when a keep-alive connection has timed out on server
        ConnectionOptions::OPTION_RECONNECT => true,
        // optionally create new collections when inserting documents
        ConnectionOptions::OPTION_CREATE => true,
        // optionally create new collections when inserting documents
        ConnectionOptions::OPTION_UPDATE_POLICY => UpdatePolicy::LAST,
    ];

    /** @var null|CollectionHandler $collectionHandler */
    private $collectionHandler = null;
    /** @var null|DocumentHandler $documentHandler */
    private $documentHandler = null;
    /** @var null|EdgeHandler $documentHandler */
    private $edgeHandler = null;
    /** @var null|EdgeHandler $graphHandler */
    private $graphHandler = null;

    public function init()
    {
        parent::init();

        $token = 'Opening ArangoDB connection: ' . $this->connectionOptions[ConnectionOptions::OPTION_ENDPOINT];
        try {
            Yii::info($token, 'explosivebit\arangodb\Connection::open');
            Yii::beginProfile($token, 'explosivebit\arangodb\Connection::open');
            $this->connection        = new \ArangoDBClient\Connection($this->connectionOptions);
            $this->collectionHandler = new CollectionHandler($this->connection);
            $this->documentHandler   = new DocumentHandler($this->connection);
            $this->edgeHandler       = new EdgeHandler($this->connection);
            $this->graphHandler      = new GraphHandler($this->connection);
            Yii::endProfile($token, 'explosivebit\arangodb\Connection::open');
        } catch (\Exception $ex) {
            Yii::endProfile($token, 'explosivebit\arangodb\Connection::open');
            throw new \Exception($ex->getMessage(), (int)$ex->getCode(), $ex);
        }
    }

    /**
     * @return EdgeHandler|null
     */
    public function getGraphHandler(): EdgeHandler
    {
        return $this->graphHandler;
    }

    /**
     * @return null|CollectionHandler
     */
    public function getCollectionHandler()
    {
        return $this->collectionHandler;
    }

    /**
     * @param $collectionId
     * @return \triagens\ArangoDb\Collection
     */
    public function getCollection($collectionId)
    {
        return $this->getCollectionHandler()->get($collectionId);
    }

    /**
     * @return null|DocumentHandler
     */
    public function getDocumentHandler()
    {
        return $this->documentHandler;
    }

    /**
     * @return null|EdgeHandler
     */
    public function getEdgeHandler()
    {
        return $this->edgeHandler;
    }

    /**
     * @param $collectionId
     * @param $documentId
     * @return Document
     */
    public function getDocument($collectionId, $documentId)
    {
        return $this->getDocumentHandler()->get($collectionId, $documentId);
    }

    /**
     * @param array $options
     * @return Statement
     */
    public function getStatement($options = [])
    {
        return new Statement($this->connection, $options);
    }

    /**
     * @param array $options
     * @return Export
     */
    public function getExport($options = []) {
        return new Export($this->connection, $options);
    }
}
