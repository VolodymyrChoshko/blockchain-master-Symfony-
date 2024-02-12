<?php
namespace Tests\BlocksEdit\Database;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Cache\MemoryCache;
use BlocksEdit\Config\Config;
use BlocksEdit\Database\CacheTagInvalidator;
use BlocksEdit\Database\EntityAccessor;
use BlocksEdit\Database\EntityManager;
use BlocksEdit\Database\EntityMeta;
use BlocksEdit\Database\PDOFactory;
use BlocksEdit\Database\Repository;
use BlocksEdit\IO\Files;
use BlocksEdit\IO\Paths;
use BlocksEdit\System\ClassFinder;
use BlocksEdit\Test\TestCase;
use Entity\Comment;
use Entity\Email;
use Entity\User;
use Exception;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Repository\CommentRepository;
use Repository\EmailRepository;
use Repository\EmojiRepository;
use Repository\OrganizationsRepository;
use Repository\TemplatesRepository;
use Repository\UserRepository;

/**
 * @coversDefaultClass \BlocksEdit\Database\Repository
 */
class RepositoryTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityMeta
     */
    protected $meta;

    /**
     * @var EntityAccessor
     */
    protected $entityAccessor;

    /**
     * @var CacheTagInvalidator
     */
    protected $cacheTagInvalidator;

    /**
     * @var CommentRepository
     */
    protected $repo;

    /**
     * @param string $className
     *
     * @return Repository
     */
    protected function createRepo(string $className): Repository
    {
        $repo = new $className($this->pdo, $this->config, $this->em, $this->cacheTagInvalidator);
        $repo->setCache($this->cache);
        $repo->setLogger(new NullLogger());
        $repo->setFiles(new Files($this->config));
        // $this->repo->setPaths(new Paths($config));

        return $repo;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainer([]);
        $this->config = $this->getConfig();
        $this->config->pdo = [
            'adapter'  => 'mysql',
            'port'     => 3306,
            'host'     => 'localhost',
            'name'     => 'blocksedit_testing',
            'username' => 'blocksedit_testing',
            'password' => 'blocksedit_testing'
        ];
        $this->pdo                 = PDOFactory::create($this->config);
        $this->cache               = new MemoryCache();
        $this->em                  = new EntityManager($this->config, $this->cache, $this->container, new ClassFinder($this->config));
        $this->meta                = new EntityMeta();
        $this->entityAccessor      = new EntityAccessor($this->meta);
        $this->cacheTagInvalidator = new CacheTagInvalidator($this->em, $this->entityAccessor);
        $this->cacheTagInvalidator->setCache($this->cache);

        $this->tearDown();
        $this->repo = $this->createRepo(CommentRepository::class);
        $this->addContainerService(CommentRepository::class, $this->repo);
        $this->addContainerService(UserRepository::class, $this->createRepo(UserRepository::class));
        $this->addContainerService(EmojiRepository::class, $this->createRepo(EmojiRepository::class));
        $this->addContainerService(TemplatesRepository::class, $this->createRepo(TemplatesRepository::class));
        $this->addContainerService(EmailRepository::class, $this->createRepo(EmailRepository::class));
        $this->addContainerService(OrganizationsRepository::class, $this->createRepo(OrganizationsRepository::class));


        $stmt = $this->pdo->prepare('INSERT INTO usr_users (usr_email, usr_pass, usr_name) VALUES(?, ?, ?)');
        $stmt->execute(['sean@blocksedit.com', '123456', 'Sean']);

        $stmt = $this->pdo->prepare('INSERT INTO org_organization (org_name, org_token) VALUES(?, ?)');
        $stmt->execute(['Testing', 'testing']);

        $stmt = $this->pdo->prepare('INSERT INTO tmp_templates (tmp_usr_id, tmp_title, tmp_org_id) VALUES(?, ?, ?)');
        $stmt->execute([1, 'Test Template', 1]);

        $stmt = $this->pdo->prepare('INSERT INTO ema_emails (ema_tmp_id, ema_title, ema_token) VALUES(?, ?, ?)');
        $stmt->execute([1, 'Test Email', 'test_token']);

        $stmt = $this->pdo->prepare('INSERT INTO cmt_comments (cmt_ema_id, cmt_usr_id, cmt_content, cmt_date_created) VALUES(?, ?, ?, NOW())');
        $stmt->execute([1, 1, 'Hello, World!']);

        $stmt = $this->pdo->prepare('INSERT INTO emj_emojis (emj_uuid, emj_usr_id, emj_comment_id, emj_code, emj_date_created) VALUES(?, ?, ?, ?, NOW())');
        $stmt->execute(['aaaaa', 1, 1, 'xxxx']);
        $stmt = $this->pdo->prepare('INSERT INTO emj_emojis (emj_uuid, emj_usr_id, emj_comment_id, emj_code, emj_date_created) VALUES(?, ?, ?, ?, NOW())');
        $stmt->execute(['bbbbb', 1, 1, 'xxxx']);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->pdo->exec('TRUNCATE TABLE emj_emojis');
        $this->pdo->exec('TRUNCATE TABLE cmt_comments');
        $this->pdo->exec('TRUNCATE TABLE ema_emails');
        $this->pdo->exec('TRUNCATE TABLE tmp_templates');
        $this->pdo->exec('TRUNCATE TABLE usr_users');
        $this->pdo->exec('TRUNCATE TABLE org_organization');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @covers ::find
     * @throws Exception
     */
    public function testFind()
    {
        $comment = $this->repo->findOne([
            'id' => 1
        ]);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertInstanceOf(User::class, $comment->getUser());
        $this->assertInstanceOf(Email::class, $comment->getEmail());
        $this->assertCount(2, $comment->getEmojis());

        $emoji = $comment->getEmojis()[0];
        $comment->removeEmoji($emoji);
        $this->repo->update($comment);
        $this->assertCount(1, $comment->getEmojis());

        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS c FROM emj_emojis');
        $stmt->execute();
        $row = $stmt->fetchColumn();
        $this->assertEquals('1', $row);
    }
}
