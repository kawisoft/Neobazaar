<?php
 
namespace NeobazaarTest\Entity\Repository;
 
use PHPUnit_Framework_TestCase;
use Neobazaar\Entity\Repository\DocumentRepository;
use NeobazaarTest\Bootstrap;
use NeobazaarTest\Fixture\DocumentSample;

class DocumentRepositoryTest 
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\Common\DataFixtures\Executor\AbstractExecutor
     */
    protected $fixtureExectutor;

    /**
     * @var DocumentRepository
     */
    protected $repository;

    public function setUp()
    {
        $sm = Bootstrap::getServiceManager();
        $this->repository = $sm->get('Neobazaar\Entity\Repository\DocumentRepository');
        $this->repository->setServiceLocator($sm);
        
        // SphinxClient Mock
        $sphinxClient = $this->getMock('SphinxClient', array('getLastSearchCount'));
        $sphinxClient->expects($this->any())
            ->method('getLastSearchCount')
            ->will($this->returnValue(10));
        
        $this->repository->setSphinxClient($sphinxClient);
        $this->fixtureExectutor = $sm->get('Doctrine\Common\DataFixtures\Executor\AbstractExecutor');
        $this->assertInstanceOf('Neobazaar\Entity\Repository\DocumentRepository', $this->repository);

        $documentSample = new DocumentSample();
        $this->fixtureExectutor->execute(array($documentSample));
    }

    /**
     * @cover Neobazaar\Entity\Repository\DocumentRepository::getPaginator()
     */
    public function testGetPaginator()
    {
        $paginator = $this->repository->getPaginator(array(
            'limit' => 10,
            'offset' => 0,
            'returnSelect' => true
        ));

        $this->assertTrue(1 === $paginator->count());
    }

    /**
     * @cover Neobazaar\Entity\Repository\DocumentRepository::getPaginator()
     */
    public function testGetPaginatoFieldAccount() 
    {
        $paginator = $this->repository->getPaginator(array(
            'limit' => 10,
            'offset' => 0,
            'field' => array(
                'account'
            ),
            'value' => array(
                1
            ),
            'returnSelect' => true
        ));

        $this->assertTrue(0 === $paginator->count());
    }

    /**
     * @cover Neobazaar\Entity\Repository\DocumentRepository::getExpiredElegibleToMailSend()
     */
    public function testGetExpiredElegibleToMailSend() 
    {
        $paginator = $this->repository->getExpiredElegibleToMailSend();
       
        $this->assertEquals(0, $paginator->count());
    }

    /**
     * @cover Neobazaar\Entity\Repository\DocumentRepository::getList()
     */
    public function testGetList() 
    {
        $sm = Bootstrap::getServiceManager();
        
        // Mock 'neobazaar.service.hashid' service
        $mock = $this->getMock('Neobazaar\Service\HashId', array('encrypt', 'decrypt'));
        $mock->expects($this->any())
        ->method('encrypt')
        ->will($this->returnValue('xyzabc'));
        $mock->expects($this->any())
        ->method('decrypt')
        ->will($this->returnValue('xyzabc'));
        $sm->setService('neobazaar.service.hashid', $mock);
        
        $mock = $this->getMockBuilder('Zend\Cache\Storage\Adapter\Apc', array('getEncryptedId'))
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('getEncryptedId')
            ->will($this->returnValue('nothing'));
        $sm->setService('ClassifiedCache', $mock);
        
        // Mock 'Document\Model\Document' service
        $mock = $this->getMock('Document\Model\Classified\PublicListing', array('setServiceManager', 'setUserEntity', 'init'));
        $mock->expects($this->any())
            ->method('setServiceManager')
            ->will($this->returnSelf());
        $mock->expects($this->any())
            ->method('setUserEntity')
            ->will($this->returnSelf());
        $mock->expects($this->any())
            ->method('init')
            ->will($this->returnSelf());
        $sm->setService('document.model.classifiedPublicListing', $mock);
        
        $list = $this->repository->getList(array(
            'returnSelect' => true
        ), $sm);
        $data = $list['data'];
        $paginationData = $list['paginationData'];
        
        $this->assertEquals(1, count($data));
        $this->assertEquals('In visualizzazione record 1/1 di 10', $paginationData->message);
        $this->assertEquals('disabled', $paginationData->next->class);
        $this->assertEquals('disabled', $paginationData->previous->class);
        $this->assertEquals(1, count($paginationData->pages));
    }
}
 