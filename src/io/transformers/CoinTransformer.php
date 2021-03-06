<?php
namespace groupcash\php\io\transformers;

use groupcash\php\io\Transformer;
use groupcash\php\model\Coin;
use groupcash\php\model\Confirmation;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Base;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Transaction;

class CoinTransformer extends Transformer {

    const TOKEN = 'COIN';

    private static $SUPPORTED_VERSIONS = ['dev'];

    /**
     * @return string Name of class that is dToArray and arrayTod
     */
    public function transforms() {
        return Coin::class;
    }

    /**
     * @return string
     */
    protected function token() {
        return self::TOKEN;
    }

    /**
     * @param Coin $object
     * @return array
     */
    protected function toArray($object) {
        return $this->CoinToArray($object);
    }

    /**
     * @param array $array
     * @return object
     */
    protected function toObject($array) {
        return $this->arrayToCoin($array);
    }

    private function CoinToArray(Coin $coin) {
        return [
            'v' => $coin->version(),
            'in' => $this->InputToArray($coin->getInput())
        ];
    }

    private function arrayToCoin($array) {
        if (!in_array($array['v'], self::$SUPPORTED_VERSIONS)) {
            throw new \Exception('Unsupported coin version.');
        }

        return new Coin(
            $this->arrayToInput($array['in'])
        );
    }

    private function InputToArray(Input $input) {
        return [
            'iout' => $input->getOutputIndex(),
            'tx' => $this->TransactionToArray($input->getTransaction())
        ];
    }

    private function arrayToInput($array) {
        return new Input(
            $this->arrayToTransaction($array['tx']),
            $array['iout']
        );
    }

    private function TransactionToArray(Transaction $transaction) {
        if ($transaction instanceof Base) {
            return $this->BaseToArray($transaction);
        } else if ($transaction instanceof Confirmation) {
            return $this->ConfirmationToArray($transaction);
        }

        return [
            'ins' => array_map([$this, 'InputToArray'], $transaction->getInputs()),
            'outs' => array_map([$this, 'OutputToArray'], $transaction->getOutputs()),
            'sig' => $transaction->getSignature()
        ];
    }

    private function arrayToTransaction($array) {
        if (array_key_exists('promise', $array)) {
            return $this->arrayToBase($array);
        } else if (array_key_exists('finger', $array)) {
            return $this->arrayToConfirmation($array);
        }

        return new Transaction(
            array_map([$this, 'arrayToInput'], $array['ins']),
            array_map([$this, 'arrayToOutput'], $array['outs']),
            $array['sig']
        );
    }

    private function BaseToArray(Base $base) {
        return [
            'promise' => $this->PromiseToArray($base->getPromise()),
            'out' => $this->OutputToArray($base->getOutput()),
            'by' => $base->getIssuerAddress(),
            'sig' => $base->getSignature()
        ];
    }

    private function arrayToBase($array) {
        return new Base(
            $this->arrayToPromise($array['promise']),
            $this->arrayToOutput($array['out']),
            $array['by'],
            $array['sig']
        );
    }

    private function ConfirmationToArray(Confirmation $confirmation) {
        return [
            'finger' => $confirmation->getHash(),
            'bases' => array_map([$this, 'BaseToArray'], $confirmation->getBases()),
            'out' => $this->OutputToArray($confirmation->getOutput()),
            'sig' => $confirmation->getSignature()
        ];
    }

    private function arrayToConfirmation($array) {
        return new Confirmation(
            array_map([$this, 'arrayToBase'], $array['bases']),
            $this->arrayToOutput($array['out']),
            $array['finger'],
            $array['sig']
        );
    }

    private function PromiseToArray(Promise $promise) {
        return [
            $promise->getCurrency(),
            $promise->getDescription()
        ];
    }

    private function arrayToPromise($array) {
        return new Promise(
            $array[0],
            $array[1]
        );
    }

    private function OutputToArray(Output $output) {
        return [
            'to' => $output->getTarget(),
            'val' => $this->FractionToArray($output->getValue())
        ];
    }

    private function arrayToOutput($array) {
        return new Output(
            $array['to'],
            $this->arrayToFraction($array['val'])
        );
    }

    private function FractionToArray(Fraction $fraction) {
        if ($fraction->getDenominator() == 1 || $fraction->getNominator() == 0) {
            return $fraction->getNominator();
        } else {
            return [$fraction->getNominator(), $fraction->getDenominator()];
        }
    }

    private function arrayToFraction($val) {
        if (is_array($val)) {
            list($nom, $den) = $val;
        } else {
            $nom = $val;
            $den = 1;
        }

        return new Fraction($nom, $den);
    }
}