<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql;

$loader = new Loader();

$loader->registerNamespaces(
    [
        'Models' => '../app/models/',
    ]
);

$loader->register();

$container = new FactoryDefault();

$container->set(
    'db',
    function () {
        return new Mysql(
            [
                'host'      => 'db',
                'username'  => 'root',
                'password'  => 'root',
                'dbname'    => 'robotics',
            ]
        );
    }
);

$app = new Micro($container);

// Retrieves all robots
$app->get(
    '/api/robots',
    function () use ($app) {
        //Operations
        $phql = 'SELECT * FROM Models\Robots ORDER BY name';

        $robots = $app->modelsManager->executeQuery($phql);

        $data = [];

        foreach ($robots as $robot) {
            $data[] = [
                'id'    =>  $robot->id,
                'name'  =>  $robot->name,
            ];
        }

        echo json_encode($data);
    }
);

// Searches for robots with $name in their name
$app->get(
    '/api/robots/search/{name}',
    function ($name) use($app) {
        //Operations
        $phql = 'SELECT * FROM Models\Robots WHERE name LIKE :name: ORDER BY name';

        $robots = $app->modelsManager->executeQuery(
            $phql,
            [
                'name' => '%' . $name . '%'
            ]
        );

        $data = [];

        foreach ($robots as $robot) {
            $data[] = [
                'id'   => $robot->id,
                'name' => $robot->name,
            ];
        }

        echo json_encode($data);
    }
);

// Retrieves robots based on primary key
$app->get(
    '/api/robots/{id:[0-9]+}',
    function ($id) use ($app) {
        //Operations
        $phql = 'SELECT * FROM Models\Robots WHERE id = :id:';

        $robot = $app->modelsManager->executeQuery(
            $phql,
            [
                'id' => $id,
            ]
        )->getFirst();



        // Create a response
        $response = new Response();

        if ($robot === false) {
            $response->setJsonContent(
                [
                    'status' => 'NOT-FOUND'
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    'status' => 'FOUND',
                    'data'   => [
                        'id'   => $robot->id,
                        'name' => $robot->name
                    ]
                ]
            );
        }

        return $response;
    }
);

// Adds a new robot
$app->post(
    '/api/robots',
    function () use ($app) {
        //Operations
        $robot = $app->request->getJsonRawBody();

        $phql = 'INSERT INTO Models\Robots (name, type, year) VALUES (:name:,
        :type:, :year:)';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'name'  => $robot->name,
                'type'  => $robot->type,
                'year'  => $robot->year,
            ]
        );

        //Create a response
        $response = new Response();

        //Check if the insertion was succesful
        if ($status->success() === true) {
            //Change the HTTP status
            $response->setStatusCode(201, 'Created');

            $robot->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status'    =>  'OK',
                    'data'      =>  $robot,
                ]
            );
        } else {
            //Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            //Send errors to client
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors [] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'    =>  'ERROR',
                    'messages'  =>  $errors,
                ]
            );
        }
        
        return $response;

    }
);

// Updates robots based on primary key
$app->put(
    '/api/robots/{id:[0-9]+}',
    function ($id) use ($app) {
        $robot = $app->request->getJsonRawBody();

        $phql = 'UPDATE Models\Robots SET name = :name:, type = :type:,
        year = :year: WHERE id = :id:';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'id'   => $id,
                'name' => $robot->name,
                'type' => $robot->type,
                'year' => $robot->year,
            ]
        );

        // Create a response
        $response = new Response();

        // Check if the insertion was successful
        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

// Deletes robots based on primary key
$app->delete(
    '/api/robots/{id:[0-9]+}',
    function ($id) use ($app) {
        //Operations
        $phql = 'DELETE FROM Models\Robots WHERE id = :id:';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'id'    => $id,
            ]
        );

        //Create a response
        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status'    =>  'OK',
                ]
            );
        } else {
            //Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors [] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'    =>  'ERROR',
                    'messages'  =>  $errors,
                ]
            );
        }

        return $response;
    }
);

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, 'Not Found')->sendHeaders();
    echo 'This is crazy, but this page was not found!';
});

try {
    $app->handle(
        $_SERVER['REQUEST_URI']
    );
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}