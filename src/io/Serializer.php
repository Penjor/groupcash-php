<?php
namespace groupcash\php\io;

class Serializer {

    /** @var Transcoder[] indexed by key */
    private $transcoders = [];

    /** @var Transformer[] */
    private $transformers = [];

    /**
     * @param string $key
     * @param Transcoder $transcoder
     * @return Serializer
     */
    public function registerTranscoder($key, Transcoder $transcoder) {
        $this->transcoders[$key] = $transcoder;
        return $this;
    }

    /**
     * @param Transformer $transformer
     * @return Serializer
     */
    public function addTransformer(Transformer $transformer) {
        $this->transformers[] = $transformer;
        return $this;
    }

    /**
     * @param string|object $classOrObject
     * @return bool
     */
    public function handles($classOrObject) {
        $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;

        foreach ($this->transformers as $transformer) {
            if ($transformer->transforms() == $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param object $object
     * @param null|string $transcoderKey
     * @return string
     */
    public function serialize($object, $transcoderKey = null) {
        $transcoder = $this->getTranscoder($transcoderKey);
        $transformer = $this->getTransformerForObject($object);

        return $transcoder->encode($transformer->objectToArray($object));
    }

    /**
     * @param string $serialized
     * @return object
     * @throws \Exception
     */
    public function inflate($serialized) {
        $array = $this->decode($serialized);
        $transformer = $this->getTransformerForArray($array);
        return $transformer->arrayToObject($array);
    }

    /**
     * @param string $encoded
     * @return array
     * @throws \Exception
     */
    public function decode($encoded) {
        $transcoder = $this->getTranscoderForString($encoded);
        return $transcoder->decode($encoded);
    }

    private function getTranscoder($transcoderKey = null) {
        if (!$transcoderKey && $this->transcoders) {
            return array_values($this->transcoders)[0];
        } else if (array_key_exists($transcoderKey, $this->transcoders)) {
            return $this->transcoders[$transcoderKey];
        } else {
            throw new \Exception("Transcoder not registered: [$transcoderKey]");
        }
    }

    public function getTranscoderKeys() {
        return array_keys($this->transcoders);
    }

    private function getTransformerForObject($object) {
        foreach ($this->transformers as $transformer) {
            if ($transformer->transforms() == get_class($object)) {
                return $transformer;
            }
        }
        throw new \Exception('Not transformer registered for [' . get_class($object) . '].');
    }

    private function getTranscoderForString($string) {
        foreach ($this->transcoders as $transcoder) {
            if ($transcoder->hasEncoded($string)) {
                return $transcoder;
            }
        }
        throw new \Exception('No matching transcoder registered.');
    }

    private function getTransformerForArray($array) {
        foreach ($this->transformers as $transformer) {
            if ($transformer->matches($array)) {
                return $transformer;
            }
        }
        throw new \Exception('New matching transformer available.');
    }
}