<?php

namespace App;

// set up some aliases for less typing later
use ArangoDBClient\Collection as ArangoCollection;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\Document as ArangoDocument;
use ArangoDBClient\Exception as ArangoException;
use ArangoDBClient\Export as ArangoExport;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use ArangoDBClient\Statement as ArangoStatement;
use ArangoDBClient\UpdatePolicy as ArangoUpdatePolicy;

use Illuminate\Support\Str;

class ArangoModel
{
    /**
     * The connection.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * The model name from class name
     *
     * @var string|null
     */
    protected $model;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The table collection of the current model.
     *
     * @var string
     */
    protected $collection;

    /**
     * The AQL statement.
     *
     * @var string
     */
    protected $statement;

    // new document properties
    protected $document;
    protected $documentHandler;

    public function __construct()
    {
        // set up some basic connection options
        $connectionOptions = [
            // database name
            ArangoConnectionOptions::OPTION_DATABASE => '_system',
            // server endpoint to connect to
            ArangoConnectionOptions::OPTION_ENDPOINT => 'tcp://localhost:8529',
            // authorization type to use (currently supported: 'Basic')
            ArangoConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
            // user for basic authorization
            ArangoConnectionOptions::OPTION_AUTH_USER => 'root',
            // password for basic authorization
            ArangoConnectionOptions::OPTION_AUTH_PASSWD => '123456',
            // connection persistence on server. can use either 'Close' (one-time connections) or 'Keep-Alive' (re-used connections)
            ArangoConnectionOptions::OPTION_CONNECTION => 'Keep-Alive',
            // connect timeout in seconds
            ArangoConnectionOptions::OPTION_TIMEOUT => 3,
            // whether or not to reconnect when a keep-alive connection has timed out on server
            ArangoConnectionOptions::OPTION_RECONNECT => true,
            // optionally create new collections when inserting documents
            ArangoConnectionOptions::OPTION_CREATE => true,
            // optionally create new collections when inserting documents
            ArangoConnectionOptions::OPTION_UPDATE_POLICY => ArangoUpdatePolicy::LAST,
        ];

        // turn on exception logging (logs to whatever PHP is configured)
        ArangoException::enableLogging();

        $this->connection = new ArangoConnection($connectionOptions);

        $this->model = Str::snake(class_basename($this));

        $this->collection = Str::snake(Str::pluralStudly(class_basename($this)));

        // default AQL statement
        $this->statement = 'FOR i IN ' . $this->collection;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    // public function save(array $options = [])
    // {
    //     // create a new document handler
    //     $handler = new ArangoDocumentHandler($this->connection);

    //     // send the document to the server
    //     $id = $handler->save($this->collection, $this->attributes);

    //     return $handler->get($this->collection, $id);
    // }

    /**
     * AQL query method
     *
     * @return \ArangoDBClient\Statement
     */
    public function query(string $query = '')
    {
        (!empty($query)) ?? $this->statement .= $query;

        $this->statement .= ' RETURN i';
        $statement = new ArangoStatement($this->connection, ['query' => $this->statement]);

        // execute the statement
        $cursor = $statement->execute();

        return $cursor->getAll();
    }
    
    public function find(int $id)
    {
        $this->statement .= ' FILTER i._key > "'. $id .'"';
        $this->take(1);
        // Get the first element from the collection, not the collection itself
        return $this->get(){0};
    }

    public function take(int $limit)
    {
        $this->statement .= ' LIMIT 1';
        return $this;
    }
    
    public function first()
    {
        return $this->get(){0};
    }
    
    public function get()
    {
        return $this->query($this->statement);
    }
    
    public function all()
    {
        return $this->get();
    }

    public function filter($property, $operator = null, $value = null)
    {
        // if operator is not present
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '==';
        }
        $this->statement .= ' FILTER i.'.$property.' '.$operator.' "'.$value.'"';
        return $this;
    }

    public function create(array $data)
    {
        $statement = 'INSERT ' . json_encode($data) . ' IN ' . $this->collection;
        $query = $this->query($statement);
        dd($query);
    }

}
