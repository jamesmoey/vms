<?php

namespace Tpg\S3UploadBundle\Command;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MultipartUploadCommand extends ContainerAwareCommand {

    /** @var $s3 S3Client */
    protected $s3;
    protected $bucket;

    protected function configure()
    {
        $this->setName("s3:multipart")
            ->setDescription('Command to interface with S3 Multipart Upload')
            ->addArgument("run", InputArgument::REQUIRED, 'Valid command are ls, del')
            ->addOption("bucket", 'b', InputOption::VALUE_REQUIRED, 'S3 Bucket to operate on')
            ->addOption("older", 'o', InputOption::VALUE_REQUIRED, 'Delete older than x days initiated');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (method_exists($this, 'action'.ucfirst($input->getArgument("run")))) {
            $this->s3 = $this->getContainer()->get("platinum_pixs_aws.base")->get('s3');
            if ($input->hasOption("bucket")) {
                $this->bucket = $input->getOption("bucket");
            } else {
                $this->bucket = $this->getContainer()->getParameter("tpg_s3");
            }
            call_user_func_array(
                array($this, 'action'.ucfirst($input->getArgument("run"))),
                [$input, $output]
            );
        } else {
            $output->writeln("Command is not found.");
        }
    }

    protected function actionLs(InputInterface $input, OutputInterface $output) {
        $list = $this->s3->listMultipartUploads([
            'Bucket' => $this->bucket
        ]);
        foreach($list->get("Uploads") as $upload) {
            $output->writeln($upload['UploadId']."\t".$upload['Key']."\t".$upload['Initiated']);
        }
    }

    protected function actionDel(InputInterface $input, OutputInterface $output) {
        if (!$input->hasOption("older")) {
            $output->writeln("You need to specify older option when using del command");
            return false;
        }
        $older = new \DateTime('-'.$input->getOption("older").' days');
        $list = $this->s3->listMultipartUploads([
            'Bucket' => $this->bucket
        ]);
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get("doctrine.orm.entity_manager")->getRepository("TpgS3UploadBundle:Multipart");
        foreach($list->get("Uploads") as $upload) {
            $date = new \DateTime($upload['Initiated']);
            if ($date < $older && $repository->findOneBy(['uploadId'=>$upload['UploadId']]) === null) {
                $this->s3->abortMultipartUpload([
                    'Bucket' => $this->bucket,
                    'Key' => $upload['Key'],
                    'UploadId' => $upload['UploadId']
                ]);
                $output->writeln("Removing Multipart Upload ID: " . $upload['UploadId']);
            }
        }
    }
}