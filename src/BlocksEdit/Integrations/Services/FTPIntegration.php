<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use FtpClient\FtpClient;
use GuzzleHttp\Exception\GuzzleException;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use Exception;
use RuntimeException;

/**
 * Class FTPIntegration
 */
class FTPIntegration extends AbstractFilesystemIntegration
{
    /**
     * @var SFTP|null
     */
    protected $sftp;

    /**
     * @var FtpClient|null
     */
    protected $ftp;

    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'ftp';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'FTP/sFTP';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Import templates from an FTP or sFTP account.';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(): int
    {
        return 9900;
    }

    /**
     * {@inheritDoc}
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-ftp.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/ftp-sftp-setup/';
    }

    /**
     * {@inheritDoc}
     */
    public function getHomeDirectoryPlaceholder(): string
    {
        return 'Home directory';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultHomeDirectory(): string
    {
        return '/';
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return parent::getFrontendSettings([
            self::RULE_NO_FOLDER_SPACES => true
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function connect(): bool
    {
        if (!$this->isConnected()) {
            if ($this->settings['type'] === 'sftp') {
                if ($this->settings['password']) {
                    $key = $this->settings['password'];
                } else if ($this->settings['cert']) {
                    $key = new RSA();
                    $key->loadKey($this->settings['cert']);
                } else {
                    throw new RuntimeException('No password or cert given.');
                }

                $this->sftp = new SFTP($this->settings['host'], $this->settings['port']);
                if (!$this->sftp->login($this->settings['username'], $key)) {
                    throw new Exception('Log in failed');
                }
            } else {
                $this->ftp = new FtpClient();
                $this->ftp->connect($this->settings['host'], false, $this->settings['port']);
                $this->ftp->login($this->settings['username'], $this->settings['password']);
                /** @phpstan-ignore-next-line */
                $this->ftp->pasv(!empty($this->settings['pasv']) && $this->settings['pasv']);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        if ($this->isConnected()) {
            $this->sftp->_disconnect(1);
            $this->sftp = null;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->sftp !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDirectoryListing(string $dir): array
    {
        $files = [];

        $this->connect();
        if ($this->sftp) {
            foreach ($this->sftp->rawlist($dir) as $name => $info) {
                $files[] = new FileInfo(
                    $name,
                    $dir,
                    $info['size'],
                    $info['mtime'],
                    $info['type'] == 2,
                    $info
                );
            }
        } else {
            $this->ftp->chdir($dir);
            foreach($this->ftp->scanDir() as $info) {
                if ($info['type'] === 'link' && preg_match('/(.*?) ->/', $info['name'], $matches)) {
                    $name = $matches[1];
                } else {
                    $name = $info['name'];
                }

                $files[] = new FileInfo(
                    $name,
                    $dir,
                    $info['size'],
                    0,
                    ($info['type'] === 'directory' || $info['type'] === 'link'),
                    $info
                );
            }
        }

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory(string $dir): bool
    {
        $this->connect();

        if ($this->sftp) {
            return $this->sftp->mkdir($dir, -1, true);
        }
        return $this->ftp->mkdir($dir, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory(string $dir): bool
    {
        $this->connect();

        if ($this->sftp) {
            return $this->sftp->rmdir($dir);
        }
        return $this->ftp->rmdir($dir);
    }

    /**
     * {@inheritDoc}
     * @throws GuzzleException
     */
    public function uploadFile(
        string $remoteFilename,
        string $localFilename,
        string $assetType,
        int $assetID,
        $subject = '',
        array $extra = []
    ): string
    {
        $this->connect();

        if ($this->exists($remoteFilename)) {
            $this->deleteFile($remoteFilename);
        }

        if ($this->sftp) {
            $parts = pathinfo($remoteFilename);
            $this->sftp->chdir($parts['dirname']);
            $this->sftp->put($parts['basename'], $localFilename, SFTP::SOURCE_LOCAL_FILE);
        } else {
            $this->ftp->put($remoteFilename, $localFilename, FTP_BINARY);
        }

        return $this->getFileURL($remoteFilename);
    }

    /**
     * {@inheritDoc}
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int
    {
        $this->connect();

        if ($this->sftp) {
            $parts = pathinfo($remoteFilename);
            $this->sftp->chdir($parts['dirname']);

            return $this->sftp->get($parts['basename'], $localFilename);
        }

        return (int)$this->ftp->get($localFilename, $remoteFilename, FTP_BINARY);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile(string $remoteFilename): bool
    {
        $this->connect();

        if ($this->sftp) {
            $parts = pathinfo($remoteFilename);
            $this->sftp->chdir($parts['dirname']);

            return $this->sftp->delete($parts['basename']);
        }

        return $this->ftp->delete($remoteFilename);
    }

    /**
     * {@inheritDoc}
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool
    {
        $this->connect();

        if ($this->sftp) {
            return $this->sftp->rename($remoteOldName, $remoteNewName);
        }

        return $this->ftp->rename($remoteOldName, $remoteNewName);
    }

    /**
     * @param string $remoteFilename
     *
     * @return string
     * @throws Exception
     */
    public function getFileURL(string $remoteFilename): string
    {
        // if (strpos($remoteFilename, $this->settings['home_dir']) !== 0) {
            // throw new Exception('Invalid directory.'); This is causing problems.
        // }

        $urlPath = substr($remoteFilename, strlen($this->settings['home_dir']));

        return sprintf('%s/%s', rtrim($this->settings['url'], '/'), ltrim($urlPath, '/'));
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $remoteFilename): bool
    {
        $this->connect();

        if ($this->sftp) {
            $size = $this->sftp->size($remoteFilename);
        } else {
            $size = $this->ftp->size($remoteFilename);
        }

        return (bool)$size;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        $form = [
            'url'  => [
                'type'     => 'text',
                'label'    => 'Base URL (Match to home directory location)',
                'required' => true
            ],
            'type' => [
                'type'     => 'choice',
                'label'    => 'Connection Type',
                'required' => true,
                'choices'  => [
                    ''     => 'Select...',
                    'ftp'  => 'FTP',
                    'sftp' => 'sFTP'
                ]
            ],
            'host' => [
                'type'     => 'text',
                'label'    => 'Hostname',
                'required' => true
            ],
            'port' => [
                'type'  => 'number',
                'label' => 'Port Number'
            ],
            'pasv' => [
                'type'  => 'checkbox',
                'label' => 'Passive Mode'
            ],
            'cert' => [
                'type'  => 'file',
                'label' => 'Certificate'
            ],
            'username' => [
                'type'  => 'text',
                'label' => 'Username'
            ],
            'password' => [
                'type'  => 'password',
                'label' => 'Password'
            ]
        ];

        return $this->applyFormValues($form, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsScript(): string
    {
        return file_get_contents(__DIR__ . '/scripts/ftp.js');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSettings(): array
    {
        return [
            'type'     => 'sftp',
            'host'     => '127.0.0.1',
            'url'      => 'http://example.com/images',
            'port'     => 22,
            'pasv'     => true,
            'cert'     => '',
            'username' => '',
            'password' => ''
        ];
    }
}
