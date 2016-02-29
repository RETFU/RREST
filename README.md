# RREST

**RREST** build good REST API in PHP.

[![Build Status](https://api.travis-ci.org/RETFU/RREST.svg?branch=master)](https://travis-ci.org/RETFU/RREST)

> Important: **RREST** is in active development. The API is not frozen and BC break can happen at any time.

**RREST** do the glue between an [API specification language](https://en.wikipedia.org/wiki/Overview_of_RESTful_API_Description_Languages) (APISpec) like RAML, Swagger... and a router/framework (Provider) in charge of routing the request like [Silex](http://silex.sensiolabs.org/), [Symfony](https://symfony.com/), [Laravel](https://laravel.com/)... to your business logic.


**RREST** take care of boring stuff to make a good REST API:
* query parameters validation
* `Accept` header validation
* `Content-Type` validation
* protocol validation
* request payload body validation with [JSON Schema](http://json-schema.org/) or [XMLSchema](https://www.w3.org/XML/Schema)
* response with the right HTTP Status code
* parameters & payload body hinted (only for JSON)
* pre-formated response object (header configured) and automatic serialization
* automatic binding with a Controller, by convention:
    * POST /item/ -> `Controllers\Item#postAction`
    * GET /item/{itemId}/ -> `Controllers\Item#getAction`
    * GET /item/{itemId}/comment -> `Controllers\Item\Comment#getAction`
    * PUT /item/{itemId}/comment/{commentId} -> `Controllers\Item\Comment#putAction`

## Use case

I need to add a new resource in my API:
* update the API specification with new resource and all parameters, headers... needed.
* create the Controller for the resource

That's all, your resource if ready to use:
* the route is ready
* the validations are ready
* the response is ready

What you need to do is your job: **making the business logic**.

## Installation

> **RREST** is in active development and not available in [packagist](https://packagist.org).

Manualy update your composer.json file:

```json
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/RETFU/RREST"
    }
],
"require": {
    "retfu/rrest": "dev-master"
}
```

```bash
$ composer update
```

<!-- The recommended way to install **RREST** is through [Composer](https://getcomposer.org/):

```bash
$ composer require retfu/rrest
``` -->

## Usage

Silex + RAML

```php
<?php

$ramlFile = 'api.raml';

$app = new Silex\Application();

//more application logic here if needed

//parse the RAML file
$apiDefinition = (new Raml\Parser())->parse($ramlFile, true);
//load a RAML APISPec by injecting the RAML Parser instance
$apiSpec = new APISpec\RAML($apiDefinition);
//load a Silex Provider by injecting the Silex app instance
$provider = new Provider\Silex($app);
//bind APISpec + Provider
$rrest = new RREST\RREST($apiSpec, $provider, 'Controllers');
//add only the current route if match the APISpec
//validate `Accept`, `Content-Type`, protocol
$rrest->addRoute();

//more application logic here if needed

$app->run();
//after app apply routing logic, validate parameters and body payload against the APISpec
//hint parameters & body payload (only for JSON)
```

## Support

##### Format input/output
* JSON
* XML

##### APISPec
* RAML
* More coming soon

##### Provider
* Silex
* More coming soon

## Contributing

Lauch unit test:
```bash
./vendor/bin/atoum
```

## Why RREST

All those projects make an **amazing work**:
* [Microrest](https://github.com/marmelab/microrest.php)
* [The API Platform framework](https://github.com/api-platform/api-platform)
* [PSX Framework](https://github.com/k42b3/psx)

But they are frameworks. And all the magics come with dependencies like ORM, frameworks, response format standard...

I want to write an API with a specification language like RAML, Swagger...  
I want to plug it to the router/framework of my choice.  
I want to manage all the business logic.  
I want to manage all the persistence layer.

This is why I have create **RREST** :)

## Author

Fabien Furet - http://fabienfuret.net

## License

**RREST** is licensed under the MIT License - see the LICENSE file for details
