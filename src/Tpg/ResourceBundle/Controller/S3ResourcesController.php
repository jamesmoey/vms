<?php
namespace Tpg\ResourceBundle\Controller;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Tpg\S3UploadBundle\Entity\Multipart;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class S3ResourcesController extends FOSRestController {
    /**
     * Delete an existing S3 Resource
     *
     * @ApiDoc(
     *  description="Delete a S3 Resource",
     *  output="Tpg\ResourceBundle\Entity\S3Resources",
     *  statusCodes={
     *      200="Remove Successfully",
     *  }
     * )
     *
     * @param integer $id S3 Resource ID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteResourcesAction($id) {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        $part = $em->find('TpgResourceBundle:S3Resources', $id);
        if ($part === null) {
            $view = View::create(['errors'=>'Entity not found'], 404);
        } else {
            $view = View::create($part, 200);
            $em->remove($part);
            $em->flush();
        }
        return $this->handleView($view);
    }

    /**
     * Get a list of S3 Resources
     *
     * @ApiDoc(
     *  description="Return a collection of S3 Resources",
     *  resource=true,
     *  statusCodes={
     *      200="List Successfully",
     *  }
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResourcesAction() {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        /** @var EntityRepository $repo */
        $repo = $em->getRepository('TpgResourceBundle:S3Resources');
        $view = View::create($repo->findAll(), 200);
        return $this->handleView($view);
    }
}