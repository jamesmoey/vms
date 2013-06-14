<?php
namespace Tpg\ResourceBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Tpg\ResourceBundle\Entity\Tag;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class TagController extends FOSRestController {

    /**
     * Get a list of Tag
     *
     * @ApiDoc(
     *  description="Return a collection of Tag",
     *  output="Tpg\ResourceBundle\Entity\Tag",
     *  statusCodes={
     *      200="List Successfully",
     *  }
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTagsAction($tag = "root") {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        /** @var NestedTreeRepository $repo */
        $repo = $em->getRepository('TpgResourceBundle:Tag');
        $view = View::create($repo->findBy(['lvl'=>0]), 200);
        return $this->handleView($view);
    }

    /**
     * Create a new instance of Tag
     *
     * @ApiDoc(
     *  description="Create new Tag",
     *  input="Tpg\ResourceBundle\Model\TagDTO",
     *  output="Tpg\ResourceBundle\Entity\Tag",
     *  resource=true,
     *  statusCodes={
     *      200="Created Successfully"
     *  }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTagsAction() {
        $entity = new Tag();
        $entity->setName($this->getRequest()->request->get("name"));
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        if ($this->getRequest()->request->has('parent')) {
            /** @var NestedTreeRepository $repo */
            $repo = $em->getRepository('TpgResourceBundle:Tag');
            $parent = $repo->find($this->getRequest()->request->get('parent'));
            $entity->setParent($parent);
        }
        $em->persist($entity);
        $em->flush();
        $view = View::create($entity, 200);
        return $this->handleView($view);
    }

    /**
     * Update Tag
     *
     * @ApiDoc(
     *  description="Update a Tag",
     *  output="Tpg\ResourceBundle\Entity\Tag",
     *  statusCodes={
     *      200="Successfully",
     *  }
     * )
     *
     * @param integer $id Tag ID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putTagAction($id) {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');
        /** @var NestedTreeRepository $repo */
        $repo = $em->getRepository('TpgResourceBundle:Tag');
        /** @var Tag $tag */
        $tag = $repo->find($id);
        /** @var Tag $parentTag */
        $parentTag = $repo->find($this->getRequest()->request->get('parentId'));
        if ($tag->getParent() !== $parentTag) {
            $tag->setParent($parentTag);
            $em->flush($tag);
            $em->refresh($tag);
        }
        $view = View::create($tag, 200);
        return $this->handleView($view);
    }
}