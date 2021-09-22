<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class ImageFormatsExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritDoc
     */
    public function getGlobals(): array
    {
        return [
            'imageFormats' => [
                'headerImage' => [
                    'fit' => '1920x500',
                    'quality' => 85,
                    'progressive' => true,
                    'picture' => true,
                    'class' => 'img-fluid img-responsive',
                    'media' => [
                        [
                            'srcset' => [
                                [
                                    'format' => [
                                        'fit' => '600x300'
                                    ],
                                    'rule' => '1x'
                                ],
                                [
                                    'format' => [
                                        'fit' => '1200x600'
                                    ],
                                    'rule' => '2x'
                                ]
                            ],
                            'rule' => '(max-width: 767px)'
                        ],
                        [
                            'srcset' => [
                                [
                                    'format' => [
                                        'fit' => '1500x400'
                                    ],
                                    'rule' => '1x'
                                ],
                                [
                                    'format' => [
                                        'fit' => '2000x533',
                                        'quality' => 70
                                    ],
                                    'rule' => '2x'
                                ]
                            ],
                            'rule' => '(min-width: 768px)'
                        ]
                    ]
                ],
                'columnedImage' => [
                    'width' => 720,
                    'progressive' => true,
                    'picture' => true,
                    'embed' => true,
                    //'loading' => 'lazy', // Only Chrome 77
                    'class' => 'img-fluid img-responsive',
                ],
                'thumbnail' => [
                    'fit' => '600x400',
                    'controls' => true,
                    'embed' => true,
                    'progressive' => true,
                    'picture' => true,
                    //'loading' => 'lazy', // Only Chrome 77
                    //'lazyload' => true, // Need JS lib
                    'class' => 'img-fluid img-responsive',
                    'media' => [
                        [
                            'srcset' => [
                                [
                                    'format' => [
                                        'fit' => '400x300'
                                    ],
                                    'rule' => '1x'
                                ],
                                [
                                    'format' => [
                                        'fit' => '400x300'
                                    ],
                                    'rule' => '2x'
                                ]
                            ],
                            'rule' => '(max-width: 767px)'
                        ],
                        [
                            'srcset' => [
                                [
                                    'format' => [
                                        'fit' => '400x300'
                                    ],
                                    'rule' => '1x'
                                ],
                                [
                                    'format' => [
                                        'fit' => '800x600',
                                        'quality' => 70
                                    ],
                                    'rule' => '2x'
                                ]
                            ],
                            'rule' => '(min-width: 768px)'
                        ]
                    ]
                ],
                'mini' => [
                    'fit' => '200x200',
                    'progressive' => true,
                ],
                'shareImage' => [
                    'fit' => '1200x630',
                    'absolute' => true,
                    'progressive' => true,
                ]
            ]
        ];
    }
}
