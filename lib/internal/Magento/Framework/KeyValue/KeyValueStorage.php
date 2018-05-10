<?php
/**
 *
 * We are using Credis Client. Here are some reference materials which can help:
 *
     * Hashes:
     * @method bool|int      hSet(string $key, string $field, string $value)
     * @method bool          hSetNx(string $key, string $field, string $value)
     * @method bool|string   hGet(string $key, string $field)
     * @method bool|int      hLen(string $key)
     * @method bool          hDel(string $key, string $field)
     * @method array         hKeys(string $key, string $field)
     * @method array         hVals(string $key)
     * @method array         hGetAll(string $key)
     * @method bool          hExists(string $key, string $field)

     * @method bool          hMSet(string $key, array $keysValues)
     * @method array         hMGet(string $key, array $fields)

     *  * Sets:
     * @method int|Credis_Client           sAdd(string $key, mixed $value, string $valueN = null)
     * @method int|Credis_Client           sRem(string $key, mixed $value, string $valueN = null)
 */

class KeyValueStorage {

    private $redisClient;

    public function __construct(\Credis_Client $redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * Implement Tags as a Redis Sets
     * According to https://www.compose.com/articles/how-to-handle-tagged-data-with-redis-sets/
     *
     * Set is a key which can have arbitrary number of distinct values
     * We store every tag as a Set key. We add the hash key as a value of the set.
     *
     * Later, when we need to retrieve values for the certain tag,
     * we retrieve all the values from the set -- it will be arrey of hash keys
     *
     * Then, for every hash key we retrieve value from the hash table
     *
     * @param $key
     * @param $value
     * @param $tags
     */
    public function set($key, $value, $tags)
    {
        $this->redisClient->hSet($key, 'body', $value);
        $this->addTags($key, $tags);
    }

    /**
     * Retrieve all the values from the set -- it will be arrey of hash keys
     * Then, for every hash key we retrieve value from the hash table
     *
     * @param $tag
     * @return array
     */
    public function getByTag($tag)
    {
        $result = [];
        $hashKeys = $this->sMembers($tag);
        foreach ($hashKeys as $hashKey) {
            $result[] = $this->hGet($hashKey, 'body');
        }
        return $result;
    }

    public function addTags(string $key, array $tags)
    {
        foreach ($tags as $tag) {
            $this->redisClient->sAdd($tag, $key);
        }
    }

    public function setIfNotExists($key, $field, $value) {

    }

    /**
     * Get value by key
     *
     * @param $key
     * @return bool|Credis_Client|string
     */
    public function get($key) {
        return $this->redisClient->hGet($key, 'body');
    }

    /**
     * Does value exists for the key
     *
     * @param $key
     * @return bool|Credis_Client
     */
    public function exists($key)
    {
        return $this->redisClient->hExists($key, 'body');
    }
}