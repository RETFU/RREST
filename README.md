# RREST

This is a pet work in progress project to easily create REST API in PHP.

## Why?

A lots of solution do the job, but with a lots of dependencies: an ORM, a Framework...

**RREST** will only do the glue between an APISpec like RAML, Swagger... and a Provider in charge of routing the request like Silex, Symfony, Laravel... to your business logic.

## WIP

**DONE**
* support APISpec RAML
* support Provider Silex
* automatic routing by convention:
    * /item/{itemId}/ -> Controllers\Item
    * /item/{itemId}/comment -> Controllers\Item\Comment.
* automatic parameters validation
* automatic parameters hinting

**TODO**
* automatic headers validation
* automatic protocols validation
* better example

## Quick & dirty usage

```php
<?php

// [...]

$app = new Silex\Application();

// [...]

$apiDefinition = (new Raml\Parser())->parse($ramlFile, true);
$apiSpec = new APISpec\RAML($apiDefinition);
$provider = new Provider\Silex($app);
$rrest = new RREST\RREST($apiSpec, $provider, 'Controllers');
$rrest->addRoute();

// [...]

```
