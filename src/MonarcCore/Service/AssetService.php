<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Table\AnrTable;

/**
 * Asset Service
 *
 * Class AssetService
 * @package MonarcCore\Service
 */
class AssetService extends AbstractService
{
    protected $anrTable;
    protected $modelTable;
    protected $amvService;
    protected $modelService;
    protected $objectTable;
    protected $objectObjectTable;
    protected $assetExportService;
    protected $dependencies = ['anr', 'model[s]()'];
    protected $forbiddenFields = ['anr'];
    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true)
    {

        $entity = $this->get('entity');
        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \Exception('This risk analysis does not exist', 412);
            }
            $entity->setAnr($anr);
        }
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $entity->status = 1;

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (($entity->mode == Asset::MODE_SPECIFIC) && ($data['mode'] == Asset::MODE_GENERIC)) {
            //delete models
            unset($data['models']);
        }

        $models = isset($data['models']) ? $data['models'] : [];
        $follow = isset($data['follow']) ? $data['follow'] : null;
        unset($data['models']);
        unset($data['follow']);

        $entity->exchangeArray($data);
        if ($entity->get('models')) {
            $entity->get('models')->initialize();
        }

        /** @var AmvService $amvService */
        $amvService = $this->get('amvService');
        if (!$amvService->checkAMVIntegrityLevel($models, $entity, null, null, $follow)) {
            throw new \Exception('Integrity AMV links violation', 412);
        }

        if ($entity->mode == Asset::MODE_SPECIFIC) {
            $associateObjects = $this->get('objectTable')->getGenericByAssetId($entity->getId());
            if (count($associateObjects)) {
                throw new \Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$amvService->checkModelsInstantiation($entity, $models)) {
            throw new \Exception('This type of asset is used in a model that is no longer part of the list', 412);
        }

        switch ($entity->get('mode')) {
            case Asset::MODE_SPECIFIC:
                if (empty($models)) {
                    $entity->set('models', []);
                } else {
                    $modelsObj = [];
                    foreach ($models as $mid) {
                        $modelsObj[] = $this->get('modelTable')->getEntity($mid);
                    }
                    $entity->set('models', $modelsObj);
                }
                if ($follow) {
                    $amvService->enforceAMVtoFollow($entity->get('models'), $entity, null, null);
                }
                break;
            case Asset::MODE_GENERIC:
                $entity->set('models', []);
                break;
        }

        $objects = $this->get('objectTable')->getEntityByFields(['asset' => $entity->get('id')]);
        if (!empty($objects)) {
            $oids = [];
            foreach ($objects as $o) {
                $oids[$o->id] = $o->id;
            }
            if (!empty($entity->models)) {
                //We need to check if the asset is compliant with reg/spec model when they are used as fathers
                //not already used in models
                $olinks = $this->get('objectObjectTable')->getEntityByFields(['father' => $oids]);
                if (!empty($olinks)) {
                    foreach ($olinks as $ol) {
                        foreach ($entity->models as $m) {
                            $this->get('modelTable')->canAcceptObject($m->id, $ol->child);
                        }
                    }
                }
            }
            //We need to check if the asset is compliant with reg/spec model when they are used as children
            //of objects not already used in models. This code is pretty similar to the previous one

            //we need the parents of theses objects
            $olinks = $this->get('objectObjectTable')->getEntityByFields(['child' => $oids]);
            if (!empty($olinks)) {
                foreach ($olinks as $ol) {
                    if (!empty($ol->father->asset->models)) {
                        foreach ($ol->father->asset->models as $m) {
                            $this->get('modelTable')->canAcceptObject($m->id, $ol->child, null, $entity);
                        }
                    }
                }
            }
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }

    /**
     * Export Asset
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function exportAsset(&$data)
    {
        if (empty($data['id'])) {
            throw new \Exception('Asset to export is required', 412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }
        $filename = "";
        $return = $this->get('assetExportService')->generateExportArray($data['id'], $filename);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return), $data['password']));
    }

    /**
     * Generate Export Array
     *
     * @param $id
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    public function generateExportArray($id, &$filename = "")
    {
        if (empty($id)) {
            throw new \Exception('Asset to export is required', 412);
        }

        $entity = $this->get('table')->getEntity($id);
        if (empty($entity)) {
            throw new \Exception('Asset not found', 412);
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('code'));

        $assetObj = [
            'id' => 'id',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
            'mode' => 'mode',
            'type' => 'type',
            'code' => 'code',
        ];
        $return = [
            'type' => 'asset',
            'asset' => $entity->getJsonArray($assetObj),
            'version' => $this->getVersion(),
        ];
        $amvService = $this->get('amvService');
        $amvTable = $amvService->get('table');

        $amvResults = $amvTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.asset = :asset")
            ->setParameter(':asset', $entity->get('id'));
        $anrId = $entity->get('anr');
        if (empty($anrId)) {
            $amvResults = $amvResults->andWhere('t.anr IS NULL');
        } else {
            $anrId = $anrId->get('id');
            $amvResults = $amvResults->andWhere('t.anr = :anr')->setParameter(':anr', $anrId);
        }
        $amvResults = $amvResults->getQuery()->getResult();

        $data_amvs = $data_threats = $data_vuls = $data_themes = $t_ids = $v_ids = $m_ids = $tt_ids = $threats = $vuls = $themes = [];

        $amvObj = [
            'id' => 'v',
            'threat' => 'o',
            'vulnerability' => 'o',
            'measure1' => 'o',
            'measure2' => 'o',
            'measure3' => 'o',
            'status' => 'v',
        ];
        $treatsObj = [
            'id' => 'id',
            'theme' => 'theme',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'c' => 'c',
            'i' => 'i',
            'd' => 'd',
            'status' => 'status',
            'isAccidental' => 'isAccidental',
            'isDeliberate' => 'isDeliberate',
            'descAccidental1' => 'descAccidental1',
            'descAccidental2' => 'descAccidental2',
            'descAccidental3' => 'descAccidental3',
            'descAccidental4' => 'descAccidental4',
            'exAccidental1' => 'exAccidental1',
            'exAccidental2' => 'exAccidental2',
            'exAccidental3' => 'exAccidental3',
            'exAccidental4' => 'exAccidental4',
            'descDeliberate1' => 'descDeliberate1',
            'descDeliberate2' => 'descDeliberate2',
            'descDeliberate3' => 'descDeliberate3',
            'descDeliberate4' => 'descDeliberate4',
            'exDeliberate1' => 'exDeliberate1',
            'exDeliberate2' => 'exDeliberate2',
            'exDeliberate3' => 'exDeliberate3',
            'exDeliberate4' => 'exDeliberate4',
            'typeConsequences1' => 'typeConsequences1',
            'typeConsequences2' => 'typeConsequences2',
            'typeConsequences3' => 'typeConsequences3',
            'typeConsequences4' => 'typeConsequences4',
            'trend' => 'trend',
            'comment' => 'comment',
            'qualification' => 'qualification',
        ];
        $vulsObj = [
            'id' => 'id',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
        ];
        $themesObj = [
            'id' => 'id',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];
        $measuresObj = [
            'id' => 'id',
            'code' => 'code',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
        ];

        foreach ($amvResults as $amv) {
            $data_amvs[$amv->get('id')] = [];
            foreach ($amvObj as $k => $v) {
                switch ($v) {
                    case 'v':
                        $data_amvs[$amv->get('id')][$k] = $amv->get($k);
                        break;
                    case 'o':
                        $o = $amv->get($k);
                        if (empty($o)) {
                            $data_amvs[$amv->get('id')][$k] = null;
                        } else {
                            $o = $amv->get($k)->getJsonArray();
                            $data_amvs[$amv->get('id')][$k] = $o['id'];

                            switch ($k) {
                                case 'threat':
                                    $return['threats'][$o['id']] = $amv->get($k)->getJsonArray($treatsObj);
                                    if (!empty($return['threats'][$o['id']]['theme'])) {
                                        $return['threats'][$o['id']]['theme'] = $return['threats'][$o['id']]['theme']->getJsonArray($themesObj);

                                        $return['themes'][$return['threats'][$o['id']]['theme']['id']] = $return['threats'][$o['id']]['theme'];

                                        $return['threats'][$o['id']]['theme'] = $return['threats'][$o['id']]['theme']['id'];
                                    }
                                    break;
                                case 'vulnerability':
                                    $return['vuls'][$o['id']] = $amv->get($k)->getJsonArray($vulsObj);
                                    break;
                                case 'measure1':
                                case 'measure2':
                                case 'measure3':
                                    $return['measures'][$o['id']] = $amv->get($k)->getJsonArray($measuresObj);
                                    break;
                            }
                        }
                        break;
                }
            }
        }

        $return['amvs'] = $data_amvs;
        return $return;
    }
}