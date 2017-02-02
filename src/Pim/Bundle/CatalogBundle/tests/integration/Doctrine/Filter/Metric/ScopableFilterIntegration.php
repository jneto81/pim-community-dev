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
class ScopableFilterIntegration extends AbstractFilterTestCase
{
    public function setUp()
    {
        parent::setUp();

        if (1 === self::$count) {
            $this->createAttribute([
                'code'                => 'a_metric_scopable',
                'attribute_type'      => AttributeTypes::METRIC,
                'localizable'         => false,
                'scopable'            => true,
                'decimals_allowed'    => true,
                'metric_family'       => 'Length',
                'default_metric_unit' => 'METER'
            ]);

            $this->createProduct('product_one', [
                'values' => [
                    'a_metric_scopable' => [
                        ['data' => ['amount' => '10.55', 'unit' => 'CENTIMETER'], 'locale' => null, 'scope' => 'ecommerce'],
                        ['data' => ['amount' => '25', 'unit' => 'CENTIMETER'], 'locale' => null, 'scope' => 'tablet']
                    ]
                ]
            ]);

            $this->createProduct('product_two', [
                'values' => [
                    'a_metric_scopable' => [
                        ['data' => ['amount' => '2', 'unit' => 'CENTIMETER'], 'locale' => null, 'scope' => 'ecommerce'],
                        ['data' => ['amount' => '30', 'unit' => 'CENTIMETER'], 'locale' => null, 'scope' => 'tablet']
                    ]
                ]
            ]);

            $this->createProduct('empty_product', []);
        }
    }

    public function testOperatorInferior()
    {
        $result = $this->execute([['a_metric_scopable', Operators::LOWER_THAN, ['amount' => 10.55, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_scopable', Operators::LOWER_THAN, ['amount' => 10.5501, 'unit' => 'CENTIMETER'], ['scope' => 'ecommerce']]]);
        $this->getResults($result, ['product_one', 'product_two']);

        $result = $this->execute([['a_metric_scopable', Operators::LOWER_THAN, ['amount' => 10.55, 'unit' => 'CENTIMETER'], ['scope' => 'ecommerce']]]);
        $this->getResults($result, ['product_two']);
    }

    public function testOperatorInferiorOrEquals()
    {
        $result = $this->execute([['a_metric_scopable', Operators::LOWER_OR_EQUAL_THAN, ['amount' => 2, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_scopable', Operators::LOWER_OR_EQUAL_THAN, ['amount' => 2, 'unit' => 'CENTIMETER'], ['scope' => 'ecommerce']]]);
        $this->getResults($result, ['product_two']);

        $result = $this->execute([['a_metric_scopable', Operators::LOWER_OR_EQUAL_THAN, ['amount' => 10.55, 'unit' => 'CENTIMETER'], ['scope' => 'ecommerce']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorEquals()
    {
        $result = $this->execute([['a_metric_scopable', Operators::EQUALS, ['amount' => 25, 'unit' => 'CENTIMETER'], ['scope' => 'ecommerce']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_scopable', Operators::EQUALS, ['amount' => 25, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_one']);
    }

    public function testOperatorSuperior()
    {
        $result = $this->execute([['a_metric_scopable', Operators::GREATER_THAN, ['amount' => 30, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result);

        $result = $this->execute([['a_metric_scopable', Operators::GREATER_THAN, ['amount' => 25, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_two']);
    }

    public function testOperatorSuperiorOrEquals()
    {
        $result = $this->execute([['a_metric_scopable', Operators::GREATER_OR_EQUAL_THAN, ['amount' => 30, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_two']);

        $result = $this->execute([['a_metric_scopable', Operators::GREATER_OR_EQUAL_THAN, ['amount' => 25, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorEmpty()
    {
        $result = $this->execute([['a_metric_scopable', Operators::IS_EMPTY, [], ['scope' => 'tablet']]]);
        $this->getResults($result, ['empty_product']);
    }

    public function testOperatorNotEmpty()
    {
        $result = $this->execute([['a_metric_scopable', Operators::IS_NOT_EMPTY, [], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_one', 'product_two']);
    }

    public function testOperatorDifferent()
    {
        $result = $this->execute([['a_metric_scopable', Operators::NOT_EQUAL, ['amount' => 30, 'unit' => 'METER'], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_one', 'product_two']);

        $result = $this->execute([['a_metric_scopable', Operators::NOT_EQUAL, ['amount' => 30, 'unit' => 'CENTIMETER'], ['scope' => 'tablet']]]);
        $this->getResults($result, ['product_one']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Attribute or field "a_metric_scopable" expects valid data, scope and locale. Attribute "a_metric_scopable" expects a scope, none given.
     */
    public function testErrorMetricScopable()
    {
        $this->execute([['a_metric_scopable', Operators::NOT_EQUAL, ['amount' => 250, 'unit' => 'KILOWATT']]]);
    }
}
