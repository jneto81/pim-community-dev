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
class LocalizableFilterIntegration extends AbstractFilterTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (1 === self::$count) {
            $this->createAttribute([
                'code'                => 'a_metric_localizable',
                'attribute_type'      => AttributeTypes::METRIC,
                'localizable'         => true,
                'decimals_allowed'    => false,
                'metric_family'       => 'Length',
                'default_metric_unit' => 'METER',
            ]);

            $this->createProduct('product_one', [
                'values' => [
                    'a_metric_localizable' => [
                        ['data' => ['amount' => 20, 'unit' => 'METER'], 'locale' => 'en_US', 'scope' => null],
                        ['data' => ['amount' => 21, 'unit' => 'METER'], 'locale' => 'fr_FR', 'scope' => null]
                    ]
                ]
            ]);

            $this->createProduct('product_two', [
                'values' => [
                    'a_metric_localizable' => [
                        ['data' => ['amount' => 10, 'unit' => 'METER'], 'locale' => 'en_US', 'scope' => null],
                        ['data' => ['amount' => 1, 'unit' => 'METER'], 'locale' => 'fr_FR', 'scope' => null]
                    ]
                ]
            ]);

            $this->createProduct('empty_product', []);
        }
    }

    public function testOperatorInferior()
    {
        $result = $this->execute([['a_metric_localizable', Operators::LOWER_THAN, ['amount' => 1, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_localizable', Operators::LOWER_THAN, ['amount' => 20, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result, ['product_two']);

        $result = $this->execute([['a_metric_localizable', Operators::LOWER_THAN, ['amount' => 21.0001, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorInferiorOrEquals()
    {
        $result = $this->execute([['a_metric_localizable', Operators::LOWER_OR_EQUAL_THAN, ['amount' => 1, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result, ['product_two']);

        $result = $this->execute([['a_metric_localizable', Operators::LOWER_OR_EQUAL_THAN, ['amount' => 20, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result, ['product_one', 'product_two']);

        $result = $this->execute([['a_metric_localizable', Operators::LOWER_OR_EQUAL_THAN, ['amount' => 21, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorEquals()
    {
        $result = $this->execute([['a_metric_localizable', Operators::EQUALS, ['amount' => 21, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_localizable', Operators::EQUALS, ['amount' => 21, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result, ['product_one']);
    }

    public function testOperatorSuperior()
    {
        $result = $this->execute([['a_metric_localizable', Operators::GREATER_THAN, ['amount' => 20, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_localizable', Operators::GREATER_THAN, ['amount' => 21, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_localizable', Operators::GREATER_THAN, ['amount' => 9, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorSuperiorOrEquals()
    {
        $result = $this->execute([['a_metric_localizable', Operators::GREATER_OR_EQUAL_THAN, ['amount' => 25, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_localizable', Operators::GREATER_OR_EQUAL_THAN, ['amount' => 20, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result, ['product_one']);

        $result = $this->execute([['a_metric_localizable', Operators::GREATER_OR_EQUAL_THAN, ['amount' => 1, 'unit' => 'METER'], ['locale' => 'fr_FR']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorEmpty()
    {
        $result = $this->execute([['a_metric_localizable', Operators::IS_EMPTY, [], ['locale' => 'en_US']]]);
        $this->getResults($result, ['empty_product']);
    }

    public function testOperatorNotEmpty()
    {
        $result = $this->execute([['a_metric_localizable', Operators::IS_NOT_EMPTY, [], ['locale' => 'en_US']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorDifferent()
    {
        $result = $this->execute([['a_metric_localizable', Operators::NOT_EQUAL, ['amount' => 20, 'unit' => 'METER'], ['locale' => 'en_US']]]);
        $this->getResults($result, ['product_two']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Attribute or field "a_metric_localizable" expects valid data, scope and locale. Attribute "a_metric_localizable" expects a locale, none given.
     */
    public function testErrorMetricLocalizable()
    {
        $this->execute([['a_metric_localizable', Operators::NOT_EQUAL, ['amount' => 250, 'unit' => 'KILOWATT']]]);
    }
}
