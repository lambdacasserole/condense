<?php

namespace Condense;

use Defuse\Crypto\Crypto;

/**
 * Represents a database file.
 *
 * @author      Alfred Xing <xing@lfred.info>
 * @author      Saul Johnson <saul.a.johnson@gmail.com>
 * @copyright   2013 Alfred Xing
 * @copyright   2017 Saul Johnson
 * @license     MIT
 * @version     1.0
*/
class Database
{
    /**
     * The name of the database file.
     *
     * @var string
     */
    private $name;

    /**
     * The path of the directory containing the database file.
     *
     * @var string
     */
    private $path;

    /**
     * The full path of the database file.
     *
     * @var string
     */
    private $file;

    /**
     * The key used to encrypt the database file.
     *
     * @var string
     */
    private $key;

    /**
     * Create a database
     *
     * @param string $name  name of the database
     * @param string $path  directory of the database file
     * @param string $key   the key used to encrypt the database, blank for no encryption
     */
    public function __construct($name, $path = 'db', $key = '')
    {
        $this->name = $name;
        $this->path = $path;
        $this->key = $key;
        $this->initialize($path . '/' . $this->name . '.dat');
    }

    /**
     * Initializes the database for work.
     *
     * @param string $file  the relative path of the database file
     * @return bool         true if the database was newly created, otherwise false
     */
    private function initialize($file)
    {
        if (file_exists($file)) {
            $this->file = realpath($file);
            return false;
        }
        file_put_contents($file, '');
        $this->file = realpath($file);
        return true;
    }

    /**
     * Loads all data from the database, decrypting it if needed.
     *
     * @return array    the data from the database
     */
    public function load()
    {
        if (!file_exists($this->file)) {
            return []; // File doesn't exist yet.
        }
        $data = file_get_contents($this->file);
        if (strlen($data) === 0) {
            return [];
        }
        if ($this->isEncrypted()) {
            $data = Crypto::decryptWithPassword($data, $this->key, true);
        }
        return json_decode($data, true);
    }

