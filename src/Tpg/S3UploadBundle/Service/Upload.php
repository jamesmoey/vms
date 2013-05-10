<?php
namespace Tpg\S3UploadBundle\Service;

use Aws\Common\Enum\DateFormat;
use Aws\S3\S3Client;
use Aws\S3\S3SignatureInterface;
use Guzzle\Http\Message\RequestInterface;
use Monolog\Logger;
use Tpg\S3UploadBundle\Model\UploadAuthorizeSignature;

class Upload {
    /**
     * @var S3Client $s3
     */
    protected $s3;

    protected $bucket;

    /** @var  Logger $logger */
    protected $logger;

    public function __construct($s3, $bucket, $logger) {
        $this->s3 = $s3;
        $this->bucket = $bucket;
        $this->logger = $logger;
    }

    /**
     * Generate authorisation signature for S3 upload.
     *
     * @param string $mimeType
     * @param string $key
     * @param string $md5
     *
     * @return UploadAuthorizeSignature
     */
    public function generateSignature($mimeType, $key, $md5) {
        $now = new \DateTime('now', new \DateTimeZone('GMT'));
        $headers = [
            'Content-Type' => $mimeType,
            'x-amz-date' => $now->format(DateFormat::RFC2822),
            'Content-MD5' => $md5,
        ];
        $request = $this->s3->createRequest(
            RequestInterface::PUT,
            '/'.$this->bucket.'/'.urlencode($key),
            $headers
        );
        /** @var S3SignatureInterface $signature */
        $signature = $this->s3->getSignature();
        $authorization = 'AWS ' .
            $this->s3->getCredentials()->getAccessKeyId() . ':' .
            $signature->signString(
                $signature->createCanonicalizedString($request),
                $this->s3->getCredentials()
            );
        $output = new UploadAuthorizeSignature();
        $output->setAuthorisation($authorization)
            ->setKey($key)
            ->setBucket($this->bucket)
            ->setDate($now);
        return $output;
    }
}