<?php
namespace Tests\BlocksEdit\Database;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Test\TestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @coversDefaultClass \BlocksEdit\Database\ArrayEntity
 */
class ArrayEntityTest extends TestCase
{
    /**
     * @covers ::offsetExists
     * @return void
     */
    public function testExists()
    {
        $fixture = new ArrayEntityTestFixture1();
        $this->assertTrue($fixture->offsetExists('usr_id'));
        $this->assertTrue($fixture->offsetExists('usr_tmp_id'));
        $this->assertTrue($fixture->offsetExists('usr_org_id'));
        $this->assertTrue($fixture->offsetExists('usr_is_site_admin'));
        $this->assertTrue($fixture->offsetExists('usr_date_created'));
        $this->assertTrue($fixture->offsetExists('date_created'));
        $this->assertFalse($fixture->offsetExists('date_updated'));

        $this->assertTrue(isset($fixture['usr_id']));
        $this->assertTrue(isset($fixture['usr_is_site_admin']));
        $this->assertFalse(isset($fixture['date_updated']));
    }

    /**
     * @covers ::offsetGet
     * @return void
     */
    public function testGet()
    {
        $fixture = new ArrayEntityTestFixture1();
        $this->assertEquals(55, $fixture->offsetGet('usr_id'));
        $this->assertEquals(2, $fixture->offsetGet('usr_tmp_id'));
        $this->assertEquals(7, $fixture->offsetGet('usr_org_id'));
        $this->assertEquals(true, $fixture->offsetGet('usr_is_site_admin'));
        $this->assertInstanceOf(\DateTime::class, $fixture->offsetGet('usr_date_created'));

        $this->assertEquals(55, $fixture['usr_id']);
        $this->assertEquals(true, $fixture['usr_is_site_admin']);
        $this->assertEquals(7, $fixture['usr_org_id']);

        try {
            $fixture['date_updated'];
            $this->fail();
        } catch (NoSuchPropertyException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @covers ::offsetSet
     * @depends testGet
     * @return void
     */
    public function testSet()
    {
        $fixture = new ArrayEntityTestFixture1();

        $fixture->offsetSet('usr_id', 66);
        $this->assertEquals(66, $fixture->offsetGet('usr_id'));
        $fixture->offsetSet('usr_is_site_admin', false);
        $this->assertFalse($fixture->offsetGet('usr_is_site_admin'));
        $fixture->offsetSet('is_site_admin', true);
        $this->assertTrue($fixture->offsetGet('usr_is_site_admin'));
        $fixture->offsetSet('usr_org_id', 9);
        $this->assertEquals(10, $fixture->offsetGet('usr_org_id'));


        $fixture['usr_id'] = 77;
        $this->assertEquals(77, $fixture['usr_id']);

        try {
            $fixture['date_updated'] = 'foo';
            $this->fail();
        } catch (NoSuchPropertyException $e) {
            $this->assertTrue(true);
        }

        try {
            $fixture[] = 'foo';
            $this->fail();
        } catch (NoSuchPropertyException $e) {
            $this->assertTrue(true);
        }
    }
}

class ArrayEntityTestFixture1 extends ArrayEntity
{
    protected $id = 55;

    protected $tmpId = 2;

    protected $orgId = 6;

    protected $isSiteAdmin = true;

    protected $dateCreated;

    public function __construct()
    {
        parent::__construct('usr_');
        $this->dateCreated = new \DateTime();
    }

    public function getOrgId(): int
    {
        return $this->orgId + 1;
    }

    public function setOrgId(int $val)
    {
        $this->orgId = $val;
    }
}
