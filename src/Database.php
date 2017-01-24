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
     * @param string $key   the key used to encrypt the database
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
        $data = file_get_contents($this->file);
        if (strlen($data) === 0) {
            return array();
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
     * Adds data to database.
     *
     * @param array $data   the data to add
     * @return array        the updated data set
     */
    public function add($data)
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
     * @param string $col   the name of the field to change
     * @param string $to    the value to change the field to
     * @param string $key   the name of the field to search for
     * @param string $val   the value of the field to search for
     * @return array        the updated data set
     */
    public function to($col, $to, $key, $val)
    {
        $db = $this->load();
        foreach ($db as $index => $row) {
            if ($row[$key] === $val) {
                $db[$index][$col] = $to;
            }
        }
        return $this->rewrite($db);
    }

    /**
     * Get the row where the value matches that of the key and returns the value of the other key.
     *
     * @param string $col   the name of the field to return
     * @param string $key   the name of the field to search for
     * @param string $val   the value of the field to search for
     * @return array        the matching row
     */
    public function get($col, $key, $val)
    {
        $db = $this->load();
        foreach ($db as $index => $row) {
            if ($row[$key] === $val && $row[$col]) {
                return $row[$col];
                break;
            }
        }
        return null;
    }

    /**
     * Get a set of columns for all rows
     *
     * @param array $cols the list of columns to get, empty for all
     *
     * @return array
     */
    function select($cols = array())
    {
        $db = $this->load();
        $result = array();
        $values = array();
        if ($cols === array()) {
            foreach ($db as $index => $row) {
                foreach (array_keys($row) as $c) {
                    $values[$c] = $row[$c];
                }
                if ($values) {
                    $result[$index] = $values;
                }
                $values = array();
            }
        } else {
            foreach ($db as $index => $row) {
                foreach ((array) $cols as $c) {
                    if ($row[$c]) {
                        $values[$c] = $row[$c];
                    }
                }
                if ($values) {
                    $result[$index] = $values;
                }
                $values = array();
            }
        }
        return $result;
    }

    /**
     * Get the row where the value matches that of the key and return the value of the other key
     *
     * @param array  $cols
     * @param string $key
     * @param string $val
     *
     * @return array
     */
    function where($cols, $key, $val)
    {
        $db = $this->load();
        $result = array();
        $values = array();
        if ($cols === array()) {
            foreach ($db as $index => $row) {
                if ($row[$key] === $val) {
                    foreach (array_keys($row) as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = array();
                }
            }
        } else {
            foreach ($db as $index => $row) {
                if ($row[$key] === $val) {
                    foreach ((array) $cols as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = array();
                }
            }
        }
        return $result;
    }

    /**
     * Get columns from rows in which the key's value is part of the inputted array of values
     *
     * @param array  $cols the columns to return
     * @param string $key  the column to look for the value
     * @param array  $val  an array of values to be accepted
     *
     * @return array
     */
    function in($cols, $key, $val)
    {
        $db = $this->load();
        $result = array();
        $values = array();
        if ($cols === array()) {
            foreach ($db as $index => $row) {
                if (in_array($row[$key], $val)) {
                    foreach (array_keys($row) as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = array();
                }
            }
        } else {
            foreach ($db as $index => $row) {
                if (in_array($row[$key], $val)) {
                    foreach ((array) $cols as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = array();
                }
            }
        }
        return $result;
    }

    /**
     * Matches keys and values based on a regular expression
     *
     * @param array  $cols  the columns to return; an empty array returns all columns
     * @param string $key   the column whose value to match
     * @param string $regex the regular expression to match
     *
     * @return array
     */
    function like($cols, $key, $regex)
    {
        $db = $this->load();
        $result = array();
        $values = array();
        if ($cols === array()) {
            foreach ($db as $index => $row) {
                if (preg_match($regex, $row[$key])) {
                    foreach (array_keys($row) as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = array();
                }
            }
        } else {
            foreach ($db as $index => $row) {
                if (preg_match($regex, $row[$key])) {
                    foreach ((array) $cols as $c) {
                        $values[$c] = $row[$c];
                    }
                    $result[$index] = $values;
                    $values = array();
                }
            }
        }
        return $result;
    }

    /**
     * Merges two databases and gets rid of duplicates
     *
     * @param array $cols   the columns to merge
     * @param Database $second the second database to merge
     *
     * @return array          the merged array
     */
    function union($cols, $second)
    {
        return array_map(
            'unserialize', array_unique(
                array_map(
                    'serialize', array_merge(
                        $this->select($cols),
                        $second->select($cols)
                    )
                )
            )
        );
    }

    /**
     * Matches and merges columns between databases
     *
     * @param string $method the method to join (inner, left, right, full)
     * @param array  $cols   the columns to select
     * @param Database  $second the second database to consider
     * @param array  $match  a key-value pair: left column to match => right column
     *
     * @return array joined array
     */
    function join($method, $cols, $second, $match)
    {
        $left = $this->load();
        $right = $second->load();
        $result = array();
        $values = array();
        if ($method === 'inner') {
            foreach ($left as $lrow) {
                foreach ($right as $rrow) {
                    if ($lrow[array_keys($match)[0]] === $rrow[array_values($match)[0]]) {
                        $result[] = array_merge($lrow, $rrow);
                    }
                }
            }
        } elseif ($method === 'left') {
            foreach ($left as $lrow) {
                foreach ($right as $rrow) {
                    if ($lrow[array_keys($match)[0]] === $rrow[array_values($match)[0]]) {
                        $values = array_merge($lrow, $rrow);
                        break;
                    } else {
                        $values = $lrow;
                    }
                }
                $result[] = $values;
                $values = array();
            }
        } elseif ($method === 'right') {
            foreach ($left as $lrow) {
                foreach ($right as $rrow) {
                    if ($lrow[array_keys($match)[0]] === $rrow[array_values($match)[0]]) {
                        $values = array_merge($lrow, $rrow);
                        break;
                    } else {
                        $values = $rrow;
                    }
                }
                $result[] = $values;
                $values = array();
            }
        } elseif ($method === 'full') {
            $result = array_map(
                'unserialize', array_unique(
                    array_map(
                        'serialize', array_merge(
                            $this
                               ->join('left', $cols, $second, $match),
                            $this
                               ->join('right', $cols, $second, $match)
                        )
                    )
                )
            );
        }
        return Prequel::select($cols, $result);
    }


    /**
     * Checks whether the given key/value pair exists
     *
     * @param string $key the key
     * @param string $val the value
     *
     * @return boolean whether the pair exists
     */
    function exists($key, $val)
    {
        $db = $this->load();
        $result = false;
        foreach ($db as $index => $row) {
            if ($row[$key] === $val) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Counts the number of items per column or for all columns
     *
     * @param string $col the column name to count. No input counts all columns.
     *
     * @return int the number of rows containing that column.
     */
    function count($col = '')
    {
        if ($col === '') {
            $query = array();
        } else {
            $query = (array) $col;
        }
        return count($this->select($query));
    }

    /**
     * Gets the first item of a column
     *
     * @param string $col the column to look at
     *
     * @return mixed the first item in the column
     */
    function first($col)
    {
        return $this->select((array) $col)[0][$col];
    }

    /**
     * Gets the last item in a column
     *
     * @param string $col the name of the column to look at
     *
     * @return mixed the last item in the column
     */
    function last($col)
    {
        $values = $this->select((array) $col);
        return end($values)[$col];
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
