<?php
namespace Tpg\ResourceBundle\Controller;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use Tpg\S3UploadBundle\Entity\Multipart;
use Symfony\Component\Validator\Validator;
use Tpg\S3UploadBundle\Component\ValidationException;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\Serializer\Serializer;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page of the list.")
     * @QueryParam(name="start", requirements="\d+", default="0", description="Page of the list.")
     * @QueryParam(name="limit", requirements="\d+", default="25", description="Number of record per fetch.")
     * @QueryParam(name="sort", description="Sort result by field in URL encoded JSON format", default="[]")
     * @QueryParam(name="filter", description="Search filter in URL encoded JSON format", default="[]")
     *
     * @ApiDoc(
     *  description="Return a collection of S3 Resources",
     *  resource=true,
     *  statusCodes={
     *      200="List Successfully",
     *  }
     * )
     *
     * @param ParamFetcherInterface $paramFetcher
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResourcesAction(ParamFetcherInterface $paramFetcher) {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        /** @var EntityRepository $repo */
        $repo = $em->getRepository('TpgResourceBundle:S3Resources');
        $rawSorters = json_decode($paramFetcher->get("sort"), true);
        $sorters = [];
        foreach ($rawSorters as $s) {
            $sorters[$s['property']] = $s['direction'];
        }
        $rawFilters = json_decode($paramFetcher->get("filter"), true);
        $filters = [];
        foreach ($rawFilters as $f) {
            $filters[$f['property']] = $f['value'];
        }
        $start = 0;
        if ($paramFetcher->get("start") === "0") {
            if ($paramFetcher->get("page") > 1) {
                $start = ($paramFetcher->get("page")-1) * $paramFetcher->get("limit");
            }
        } else {
            $start = $paramFetcher->get("start");
        }
        $view = View::create($repo->findBy(
            $filters,
            $sorters,
            $paramFetcher->get("limit"),
            $start
        ), 200)->setSerializationContext(
            SerializationContext::create()->enableMaxDepthChecks()
        );
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
            if ($this->getRequest()->request->has("tag_id")) {
                $tag = $em->find('Tpg\ResourceBundle\Entity\Tag', $this->getRequest()->request->get("tag_id"));
                if ($tag === null) {
                    $view = View::create(['errors'=>'Entity not found'], 404);
                    return $this->handleView($view);
                } else {
                    $resource->setTag($tag);
                }
            }
            $em->flush();
            $view = View::create($resource, 200);
        }
        return $this->handleView($view);
    }
}