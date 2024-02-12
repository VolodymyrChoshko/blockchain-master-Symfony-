<?php
namespace Service;

use Redis;

/**
 * Class UploadingStatus
 */
class UploadingStatus
{
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $uuid
     * @param string $message
     * @param int    $percent
     * @param int    $id
     * @param array  $meta
     *
     * @return bool
     */
    public function update(string $uuid, string $message, int $percent, int $id = 0, array $meta = []): bool
    {
        $status = json_encode(compact('message', 'percent', 'id', 'meta'));
        $this->redis->setex("uploadingStatus:$uuid", 3600, $status);

        return true;
    }

    /**
     * @param string $uuid
     *
     * @return array
     */
    public function get(string $uuid): array
    {
        $value = $this->redis->get("uploadingStatus:$uuid");
        if (!$value) {
            return [];
        }
        $value = json_decode($value, true);

        $value['errors'] = [];
        $errors = $this->redis->lRange("uploadingStatus:$uuid:errors", 0, 1000);
        foreach($errors as $error) {
            $value['errors'][] = json_decode($error, true);
        }

        return $value;
    }
}
