<?php

namespace MonarcCore\Controller;

use MonarcCore\Service\ScaleImpactTypeService;
use Zend\View\Model\JsonModel;

class ApiScalesTypesController extends AbstractController
{
    protected $dependencies = [];
    protected $name = 'types';

    /**
     * Get List
     *
     * @return JsonModel
     */
    public function getList()
    {
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $anrId = (int) $this->params()->fromRoute('anrId');

        /** @var ScaleImpactTypeService $service */
        $service = $this->getService();
        $types = $service->getList(0, 0, $order, $filter, ['anr' => $anrId]);

        return new JsonModel(array(
            'count' => count($types),
            $this->name => $types
        ));
    }


    /**
     * Patch
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function patch($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');

        $data['anr'] = $anrId;

        /** @var ScaleImpactTypeService $service */
        $service = $this->getService();
        $service->patch($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }
}

