<?php
namespace spec\groupcash\php\io;

use groupcash\php\io\transformers\CoinTransformer;
use groupcash\php\model\Base;
use groupcash\php\model\Coin;
use groupcash\php\model\Confirmation;
use groupcash\php\model\Fraction;
use groupcash\php\model\Input;
use groupcash\php\model\Output;
use groupcash\php\model\Promise;
use groupcash\php\model\Transaction;
use rtens\scrut\Assert;
use rtens\scrut\fixtures\ExceptionFixture;

/**
 * For interoperability, coins are transformed to a standardized structure
 *
 * @property CoinTransformer transformer <-
 * @property Assert assert <-
 * @property ExceptionFixture try <-
 */
class TransformCoinSpec {

    function unsupportedCoinVersion() {
        $this->try->tryTo(function () {
            $this->transformer->arrayToObject([CoinTransformer::TOKEN, ['v' => 'foo']]);
        });
        $this->try->thenTheException_ShouldBeThrown('Unsupported coin version.');
    }

    function complete() {
        $coin = new Coin(new Input(
            new Transaction(
                [new Input(
                    new Base(
                        new Promise('coin', 'My Promise'),
                        new Output('the backer', new Fraction(1)),
                        'the issuer', 'el issuero'
                    ),
                    0
                ), new Input(
                    new Confirmation(
                        [
                            new Base(
                                new Promise('foo', 'Her Promise'),
                                new Output('the backress', new Fraction(1)),
                                'the issuress', 'la issuera'
                            )
                        ],
                        new Output('apu', new Fraction(42)),
                        'my print',
                        'la lisa'),
                    0
                )],
                [
                    new Output('homer', new Fraction(3, 13)),
                    new Output('marge', new Fraction(0, 7)),
                ],
                'el barto'
            ),
            42
        ));

        $array = $this->transformer->objectToArray($coin);

        $this->assert->equals($this->transformer->arrayToObject($array), $coin);

        $this->assert->equals($array[0], CoinTransformer::TOKEN);
        $this->assert->equals($array[1], [
            'v' => $coin->version(),
            'in' => [
                'iout' => 42,
                'tx' => [
                    'ins' => [
                        [
                            'iout' => 0,
                            'tx' => [
                                'promise' => [
                                    'coin',
                                    'My Promise'
                                ],
                                'out' => [
                                    'to' => 'the backer',
                                    'val' => 1
                                ],
                                'by' => 'the issuer',
                                'sig' => 'el issuero'
                            ]
                        ],
                        [
                            'iout' => 0,
                            'tx' => [
                                'finger' => 'my print',
                                'bases' => [
                                    [
                                        'promise' => [
                                            'foo',
                                            'Her Promise'
                                        ],
                                        'out' => [
                                            'to' => 'the backress',
                                            'val' => 1
                                        ],
                                        'by' => 'the issuress',
                                        'sig' => 'la issuera'
                                    ]
                                ],
                                'out' => [
                                    'to' => 'apu',
                                    'val' => 42
                                ],
                                'sig' => 'la lisa'
                            ]
                        ]
                    ],
                    'outs' => [
                        [
                            'to' => 'homer',
                            'val' => [3, 13]
                        ],
                        [
                            'to' => 'marge',
                            'val' => 0
                        ]
                    ],
                    'sig' => 'el barto'
                ]
            ]
        ]);
    }
}