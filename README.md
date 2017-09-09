# Condense
Flat-file database in PHP.

![Logo](logo.png)

Based on the [Fllat](https://github.com/wylst/fllat) and [Prequel](https://github.com/wylst/prequel) libraries by [Wylst](https://github.com/wylst). Special mention for [Alfred Xing](https://github.com/alfredxing) who seems to be the main contributor behind both. With added support for:

* Encrypted databases using [php-encryption](https://github.com/defuse/php-encryption) by [Taylor Hornby](https://github.com/defuse)
* Composer via Packagist

## Installation
Install Condense via Composer like this:

```bash
composer require lambdacasserole/condense
```

Or alternatively, if you're using the PHAR (make sure the `php.exe` executable is in your PATH):

```
php composer.phar require lambdacasserole/condense
```

## Usage
To initialize a new database or load an existing one, do this.

```php
$db = new Database('employees');
```

This will, by default, create a file `db/employees.dat` or load that file, if it already exists. You can change the path at which the flat file database will be created thusly.

```php
$db = new Database('employees', '../storage');
```

The constructor also accepts a third parameter which allows you to specify a key to use to encrypt the database with.

```php
$db = new Database('secrets', '../private', 'my-secret-password');
```

When loading the database again, this same password must be used.

### Create
Use `insert` to add a record (row) to the database.

```php
$hire = ['first_name' => 'Ethan', 'last_name' => 'Benson', 'salary' => 20000];
$employees->insert($hire);
```

### Retrieve
Condense provides several methods for data retrieval.

#### One Value
Use the `get` method. Specify a field name, another field name, and a value. It will return the value of the first field where (in the same row), the value of the second field matches the given value.

```php
// Returns the salary of the first employee with the first name 'Ethan' (20000).
$employees->get('salary', 'first_name', 'Ethan');
```

#### Field Subset
Use the `select` method. Returns some (or all) fields in a table, specified by giving an array of desired field names.

```php
// Returns the whole database.
$employees->select([]);

// Returns the first name of each employee, for example: 
// [['Ethan'],['Thomas'],['James']]
$employees->select(['first_name']);
```

### Update
Condense provides a couple of methods for updating existing data in a database.

#### One Field
Use the `to` method to update one field for any row satisfying a condition.

```php
// Change every employee with a first name 'Ethan' to have the surname 'Smithers'.
$employees->to('last_name', 'Smithers', 'first_name', 'Ethan');
```

#### One Row
Use the `update` method to update a row by index.

```php
// Change the first row in the database completely.
$employees->update(0, ['first_name' => 'Alison', 'last_name' => 'Bradberry']);
```

### Delete
Use the `remove` method to delete a row in the database.

```php
// Remove the first row in the database.
$employees->remove(0);
```

## Caveats
This is a flat file database system. It removes the headache of setting up and configuring a database server, but introduces a few of its own:

* I/O will be _much_ slower due to many disk read/write actions
* Encrypting a database will hugely affect performance
* Bugs may arise due to concurrency issues
* Misconfigured web applications using this library may accidentally allow their databases to be downloaded over HTTP
