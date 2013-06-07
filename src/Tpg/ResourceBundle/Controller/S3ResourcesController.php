<?php
namespace Tpg\ResourceBundle\Controller;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Tpg\S3UploadBundle\Entity\Multipart;
use Symfony\Component\Validator\Validator;
use Tpg\S3UploadBundle\Component\ValidationException;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\Serializer\Serializer;

class S3ResourcesController extends FOSRestController {

    protected function validate($part, $groups = [], $statusCode = 400) {
        /** @var Validator $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($part, array_merge($groups, ['Default']));
        if ($errors->count() > 0) {
            throw new ValidationException($errors, $statusCode);
        }
    }

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

    /**
     * Update a S3 resource
     *
     * @ApiDoc(
     *  description="Update a S3 resource",
     *  input="Tpg\ResourceBundle\Entity\S3Resources",
     *  output="Tpg\ResourceBundle\Entity\S3Resources",
     *  statusCodes={
     *      200="Successfully",
     *  }
     * )
     *
     * @param integer $id Multipart Upload ID
     * @param integer $count x number of next part to get
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putResourcesAction($id) {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        $resource = $em->find('Tpg\ResourceBundle\Entity\S3Resources', $id);
        if ($resource === null) {
            $view = View::create(['errors'=>'Entity not found'], 404);
        } else {
            $form = $this->createFormBuilder($resource, ['csrf_protection'=>false])
                ->add('key', 'text')
                ->add('mime_type', 'text')
                ->getForm();
            $form->submit($this->getRequest()->request->all(), false);
            $this->validate($resource, 400);
            $em->flush();
            $view = View::create($resource, 200);
        }
        return $this->handleView($view);
    }
}