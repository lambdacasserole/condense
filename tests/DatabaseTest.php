<?php
/**
 * Created by PhpStorm.
 * User: saula
 * Date: 11/08/2017
 * Time: 00:03
 */

namespace Condense;

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private static function getTestFixture()
    {
        return [
            [
                'field1' => 'object1_data1',
                'field2' => 'object1_data2'
            ],
            [
                'field1' => 'object2_data1',
                'field2' => 'object2_data2'
            ]
        ];
    }

    public function testConstructWithNoSlash()
    {
        // Construct database without trailing path slash.
        $subject = new Database(uniqid('ct_'), __DIR__);

        self::assertTrue(file_exists($subject->getFile())); // Database file should now exist.
    }

    public function testConstructWithSlash()
    {
        // Construct database with trailing path slash.
        $subject = new Database(uniqid('ct_'), __DIR__ . '/');

        self::assertTrue(file_exists($subject->getFile())); // Database file should now exist.
    }

    public function testConstructExtensionAppend()
    {
        $subject = new Database(uniqid('ct_'), __DIR__);

        self::assertTrue(pathinfo($subject->getFile(), PATHINFO_EXTENSION) === 'dat'); // Extension should be added.
    }

    public function testDelete()
    {
        $subject = new Database(uniqid('ct_'), __DIR__);

        self::assertTrue(file_exists($subject->getFile())); // Database file should now exist.

        // Call delete method.
        $subject->delete();

        self::assertFalse(file_exists($subject->getFile())); // Database file should no longer exist.
    }

    public function testLoad()
    {
        // Put data into file manually.
        $name = uniqid('ct_');
        $data = json_encode(self::getTestFixture());
        file_put_contents(__DIR__ . "/$name.dat", $data);

        // Construct database from manually created file.
        $subject = new Database($name, __DIR__);

        self::assertTrue($data === json_encode($subject->load())); // Data should be identical.
    }

    public function testInsert()
    {
        $subject = new Database(uniqid('ct_'), __DIR__);

        // Insert test data.
        $rows = self::getTestFixture();
        foreach ($rows as $row) {
            $subject->insert($row);
        }

        self::assertTrue(json_encode($rows) === json_encode($subject->load())); // We should be able to retrieve it.
    }

    public function testRemove()
    {
        $subject = new Database(uniqid('ct_'), __DIR__);

        $rows = self::getTestFixture();
        foreach ($rows as $row) {
            $subject->insert($row);
        }

        // Remove first row.
        $subject->remove(0);

        // First row should be gone.
        array_splice($rows, 0, 1);
        self::assertTrue(json_encode($rows) === json_encode($subject->load()));
    }



    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        // Remove all databases created.
        $databases = scandir(__DIR__);
        foreach ($databases as $database) {
            if (pathinfo($database, PATHINFO_EXTENSION) === 'dat') {
                unlink(__DIR__ . '/' . $database);
            }
        }
    }
}
