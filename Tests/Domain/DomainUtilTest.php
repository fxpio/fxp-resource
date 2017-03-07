<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Tests\Domain;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\DriverException;
use Sonatra\Component\Resource\Domain\Domain;
use Sonatra\Component\Resource\Domain\DomainUtil;
use Sonatra\Component\Resource\Exception\ConstraintViolationException;
use Sonatra\Component\Resource\ResourceEvents;
use Sonatra\Component\Resource\ResourceInterface;
use Sonatra\Component\Resource\ResourceItem;
use Sonatra\Component\Resource\ResourceList;
use Sonatra\Component\Resource\ResourceListInterface;
use Sonatra\Component\Resource\ResourceStatutes;
use Sonatra\Component\Resource\Tests\Fixtures\Exception\MockDriverException;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Tests case for Domain util.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractDriverExceptionMessage()
    {
        /* @var DriverException|\PHPUnit_Framework_MockObject_MockObject $ex */
        $ex = $this->getMockBuilder(DriverException::class)->disableOriginalConstructor()->getMock();

        $message = DomainUtil::getExceptionMessage($this->getTranslator(), $ex, false);

        $this->assertSame('Database error', $message);
    }

    public function testExtractDriverExceptionMessageInDebug()
    {
        $rootMsg = 'SQLSTATE[HY000]: General error: 1364 Field \'foo\' doesn\'t have a default value';
        $rootEx = new MockDriverException($rootMsg);
        $prevEx = new MockDriverException('Previous exception', 1, $rootEx);
        $ex = new DriverException('Exception message', $prevEx);

        $message = DomainUtil::getExceptionMessage($this->getTranslator(), $ex, true);

        $this->assertSame('Database error [Doctrine\DBAL\Exception\DriverException]: General error: 1364 Field \'foo\' doesn\'t have a default value', $message);
    }

    public function testGetIdentifier()
    {
        $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $meta->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue(array(
                'id',
            )));

        /* @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $om */
        $om = $this->getMockBuilder(ObjectManager::class)->getMock();
        $om->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->will($this->returnValue($meta));

        $object = new \stdClass();
        $object->id = 42;

        $identifier = DomainUtil::getIdentifier($om, $object);

        $this->assertSame($object->id, $identifier);
    }

    public function testGetIdentifierName()
    {
        $meta = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $meta->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue(array(
                'id',
            )));

        /* @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $om */
        $om = $this->getMockBuilder(ObjectManager::class)->getMock();
        $om->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->will($this->returnValue($meta));

        $identifierName = DomainUtil::getIdentifierName($om, \stdClass::class);

        $this->assertSame('id', $identifierName);
    }

    public function testGetEventNameCreate()
    {
        $names = DomainUtil::getEventNames(Domain::TYPE_CREATE);
        $validNames = array(ResourceEvents::PRE_CREATES, ResourceEvents::POST_CREATES);
        $this->assertSame($validNames, $names);
    }

    public function testGetEventNameUpdate()
    {
        $names = DomainUtil::getEventNames(Domain::TYPE_UPDATE);
        $validNames = array(ResourceEvents::PRE_UPDATES, ResourceEvents::POST_UPDATES);
        $this->assertSame($validNames, $names);
    }

    public function testGetEventNameUpsert()
    {
        $names = DomainUtil::getEventNames(Domain::TYPE_UPSERT);
        $validNames = array(ResourceEvents::PRE_UPSERTS, ResourceEvents::POST_UPSERTS);
        $this->assertSame($validNames, $names);
    }

    public function testGetEventNameDelete()
    {
        $names = DomainUtil::getEventNames(Domain::TYPE_DELETE);
        $validNames = array(ResourceEvents::PRE_DELETES, ResourceEvents::POST_DELETES);
        $this->assertSame($validNames, $names);
    }

    public function testGetEventNameUndelete()
    {
        $names = DomainUtil::getEventNames(Domain::TYPE_UNDELETE);
        $validNames = array(ResourceEvents::PRE_UNDELETES, ResourceEvents::POST_UNDELETES);
        $this->assertSame($validNames, $names);
    }

    public function testAddResourceError()
    {
        $errors = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();
        $errors->expects($this->once())
            ->method('add');

        /* @var ResourceInterface|\PHPUnit_Framework_MockObject_MockObject $resource */
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue($errors));

        DomainUtil::addResourceError($resource, 'Message error');
    }

    public function testExtractIdentifierInObjectList()
    {
        $identifiers = array(
            new \stdClass(),
            5,
            new \stdClass(),
        );
        $objects = array();
        $searchIds = DomainUtil::extractIdentifierInObjectList($identifiers, $objects);

        $this->assertCount(2, $objects);
        $this->assertSame($identifiers[0], $objects[0]);
        $this->assertSame($identifiers[2], $objects[1]);

        $this->assertCount(1, $searchIds);
        $this->assertSame(5, $searchIds[0]);
    }

    public function testGenerateShortName()
    {
        $this->assertSame('MockDriverException', DomainUtil::generateShortName(MockDriverException::class));
        $this->assertSame('stdClass', DomainUtil::generateShortName(\stdClass::class));
        $this->assertSame('Foo', DomainUtil::generateShortName('FooInterface'));
        $this->assertSame('Bar', DomainUtil::generateShortName('Foo\BarInterface'));
    }

    public function testInjectErrorMessage()
    {
        $res = new ResourceItem(new \stdClass());

        $this->assertSame(ResourceStatutes::PENDING, $res->getStatus());
        $this->assertCount(0, $res->getErrors());

        $ex = new \Exception('Error message');
        DomainUtil::injectErrorMessage($this->getTranslator(), $res, $ex, true);

        $this->assertSame(ResourceStatutes::ERROR, $res->getStatus());
        $this->assertCount(1, $res->getErrors());
    }

    public function testInjectErrorMessageWithConstraintViolation()
    {
        $data = new \stdClass();
        $res = new ResourceItem($data);

        $this->assertSame(ResourceStatutes::PENDING, $res->getStatus());
        $this->assertCount(0, $res->getErrors());

        $list = new ConstraintViolationList();
        $list->add(new ConstraintViolation('Violation message', 'Violation message', array(), $res->getRealData(), null, null));
        $list->add(new ConstraintViolation('Violation message 2', 'Violation message 2', array(), $res->getRealData(), null, null));
        $ex = new ConstraintViolationException($list, 'Error message');
        DomainUtil::injectErrorMessage($this->getTranslator(), $res, $ex, true);

        $this->assertSame(ResourceStatutes::ERROR, $res->getStatus());
        $this->assertCount(2, $res->getErrors());
    }

    public function testOneAction()
    {
        $errors = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();
        $errors->expects($this->once())
            ->method('addAll');

        /* @var ResourceListInterface|\PHPUnit_Framework_MockObject_MockObject $resource */
        $resource = $this->getMockBuilder(ResourceListInterface::class)->getMock();
        $resource->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue($errors));

        $listErrors = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();

        /* @var ResourceListInterface|\PHPUnit_Framework_MockObject_MockObject $resourceList */
        $resourceList = $this->getMockBuilder(ResourceListInterface::class)->getMock();
        $resourceList->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue($listErrors));

        $resourceList->expects($this->atLeast(2))
            ->method('get')
            ->with(0)
            ->will($this->returnValue($resource));

        DomainUtil::oneAction($resourceList);
    }

    public function testMoveFlushErrorsInResource()
    {
        $resources = new ResourceList();
        $errors = new ConstraintViolationList();

        $resources->add(new ResourceItem(new \stdClass()));
        $resources->add(new ResourceItem(new \stdClass()));
        $resources->add(new ResourceItem(new \stdClass()));

        $errors->add(new ConstraintViolation('Violation message global', 'Violation message global', array(), null, null, null));
        $errors->add(new ConstraintViolation('Violation message resource 1', 'Violation message resource 1', array(), $resources->get(1)->getRealData(), null, null));

        $this->assertCount(0, $resources->getErrors());
        $this->assertCount(0, $resources->get(0)->getErrors());
        $this->assertCount(0, $resources->get(1)->getErrors());
        $this->assertCount(0, $resources->get(2)->getErrors());

        DomainUtil::moveFlushErrorsInResource($resources, $errors);

        $this->assertCount(1, $resources->getErrors());
        $this->assertCount(0, $resources->get(0)->getErrors());
        $this->assertCount(1, $resources->get(1)->getErrors());
        $this->assertCount(0, $resources->get(2)->getErrors());
    }

    public function testCancelAllSuccessResources()
    {
        $resList = new ResourceList();
        $resList->add(new ResourceItem(new \stdClass()));
        $resList->add(new ResourceItem(new \stdClass()));
        $resList->add(new ResourceItem(new \stdClass()));

        $this->assertSame(ResourceStatutes::PENDING, $resList->getStatus());

        $resList->get(0)->setStatus(ResourceStatutes::ERROR);

        $this->assertSame(ResourceStatutes::ERROR, $resList->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::PENDING, $resList->get(1)->getStatus());
        $this->assertSame(ResourceStatutes::PENDING, $resList->get(2)->getStatus());

        DomainUtil::cancelAllSuccessResources($resList);

        $this->assertSame(ResourceStatutes::ERROR, $resList->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::CANCELED, $resList->get(1)->getStatus());
        $this->assertSame(ResourceStatutes::CANCELED, $resList->get(2)->getStatus());
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        $translator = new Translator('en');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $translator->addResource('xml', realpath(dirname($ref->getFileName()).'/Resources/translations/SonatraResource.en.xlf'), 'en', 'SonatraResource');
        $translator->addLoader('xml', new XliffFileLoader());

        return $translator;
    }
}
