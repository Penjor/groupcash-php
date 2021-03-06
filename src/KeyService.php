<?php
namespace groupcash\php;

interface KeyService {

    /**
     * @return string
     */
    public function generatePrivateKey();

    /**
     * @param string $privateKey
     * @return string
     */
    public function publicKey($privateKey);

    /**
     * @param string $content
     * @param string $privateKey
     * @return string
     */
    public function sign($content, $privateKey);

    /**
     * @param string $content
     * @param string $publicKey
     * @param string $signature
     * @return bool
     */
    public function verify($content, $publicKey, $signature);

    /**
     * @param string $content
     * @return string
     */
    public function hash($content);
}