    /**
     * Deletes the database from disk.
     *
     * @return bool true if the database was successfully deleted, otherwise false
     */
    public function delete()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
            return true;
        }
        return false;
    }

    /**
     * Rewrites all data to the database.
     *
     * @param array $data   the data to rewrite
     * @return array        the updated data set
     */
    private function rewrite($data)
    {
        $text = json_encode($data);
        if ($this->isEncrypted() && strlen($text) > 0) {
            $text = Crypto::encryptWithPassword($text, $this->key, true);
        }
        file_put_contents($this->file, $text);
        return $data;
    }

    /**
     * Inserts a row into the database.
     *
     * @param array $data   the row to insert
     * @return array        the updated data set
     */
    public function insert($data)
    {
        $db = $this->load();
        $db[] = $data;
        return $this->rewrite($db);
    }

    /**
     * Removes a row from the database.
     *
     * @param integer $index    the index of the row to remove
     * @return array            the updated data set
     */
    public function remove($index)
    {
        $db = $this->load();
        array_splice($db, $index, 1);
        return $this->rewrite($db);
    }

    /**
     * Updates a row in the database.
     *
     * @param integer $index    the index of the row to update
     * @param array $data       the updated row
     * @return array            the updated data set
     */
    public function update($index, $data)
    {
        $db = $this->load();
        $db[$index] = array_merge($db[$index], $data);
        return $this->rewrite($db);
    }

    /**
     * Returns the index of a row where the given key matches value.
     *
     * @param string $key   the key to search for
     * @param string $val   the value to check for
     * @return integer      the index of the row found, or -1 if no row matched
     */
    public function index($key, $val)
    {
        $db = $this->load();
        foreach ($db as $index => $row) {
            if ($row[$key] === $val) {
                return $index;
                break;
            }
        }
        return -1;
    }

    /**
     * Changes the value of a field in rows satisfying a condition.
     *
     * @param string $field the name of the field to change
     * @param string $to    the value to change the field to
     * @param string $key   the name of the field to search for
     * @param string $val   the value of the field to search for
     * @return array        the updated data set
     */
    public function to($field, $to, $key, $val)
    {
        $db = $this->load();
        foreach ($db as $index => $row) {
            if ($row[$key] === $val) {
                $db[$index][$field] = $to;
            }
        }
        return $this->rewrite($db);
    }

    /**
     * Get the value of the specified field of the first row where a specified field contains a specified value.
     *
     * @param string $field the name of the field to return
     * @param string $key   the name of the field to search for
     * @param string $val   the value of the field to search for
     * @return string       the value of the specified field
     */
    public function get($field, $key, $val)
    {
        $db = $this->load();
        foreach ($db as $index => $row) {
            if ($row[$key] === $val && $row[$field]) {
                return $row[$field];
                break;
            }
        }
        return null;
    }

    /**
     * Gets an array of field names against values for every member of an array.
     *
     * @param array $fields the list of names of fields to get, empty for all
     * @param array $arr    the array to query
     * @return array        an array of field names against values for every member of the array
     */
    private static function arraySelect($fields = [], $arr) {
        $result = [];
        $values = [];
        if ($fields === []) {
            foreach ($arr as $index => $row) {
                foreach (array_keys($row) as $c) {
                    $values[$c] = $row[$c];
                }
                if ($values) {
                    $result[$index] = $values;
                }
                $values = [];
            }
        } else {
            foreach ($arr as $index => $row) {
                foreach ((array) $fields as $c) {
                    if ($row[$c]) {
                        $values[$c] = $row[$c];
                    }
                }
                if ($values) {
                    $result[$index] = $values;
                }
                $values = [];
            }
        }
        return $result;
    }

    /**
     * Gets an array of field names against values for every row in the data set.
     *
     * @param array $fields the list of names of fields to get, empty for all
     * @return array        an array of field names against values for every row in the data set
     */
    public function select($fields = [])
    {
        $db = $this->load();
        return self::arraySelect($fields, $db);
    }

    /**
     * Gets an array of field names against values for every row in the data set where a field has a given value.
     *
     * @param array $field  the names of the fields to return
     * @param string $key   the key of the field to check
     * @param string $val   the value to check the field for
     * @return array        the returned fields
     */
    public function where($field, $key, $val)
    {
        $db = $this->load();
        $result = [];
        $values = [];
        if ($field === []) {
            foreach ($db as $index => $row) {
                if ($row[$key] === $val) {
                    foreach (array_keys($row) as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = [];
                }
            }
        } else {
            foreach ($db as $index => $row) {
                if ($row[$key] === $val) {
                    foreach ((array) $field as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = [];
                }
            }
        }
        return $result;
    }

    /**
     * Gets an array of field names against values for every row in the data set where a field has one of a given set
     * of values.
     *
     * @param array $fields the fields to return (empty for all)
     * @param string $key   the key of the field to check
     * @param array $val    the values to check the field for
     * @return array        the returned fields
     */
    public function in($fields, $key, $val)
    {
        $db = $this->load();
        $result = [];
        $values = [];
        if ($fields === []) {
            foreach ($db as $index => $row) {
                if (in_array($row[$key], $val)) {
                    foreach (array_keys($row) as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = [];
                }
            }
        } else {
            foreach ($db as $index => $row) {
                if (in_array($row[$key], $val)) {
                    foreach ((array) $fields as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = [];
                }
            }
        }
        return $result;
    }

    /**
     * Matches keys and values based on a regular expression.
     *
     * @param array $fields the fields to return (empty for all)
     * @param string $key   the key of the field to check
     * @param string $regex the regular expression to match
     * @return array        the returned fields
     */
    public function like($fields, $key, $regex)
    {
        $db = $this->load();
        $result = [];
        $values = [];
        if ($fields === []) {
            foreach ($db as $index => $row) {
                if (preg_match($regex, $row[$key])) {
                    foreach (array_keys($row) as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = [];
                }
            }
        } else {
            foreach ($db as $index => $row) {
                if (preg_match($regex, $row[$key])) {
                    foreach ((array) $fields as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = [];
                }
            }
        }
        return $result;
    }

    /**
     * Merges two databases, removing duplicates.
     *
     * @param array $fields     the fields to merge
     * @param Database $second  the second database to merge
     * @return array            the merged array
     */
    public function union($fields, $second)
    {
        return array_map(
            'unserialize', array_unique(
                array_map(
                    'serialize', array_merge(
                        $this->select($fields),
                        $second->select($fields)
                    )
                )
            )
        );
    }

    /**
     * Matches and merges fields between databases.
     *
     * @param string $method    the method to join (inner, left, right, full)
     * @param array $fields     the fields to select
     * @param Database $second  the second database to use
     * @param array $match      a key-value pair consisting of the left field name against the right field name
     * @return array            the joined data set
     */
    public function join($method, $fields, $second, $match)
    {
        $left = $this->load();
        $right = $second->load();
        $result = [];
        $values = [];
        if ($method === 'inner') {
            foreach ($left as $l) {
                foreach ($right as $r) {
                    if ($l[array_keys($match)[0]] === $r[array_values($match)[0]]) {
                        $result[] = array_merge($l, $r);
                    }
                }
            }
        } elseif ($method === 'left') {
            foreach ($left as $l) {
                foreach ($right as $r) {
                    if ($l[array_keys($match)[0]] === $r[array_values($match)[0]]) {
                        $values = array_merge($l, $r);
                        break;
                    } else {
                        $values = $l;
                    }
                }
                $result[] = $values;
                $values = [];
            }
        } elseif ($method === 'right') {
            foreach ($left as $l) {
                foreach ($right as $r) {
                    if ($l[array_keys($match)[0]] === $r[array_values($match)[0]]) {
                        $values = array_merge($l, $r);
                        break;
                    } else {
                        $values = $r;
                    }
                }
                $result[] = $values;
                $values = [];
            }
        } elseif ($method === 'full') {
            $result = array_map(
                'unserialize', array_unique(
                    array_map(
                        'serialize', array_merge(
                            $this->join('left', $fields, $second, $match),
                            $this->join('right', $fields, $second, $match)
                        )
                    )
                )
            );
        }
        return self::arraySelect($fields, $result);
    }

    /**
     * Checks whether the database contains a field with the specified value.
     *
     * @param string $field the field name
     * @param string $val   the value to check the field for
     * @return boolean      true if the pair exists, otherwise false
     */
    public function exists($field, $val)
    {
        $db = $this->load();
        $result = false;
        foreach ($db as $index => $row) {
            if ($row[$field] === $val) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Counts the number of items per field or for all fields.
     *
     * @param string $field the field name to count (empty for all fields)
     * @return int          the number of rows containing that field
     */
    public function count($field = '')
    {
        if ($field === '') {
            $query = [];
        } else {
            $query = (array) $field;
        }
        return count($this->select($query));
    }

    /**
     * Gets the first item in a field.
     *
     * @param string $field the field to look at
     * @return mixed        the first item in the field
     */
    public function first($field)
    {
        return $this->select((array) $field)[0][$field];
    }

    /**
     * Gets the last item in a field.
     *
     * @param string $field the name of the field to look at
     * @return mixed        the last item in the field
     */
    public function last($field)
    {
        $values = $this->select((array) $field);
        return end($values)[$field];
    }

    /**
     * Gets the key used to encrypt the database.
     *
     * @return string   the key used to encrypt the database
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets whether or not this database is encrypted.
     *
     * @return bool true if the database is encrypted, otherwise false
     */
    public function isEncrypted()
    {
        return $this->key !== '';
    }
}
