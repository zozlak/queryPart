# QueryPart

A simple container for passing an SQL query along with its parameters (and optionally a list of columns).

## Installation

`composer require zozlak/query-part`

## Usage

```php
$qp = new zozlak\queryPart\QueryPart("SELECT foo FROM bar WHERE baz = ?", ['bazValue'], ['foo']);
print_r($qp);
echo $qp . "\n";
```

