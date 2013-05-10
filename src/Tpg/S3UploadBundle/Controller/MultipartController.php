<?php
namespace Tpg\S3UploadBundle\Controller;

use Aws\S3\Enum\CannedAcl;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use JMS\Serializer\Serializer;
use Symfony\Component\Validator\Validator;
use Tpg\S3UploadBundle\Component\ValidationException;
use Tpg\S3UploadBundle\Entity\Multipart;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class MultipartController extends FOSRestController {

    protected function validate($part, $groups = [], $statusCode = 400) {
        /** @var Validator $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($part, array_merge($groups, ['Default']));
        if ($errors->count() > 0) {
            throw new ValidationException($errors, $statusCode);
        }
    }

    /**
     * Create a new instance of Multipart S3 Upload
     *
     * @ApiDoc(
     *  description="Create new S3 Multipart Upload",
     *  input="Tpg\S3UploadBundle\Entity\Multipart",
     *  output="Tpg\S3UploadBundle\Entity\Multipart",
     *  resource=true,
     *  statusCodes={
     *      200="Created Successfully",
     *      400={
     *          "Returned when pre-create validation failed, for detail check the errors json parameter in body"
     *      },
     *      409={
     *          "Returned when resource already exist"
     *      }
     *  }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postMultipartsAction() {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        /** @var Multipart $part */
        $part = $serializer->deserialize(
            json_encode($this->getRequest()->request->all()),
            'Tpg\S3UploadBundle\Entity\Multipart',
            'json'
        );
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        /** @var Multipart $existingPart */
        $existingPart = $em->getRepository('Tpg\S3UploadBundle\Entity\Multipart')->findOneBy(array(
            'bucket'=>$this->container->getParameter("tpg_s3"),
            'key'=>$part->getKey()
        ));
        if ($existingPart !== null) {
            if ($existingPart->getStatus() == Multipart::STARTED || $existingPart->getStatus() == Multipart::IN_PROGRESS) {
                return $this->handleView(View::create($existingPart, 200));
            } else {
                return $this->handleView(View::create(['errors'=>$part->getKey().' already exist', 'part'=>$existingPart], 409));
            }
        }
        try {
            $this->validate($part, ['precreate']);
            $model = $this->get("tpg_s3upload.multipart")->initialiseUpload($part);
            $this->validate($part, ['create']);
            $em->persist($part);
            $em->flush();
            $view = View::create($part, 200);
        } catch (ValidationException $e) {
            $view = View::create(['errors'=>$e->getErrors()], $e->getCode());
        }
        return $this->handleView($view);
    }

    /**
     * Delete an existing Multipart
     *
     * @ApiDoc(
     *  description="Delete a S3 Multipart Upload",
     *  output="Tpg\S3UploadBundle\Entity\Multipart",
     *  statusCodes={
     *      200="Remove Successfully",
     *  }
     * )
     *
     * @param integer $id
     */
    public function deleteMultipartAction($id) {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        $part = $em->find('Tpg\S3UploadBundle\Entity\Multipart', $id);
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
     * Get a list of multipart
     *
     * @ApiDoc(
     *  description="Return a collection of S3 Multipart Upload",
     *  statusCodes={
     *      200="List Successfully",
     *  }
     * )
     */
    public function getMultipartsAction() {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        /** @var EntityRepository $repo */
        $repo = $em->getRepository('Tpg\S3UploadBundle\Entity\Multipart');
        $view = View::create($repo->findAll(), 200);
        return $this->handleView($view);
    }

    /**
     * Generate the signature of the incomplete part of S3 Multipart Upload.
     *
     * @ApiDoc(
     *  description="Progress to the next part of S3 Multipart Upload",
     *  statusCodes={
     *      200="Successfully",
     *  }
     * )
     */
    public function putMultipartNextAction($id, $count = 1) {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        $part = $em->find('Tpg\S3UploadBundle\Entity\Multipart', $id);
        if ($part === null) {
            $view = View::create(['errors'=>'Entity not found'], 404);
        } else {
            $md5 = $this->getRequest()->request->has("md5")?$this->getRequest()->request->get("md5"):null;
            $signature = $this->get("tpg_s3upload.multipart")->getUploadSignature(
                $part,
                $part->getIncompletePart($count),
                new \DateTime("+1 hour", new \DateTimeZone('GMT')),
                $md5
            );
            $view = View::create($signature, 200);
        }
        return $this->handleView($view);
    }

    /**
     * Finish part of S3 Multipart upload.
     *
     * @ApiDoc(
     *  description="Finish part of S3 Multipart upload",
     *  output="Tpg\S3UploadBundle\Entity\Multipart",
     *  statusCodes={
     *      200="Saved Successfully",
     *      400="General client input error",
     *      404="Upload entity is not found"
     *  }
     * )
     */
    public function putMultipartCompleteAction($id, $part) {
        $etag = $this->getRequest()->request->get("etag");
        if (!$etag) {
            $view = View::create(['errors'=>'Missing etag in the request body'], 400);
        } else {
            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.default_entity_manager');
            /** @var Multipart $multipart */
            $multipart = $em->find('Tpg\S3UploadBundle\Entity\Multipart', $id);
            if ($multipart === null) {
                $view = View::create(['errors'=>'Entity not found'], 404);
            } else {
                $this->get("tpg_s3upload.multipart")->completePartial($multipart, $part, $etag);
                $em->flush();
                $view = View::create($multipart, 200);
            }
        }
        return $this->handleView($view);
    }
}