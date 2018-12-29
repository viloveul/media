# Installation
make sure your php version > 7.0
```bash
composer require viloveul/media
```
## HOW
```php
require __DIR__ . '/vendor/autoload.php';

// init collection object
$uploader = new Viloveul\Media\Uploader(['target' => '/path/uploads']);
// upload all uploaded files
$uploader->upload('*', function ($results) {
	var_dump($results);
});
// upload specific index of $_FILES
$uploader->upload('your_input_field', function ($results) {
	var_dump($results);
});
var_dump($uploader->errors());
// clear all
$uploader->clear();
```