<?php

namespace Condense;

/**
 * Prequel: SQL-based manipulations for PHP arrays.
 * Select. Where. Like. Union. Join.
 *
 * PHP version 5
 *
 * @author    Alfred Xing <xing@lfred.info>
 * @copyright 2013 Alfred Xing
 * @license   LICENSE.md MIT License
 * @version   0.1.0
 *
 */
class Prequel
{

    /**
     * Get the row where the value matches that of the key and return the value of the other key
     *
     * @param string $col the column to get
     * @param string $key the key whose value to match
     * @param string $val the value to match
     * @param array  $db  the array to get from
     *
     * @return array
     */
    public static function get($col, $key, $val, $db)
    {
        foreach ($db as $row) {
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
     * @param array $db   the array to select from
     *
     * @return array
     */
    public static function select($cols, $db)
    {
        $_result = array();
        $_values = array();
        if ($cols === array()) {
            foreach ($db as $row) {
                foreach (array_keys($row) as $c) {
                    $_values[$c] = $row[$c];
                };
                if ($_values)
                    $_result[] = $_values;
                $_values = array();
            }
        } else {
            foreach ($db as $row) {
                foreach ((array) $cols as $c) {
                    if ($row[$c])
                        $_values[$c] = $row[$c];
                };
                if ($_values)
                    $_result[] = $_values;
                $_values = array();
            }
        }
        return $_result;
    }

    /**
     * Get the row where the value matches that of the key and return the value of the other key
     *
     * @param array  $cols the columns to select
     * @param string $key  the key whose value to match
     * @param string $val  the value to match
     * @param array  $db   the array to work on
     *
     * @return array
     */
    public static function where($cols, $key, $val, $db)
    {
        $_result = array();
        $_values = array();
        if ($cols === array()) {
            foreach ($db as $row) {
                if ($row[$key] === $val) {
                    foreach (array_keys($row) as $c) {
                        $_values[$c] = $row[$c];
                    };
                    $_result[] = $_values;
                    $_values = array();
                }
            }
        } else {
            foreach ($db as $row) {
                if ($row[$key] === $val) {
                    foreach ((array) $cols as $c) {
                        $_values[$c] = $row[$c];
                    };
                    $_result[] = $_values;
                    $_values = array();
                };
            }
        }
        return $_result;
    }

    /**
     * Get columns from rows in which the key's value is part of the inputted array of values
     *
     * @param array  $cols the columns to return
     * @param string $key  the column to look for the value
     * @param array  $val  an array of values to be accepted
     * @param array  $db   the array to work on
     *
     * @return array
     */
    public static function in($cols, $key, $val, $db)
    {
        $_result = array();
        $_values = array();
        if ($cols === array()) {
            foreach ($db as $row) {
                if (in_array($row[$key], $val)) {
                    foreach (array_keys($row) as $c) {
                        $_values[$c] = $row[$c];
                    };
                    $_result[] = $_values;
                    $_values = array();
                }
            }
        } else {
            foreach ($db as $row) {
                if (in_array($row[$key], $val)) {
                    foreach ((array) $cols as $c) {
                        $_values[$c] = $row[$c];
                    };
                    $_result[] = $_values;
                    $_values = array();
                };
            }
        }
        return $_result;
    }

    /**
     * Matches keys and values based on a regular expression
     *
     * @param array  $cols  the columns to return; an empty array returns all columns
     * @param string $key   the column whose value to match
     * @param string $regex the regular expression to match
     * @param array  $db    the array to go through
     *
     * @return array
     */
    public static function like($cols, $key, $regex, $db)
    {
        $_result = array();
        $_values = array();
        if ($cols === array()) {
            foreach ($db as $row) {
                if (preg_match($regex, $row[$key])) {
                    foreach (array_keys($row) as $c) {
                        $_values[$c] = $row[$c];
                    };
                    $_result[] = $_values;
                    $_values = array();
                }
            }
        } else {
            foreach ($db as $row) {
                if (preg_match($regex, $row[$key])) {
                    foreach ((array) $cols as $c) {
                        $_values[$c] = $row[$c];
                    };
                    $_result[] = $_values;
                    $_values = array();
                };
            }
        }
        return $_result;
    }

    /**
     * Merges two databases and gets rid of duplicates
     *
     * @param array $cols   the columns to merge
     * @param array $left   the first array to merge
     * @param array $right  the second array to merge
     *
     * @return array          the merged array
     */
    public static function union($cols, $left, $right)
    {
        return array_map(
            "unserialize", array_unique(
                array_map(
                    "serialize", array_merge(
                        self::select($cols, $left),
                        self::select($cols, $right)
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
     * @param array  $left   the first database to consider
     * @param array  $right  the second database to consider
     * @param array  $match  a key-value pair: left column to match => right column
     *
     * @return array joined array
     */
    public static function join($method, $cols, $left, $right, $match)
    {
        $_result = array();
        $_values = array();
        if ($method === "inner") {
            foreach ($left as $lrow) {
                foreach ($right as $rrow) {
                    if ($lrow[array_keys($match)[0]] === $rrow[array_values($match)[0]]) {
                        $_result[] = array_merge($lrow, $rrow);
                    }
                }
            }
        } elseif ($method === "left") {
            foreach ($left as $lrow) {
                foreach ($right as $rrow) {
                    if ($lrow[array_keys($match)[0]] === $rrow[array_values($match)[0]]) {
                        $_values = array_merge($lrow, $rrow);
                        break;
                    } else {
                        $_values = $lrow;
                    }
                }
                $_result[] = $_values;
                $_values = array();
            }
        } elseif ($method === "right") {
            foreach ($left as $lrow) {
                foreach ($right as $rrow) {
                    if ($lrow[array_keys($match)[0]] === $rrow[array_values($match)[0]]) {
                        $_values = array_merge($lrow, $rrow);
                        break;
                    } else {
                        $_values = $rrow;
                    }
                }
                $_result[] = $_values;
                $_values = array();
            }
        } elseif ($method === "full") {
            $_result = array_map(
                "unserialize", array_unique(
                    array_map(
                        "serialize", array_merge(
                            self::join("left", $cols, $left, $right, $match),
                            self::join("right", $cols, $left, $right, $match)
                        )
                    )
                )
            );
        }
        return self::select($cols, $_result);
    }

    /**
     * Checks whether the given key/value pair exists
     *
     * @param string $key the key
     * @param string $val the value
     * @param array  $db  the array to work on
     *
     * @return boolean whether the pair exists
     */
    public static function exists($key, $val, $db)
    {
        $_result = false;
        foreach ($db as $row) {
            if ($row[$key] === $val) {
                $_result = true;
            }
        }
        return $_result;
    }

    /**
     * Counts the number of items per column or for all columns
     *
     * @param string $col   the column name to count. No input counts all columns.
     * @param array $db     the array to count
     * @return int          the number of rows containing that column.
     */
    public static function count($col, $db)
    {
        if ($col === "") {
            $query = array();
        } else {
            $query = (array) $col;
        }
        return count(self::select($query, $db));
    }

    /**
     * Gets the first item of a column
     *
     * @param string $col   the column to look at
     * @param array $db     the array to work on
     * @return mixed        the first item in the column
     */
    public static function first($col, $db)
    {
        return self::select((array) $col, $db)[0][$col];
    }

    /**
     * Gets the last item in a column
     *
     * @param string $col   the name of the column to look at
     * @param array $db     the array to work on
     * @return mixed        the last item in the column
     */
    public static function last($col, $db)
    {
        $values = self::select((array) $col, $db);
        return end($values)[$col];
    }
}
