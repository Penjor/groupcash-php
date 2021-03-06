<?php
namespace groupcash\php\model;

/**
 * A Promise describes the delivery promise of a backer in a certain currency.
 *
 * The Output of the Promises Base defines how many units it is worth.
 */
class Promise implements Finger {

    /** @var string */
    private $currency;

    /** @var string */
    private $description;

    /**
     * @param string $currency
     * @param string $description
     */
    public function __construct($currency, $description) {
        $this->currency = $currency;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return mixed|array|Finger[]
     */
    public function getPrint() {
        return [$this->currency, $this->description];
    }
}