<?php

namespace spec\Pim\Component\Catalog\Updater\Remover;

use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Model\GroupInterface;
use Pim\Component\Catalog\Model\GroupTypeInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\GroupRepositoryInterface;

class GroupFieldRemoverSpec extends ObjectBehavior
{
    function let(GroupRepositoryInterface $groupRepository)
    {
        $this->beConstructedWith(
            $groupRepository,
            ['groups']
        );
    }

    function it_is_a_remover()
    {
        $this->shouldImplement('\Pim\Component\Catalog\Updater\Remover\FieldRemoverInterface');
    }

    function it_supports_groups_field()
    {
        $this->supportsField('groups')->shouldReturn(true);
        $this->supportsField('categories')->shouldReturn(false);
    }

    function it_removes_groups_field(
        $groupRepository,
        ProductInterface $product,
        GroupInterface $packGroup,
        GroupInterface $crossGroup,
        GroupTypeInterface $nonVariantType
    ) {
        $groupRepository->findOneByIdentifier('pack')->willReturn($packGroup);
        $groupRepository->findOneByIdentifier('cross')->willReturn($crossGroup);

        $packGroup->getType()->willReturn($nonVariantType);
        $crossGroup->getType()->willReturn($nonVariantType);

        $nonVariantType->isVariant()->willReturn(false);

        $product->removeGroup($packGroup)->shouldBeCalled();
        $product->removeGroup($crossGroup)->shouldBeCalled();

        $this->removeFieldData($product, 'groups', ['pack', 'cross']);
    }

    function it_fails_if_the_group_code_does_not_exist(
        $groupRepository,
        ProductInterface $product,
        GroupInterface $pack,
        GroupTypeInterface $nonVariantType
    ) {
        $groupRepository->findOneByIdentifier('pack')->willReturn($pack);
        $pack->getType()->willReturn($nonVariantType);
        $nonVariantType->isVariant()->willReturn(false);
        $groupRepository->findOneByIdentifier('not valid code')->willReturn(null);

        $this->shouldThrow(
            InvalidArgumentException::expected(
                'groups',
                'existing group code',
                'Pim\Component\Catalog\Updater\Remover\GroupFieldRemover',
                'not valid code'
            )
        )->during('removeFieldData', [$product, 'groups', ['pack', 'not valid code']]);
    }

    function it_checks_valid_data_format(ProductInterface $product)
    {
        $this->shouldThrow(
            InvalidArgumentException::arrayExpected(
                'groups',
                'Pim\Component\Catalog\Updater\Remover\GroupFieldRemover',
                'string'
            )
        )->during('removeFieldData', [$product, 'groups', 'not an array']);

        $this->shouldThrow(
            InvalidArgumentException::arrayStringValueExpected(
                'groups',
                0,
                'Pim\Component\Catalog\Updater\Remover\GroupFieldRemover',
                'array'
            )
        )->during('removeFieldData', [$product, 'groups', [['array of array']]]);
    }

    function it_fails_if_the_group_code_does_not_correspond_to_a_simple_group(
        $groupRepository,
        ProductInterface $product,
        GroupInterface $pack,
        GroupInterface $variant,
        GroupTypeInterface $nonVariantType,
        GroupTypeInterface $variantType
    ) {
        $groupRepository->findOneByIdentifier('pack')->willReturn($pack);
        $pack->getType()->willReturn($nonVariantType);
        $nonVariantType->isVariant()->willReturn(false);
        $groupRepository->findOneByIdentifier('variant')->willReturn($variant);
        $variant->getType()->willReturn($variantType);
        $variantType->isVariant()->willReturn(true);

        $this->shouldThrow(
            InvalidArgumentException::expected(
                'groups',
                'non variant group code',
                'Pim\Component\Catalog\Updater\Remover\GroupFieldRemover',
                'variant'
            )
        )->during('removeFieldData', [$product, 'groups', ['pack', 'variant']]);
    }
}
