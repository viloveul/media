<?php

require __DIR__ . '/vendor/autoload.php';

$serverRequest = Viloveul\Http\Server\RequestFactory::fromGlobals();
// init collection object
$config = new Viloveul\Config\Configuration(['target' => __DIR__ . '/uploads']);

$uploader = new Viloveul\Media\Uploader($serverRequest, $config);

// upload all uploaded files
$uploader->upload('*', function ($results, $errors) {
    dd($results);
});

// upload specific index of $_FILES
$uploader->upload('your_input_field', function ($results, $errors) {
    dd($results);
});
