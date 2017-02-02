<?php

namespace Pim\Bundle\CatalogBundle\tests\integration\Doctrine\Filter;

use Pim\Component\Catalog\AttributeTypes;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Query\Filter\Operators;

/**
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class LocalizableScopableFilterIntegration extends AbstractFilterTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (1 === self::$count) {
            $this->createAttribute([
                'code'                => 'a_metric_scopable_localizable',
                'attribute_type'      => AttributeTypes::METRIC,
                'localizable'         => true,
                'scopable'            => true,
                'decimals_allowed'    => true,
                'metric_family'       => 'Power',
                'default_metric_unit' => 'KILOWATT'
            ]);

            $this->createProduct('product_one', [
                'values' => [
                    'a_metric_scopable_localizable' => [
                        ['data' => ['amount' => '-5.00', 'unit' => 'KILOWATT'], 'locale' => 'en_US', 'scope' => 'ecommerce'],
                        ['data' => ['amount' => '14', 'unit' => 'KILOWATT'], 'locale' => 'en_US', 'scope' => 'tablet'],
                        ['data' => ['amount' => '100', 'unit' => 'KILOWATT'], 'locale' => 'fr_FR', 'scope' => 'tablet'],
                    ],
                ]
            ]);

            $this->createProduct('product_two', [
                'values' => [
                    'a_metric_scopable_localizable' => [
                        ['data' => ['amount' => '-5.00', 'unit' => 'KILOWATT'], 'locale' => 'en_US', 'scope' => 'ecommerce'],
                        ['data' => ['amount' => '10', 'unit' => 'KILOWATT'], 'locale' => 'en_US', 'scope' => 'tablet'],
                        ['data' => ['amount' => '75', 'unit' => 'KILOWATT'], 'locale' => 'fr_FR', 'scope' => 'tablet'],
                        ['data' => ['amount' => '75', 'unit' => 'KILOWATT'], 'locale' => 'fr_FR', 'scope' => 'ecommerce'],
                    ],
                ]
            ]);

            $this->createProduct('empty_product', []);
        }
    }

    public function testOperatorInferior()
    {
        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::LOWER_THAN,
            ['amount' => 10, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'tablet']
        ]]);
        $this->getResults($result);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::LOWER_THAN,
            ['amount' => 10.0001, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'tablet']
        ]]);
        $this->getResults($result, ['product_two']);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::LOWER_THAN,
            ['amount' => 80, 'unit' => 'KILOWATT'],
            ['locale' => 'fr_FR', 'scope' => 'ecommerce']
        ]]);
        $this->getResults($result, ['product_two']);
    }

    public function testOperatorInferiorOrEquals()
    {
        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::LOWER_OR_EQUAL_THAN,
            ['amount' => 10, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'tablet']
        ]]);
        $this->getResults($result, ['product_two']);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::LOWER_OR_EQUAL_THAN,
            ['amount' => 100, 'unit' => 'KILOWATT'],
            ['locale' => 'fr_FR', 'scope' => 'tablet']
        ]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorEquals()
    {
        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::EQUALS,
            ['amount' => -5, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'tablet']
        ]]);
        $this->getResults($result);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::EQUALS,
            ['amount' => -5, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'ecommerce']
        ]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorSuperior()
    {
        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::GREATER_THAN,
            ['amount' => -5, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'ecommerce']
        ]]);
        $this->getResults($result);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::GREATER_THAN,
            ['amount' => -5.0001, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'ecommerce']
        ]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorSuperiorOrEquals()
    {
        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::GREATER_OR_EQUAL_THAN,
            ['amount' => -5, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'ecommerce']
        ]]);
        $this->getResults($result, ['product_one', 'product_two']);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::GREATER_OR_EQUAL_THAN,
            ['amount' => 80, 'unit' => 'KILOWATT'],
            ['locale' => 'fr_FR', 'scope' => 'tablet']
        ]]);
        $this->getResults($result, ['product_one']);
    }

    public function testOperatorEmpty()
    {
        $result = $this->execute([['a_metric_scopable_localizable', Operators::IS_EMPTY, [], ['locale' => 'en_US', 'scope' => 'tablet']]]);
        $this->getResults($result, ['empty_product']);
    }

    public function testOperatorNotEmpty()
    {
        $result = $this->execute([['a_metric_scopable_localizable', Operators::IS_NOT_EMPTY, [], ['locale' => 'en_US', 'scope' => 'tablet']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorDifferent()
    {
        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::NOT_EQUAL,
            ['amount' => 10, 'unit' => 'WATT'],
            ['locale' => 'en_US', 'scope' => 'tablet']
        ]]);
        $this->getResults($result, ['product_one', 'product_two']);

        $result = $this->execute([[
            'a_metric_scopable_localizable',
            Operators::NOT_EQUAL,
            ['amount' => 10, 'unit' => 'KILOWATT'],
            ['locale' => 'en_US', 'scope' => 'tablet']
        ]]);
        $this->getResults($result, ['product_one']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Attribute or field "a_metric_scopable_localizable" expects valid data, scope and locale. Attribute "a_metric_scopable_localizable" expects a locale, none given.
     */
    public function testErrorMetricLocalizableAndScopable()
    {
        $this->execute([['a_metric_scopable_localizable', Operators::NOT_EQUAL, ['amount' => 250, 'unit' => 'KILOWATT']]]);
    }
}
