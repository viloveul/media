
# Installation

make sure your php version > 7.0

```bash
composer require viloveul/media
```

## HOW

```php
require __DIR__ . '/vendor/autoload.php';

$serverRequest = Viloveul\Http\Server\RequestFactory::fromGlobals();

$uploader = new Viloveul\Media\Uploader($serverRequest, [
	'target' => '/path/uploads'
]);

// upload all uploaded files
$uploader->upload('*', function ($results, $errors) {
	dd($results);
});

// upload specific index of $_FILES
$uploader->upload('your_input_field', function ($results, $errors) {
	dd($results);
});
```
