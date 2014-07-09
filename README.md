# SimpleCsv Reader
SimpleCsv is a simple and flexible library for reading and manipulating CSV files. 


## Usage

### Read CSV FROM FILE

```php
$csv = \Reader::create_from_file('./dat.csv');
```

### Read CSV FROM String

```php
$str = 'header 1, header 2
data 1, data 2';
$csv = \Reader::create_from_string($str);
```

### CSV TO ARRAY

```php
// associative array using first line as headers
$csv = \Reader::create_from_file('./dat.csv')
				->to_assoc();

// indexed array w/ no headers
$csv = \Reader::create_from_file('./dat.csv')
				->to_array();
```

### DETECT DELIMITER

```php
// automatically detect and set the file delimiter type
$csv = \Reader::create_from_file('./dat.csv')
				->detect_delimiter(array('|', '%', '$'))
				->to_assoc();
```



## INSTALLATION

Install the `SimpleCsv` package with Composer.

```json
{
    "require": {
        "AlexBrandes/SimpleCsv": "*"
    }
}
```

Read more about [Composer](http://getcomposer.org/doc/01-basic-usage.md)

## SYSTEM REQUIREMENTS

**PHP >= 5.3.0**


## TODO

- Write to Csv
- Tests



### CREDITS

- Written and maintained by [Alex Brandes](https://github.com/alexbrandes)