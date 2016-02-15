<?php
namespace spec\groupcash\php;

use groupcash\php\Groupcash;
use groupcash\php\model\Fraction;
use groupcash\php\model\Transference;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Each backer of a coin is responsible for validating its transactions to confirm the new owner and avoid
 * double-spending of a single coin. The new coin consist of the original coin, the public key of the new owner
 * and, if existing, a fingerprint of the previous coin.
 *
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 * @property Groupcash lib
 */
class ValidateCoinSpec {

    function before() {
        $this->lib = new Groupcash(new FakeKeyService());
    }

    function issuedCoin() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $validated = $this->lib->validateCoin('backer', $coins[0]);

        $this->assert->equals($validated, $coins[0]);
    }

    function failIfNotBacker() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $transferred = $this->lib->transferCoin('backer', $coins[0], 'public first');

        $this->try->tryTo(function () use ($transferred) {
            $this->lib->validateCoin('not backer', $transferred);
        });
        $this->try->thenTheException_ShouldBeThrown('Only the backer of a coin can validate it.');
    }

    function firstTransaction() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $transferred = $this->lib->transferCoin('backer', $coins[0], 'public first');

        $validated = $this->lib->validateCoin('backer', $transferred);

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public first', new Fraction(1),
            '(my promise42public backer)'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated));
    }

    function secondTransaction() {
        $this->lib = new Groupcash(new FakeKeyService());

        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');

        $validated = $this->lib->validateCoin('backer', $second);

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public second', new Fraction(1),
            '(my promise42public backerpublic first' .
            '(my promise42public backer))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated));
    }

    function thirdTransaction() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');
        $third = $this->lib->transferCoin('second', $second, 'public third');

        $validated = $this->lib->validateCoin('backer', $third);

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', new Fraction(1),
            '(my promise42public backerpublic second' .
            '(my promise42public backerpublic first' .
            '(my promise42public backer)))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated));
    }

    function transferenceAfterValidation() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');

        $validatedSecond = $this->lib->validateCoin('backer', $second);
        $third = $this->lib->transferCoin('second', $validatedSecond, 'public third');

        $validated = $this->lib->validateCoin('backer', $third);

        $this->assert->equals($validated->getTransaction(), new Transference($coins[0], 'public third', new Fraction(1),
            '(my promise42public backerpublic second' .
            '(my promise42public backer' .
            '(my promise42public backerpublic first' .
            '(my promise42public backer))))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated));
    }

    function fractions() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first', new Fraction(1, 2));
        $second = $this->lib->transferCoin('first', $first, 'public second', new Fraction(1, 3));
        $third = $this->lib->transferCoin('second', $second, 'public third', new Fraction(5, 7));

        $validated = $this->lib->validateCoin('backer', $third);

        $this->assert->equals($validated->getTransaction(),
            new Transference($coins[0], 'public third', new Fraction(5, 42),
                '(my promise42public backerpublic second' .
                '(my promise42public backerpublic first' .
                '(my promise42public backer)))'));
        $this->assert->equals($validated->getSignature()->getSigner(), 'public backer');
        $this->assert->isTrue($this->lib->verifyCoin($validated));
    }

    function validateAValidatedCoin() {
        $coins = $this->lib->issueCoins('issuer', 'public root', 'my promise', 'public backer', 42, 1);
        $first = $this->lib->transferCoin('backer', $coins[0], 'public first');
        $second = $this->lib->transferCoin('first', $first, 'public second');

        $validated = $this->lib->validateCoin('backer', $second);

        $again = $this->lib->validateCoin('backer', $validated);
        $this->assert->equals($again->getTransaction(),
            new Transference($coins[0], 'public second', new Fraction(1),
                '(my promise42public backer' .
                '(my promise42public backerpublic first' .
                '(my promise42public backer)))'));

        $again2 = $this->lib->validateCoin('backer', $validated);
        $this->assert->equals($again2->getTransaction(),
            new Transference($coins[0], 'public second', new Fraction(1),
                '(my promise42public backer' .
                '(my promise42public backerpublic first' .
                '(my promise42public backer)))'));
    }
}