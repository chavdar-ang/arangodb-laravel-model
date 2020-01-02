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
     * The table collection with the model.
     *
     * @var string
     */
    protected $collection;

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
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return ArangoDBClient\Document|null
     */
    public function find($id, $attributes = [''])
    {
        // create a new document handler
        $handler = new ArangoDocumentHandler($this->connection);

        return $handler->get($this->collection, $id);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        // create a new document handler
        $handler = new ArangoDocumentHandler($this->connection);

        // send the document to the server
        $id = $handler->save($this->collection, $this->attributes);

        return $handler->get($this->collection, $id);
    }

    /**
     * AQL query method
     *
     * @return \ArangoDBClient\Statement
     */
    public function query(string $query)
    {
        // create a statement to insert 1000 test users
        $statement = new ArangoStatement($this->connection, ['query' => $query]);

        // execute the statement
        $cursor = $statement->execute();

        return $cursor->getAll();
    }

    /**
     * Execute query
     *
     * @return \ArangoDBClient\Statement
     */
    public function get()
    {
        
        $this->statement = 'FOR i IN ' . $this->collection . ' RETURN i';

        $cursor = $this->statement->execute();

        return $cursor->getAll();
    }

    /**
     * Return all documents
     *
     * @return \ArangoDBClient\Statement
     */
    public function all()
    {
        $statement = 'FOR i IN ' . $this->collection . ' RETURN i';

        return $this->query($statement);
    }

    /**
     * Crate new document and save it to the database
     *
     * @param  array  $data
     * @return \ArangoDBClient\Statement
     */
    public function create(array $data)
    {
        $statement = 'INSERT ' . json_encode($data) . ' IN ' . $this->collection;
        $query = $this->query($statement);
        dd($query);
    }
}
