<?php
namespace Tpg\S3UploadBundle\Controller;

use Aws\Common\Enum\DateFormat;
use Aws\S3\S3Client;
use FOS\RestBundle\View\View;
use Guzzle\Http\Message\RequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tpg\S3UploadBundle\Service\Upload;

class UploadController extends Controller {

    /**
     * Generate a signature for this upload.
     *
     * @ApiDoc(
     *  description="Get the authorisation signature for S3 Single Upload",
     *  output="Tpg\S3UploadBundle\Model\UploadAuthorizeSignature",
     *  statusCodes={
     *      200="Created Successfully",
     *  }
     * )
     *
     * @RequestParam(name="md5", strict=true, description="MD5 hash of the upload")
     * @RequestParam(name="mime_type", strict=true, description="Mime type of the upload")
     * @RequestParam(name="key", strict=true, description="Unique identification for this upload. Should map to a path in S3 bucket.")
     *
     * @Route(path="/signature", methods="POST")
     */
    public function signatureAction() {
        /** @var Upload $service */
        $service = $this->get('tpg_s3upload.upload');
        $view = View::create($service->generateSignature(
            $this->getRequest()->request->get("mime_type"),
            $this->getRequest()->request->get("key"),
            $this->getRequest()->request->get("md5")
        ));
        return $this->get('fos_rest.view_handler')->handle($view);
    }
}