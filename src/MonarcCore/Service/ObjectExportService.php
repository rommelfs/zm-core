<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Object Service Export
 *
 * Class ObjectExportService
 * @package MonarcCore\Service
 */
class ObjectExportService extends AbstractService
{
    protected $assetExportService;
    protected $objectObjectService;
    protected $categoryTable;
    protected $assetService;
    protected $anrObjectCategoryTable;
    protected $rolfTagTable;
    protected $rolfRiskTable;

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
            throw new \Exception('Object to export is required', 412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $objectObj = [
            'id' => 'id',
            'mode' => 'mode',
            'scope' => 'scope',
            'name1' => 'name1',
            'name2' => 'name2',
            'name3' => 'name3',
            'name4' => 'name4',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'disponibility' => 'disponibility',
        ];
        $return = [
            'type' => 'object',
            'object' => $entity->getJsonArray($objectObj),
            'version' => $this->getVersion(),
        ];
        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('name' . $this->getLanguage()));

        // Récupération catégories
        $categ = $entity->get('category');
        if (!empty($categ)) {
            $categObj = [
                'id' => 'id',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
            ];

            while (!empty($categ)) {
                $categFormat = $categ->getJsonArray($categObj);
                if (empty($return['object']['category'])) {
                    $return['object']['category'] = $categFormat['id'];
                }
                $return['categories'][$categFormat['id']] = $categFormat;
                $return['categories'][$categFormat['id']]['parent'] = null;

                $parent = $categ->get('parent');
                if (!empty($parent)) {
                    $parentForm = $categ->get('parent')->getJsonArray(['id' => 'id']);
                    $return['categories'][$categFormat['id']]['parent'] = $parentForm['id'];
                    $categ = $parent;
                } else {
                    $categ = null;
                }
            }
        } else {
            $return['object']['category'] = null;
            $return['categories'] = null;
        }

        // Récupération asset
        $asset = $entity->get('asset');
        $return['asset'] = null;
        $return['object']['asset'] = null;
        if (!empty($asset)) {
            $asset = $asset->getJsonArray(['id']);
            $return['object']['asset'] = $asset['id'];
            $return['asset'] = $this->get('assetExportService')->generateExportArray($asset['id']);
        }

        // Récupération des risques opérationnels
        $rolfTag = $entity->get('rolfTag');
        $return['object']['rolfTag'] = null;
        if (!empty($rolfTag)) {
            $risks = $rolfTag->get('risks');
            $rolfTag = $rolfTag->getJsonArray(['id', 'code', 'label1', 'label2', 'label3', 'label4']);
            $return['object']['rolfTag'] = $rolfTag['id'];
            $return['rolfTags'][$rolfTag['id']] = $rolfTag;
            $return['rolfTags'][$rolfTag['id']]['risks'] = [];
            if (!empty($risks)) {
                foreach ($risks as $r) {
                    $r = $r->getJsonArray(['id', 'code', 'label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4']);
                    $return['rolfTags'][$rolfTag['id']]['risks'][$r['id']] = $r['id'];
                    $return['rolfRisks'][$r['id']] = $r;
                }
            }
        }

        // Récupération children(s)
        $children = array_reverse($this->get('objectObjectService')->getChildren($entity->get('id'))); // Le tri de cette fonction est "position DESC"
        $return['children'] = null;
        if (!empty($children)) {
            $return['children'] = [];
            foreach ($children as $child) {
                $return['children'][$child->get('child')->get('id')] = $this->generateExportArray($child->get('child')->get('id'));
            }
        }

        return $return;
    }

    /**
     * Import From Array
     *
     * @param $data
     * @param $anr
     * @param string $modeImport
     * @param array $objectsCache
     * @return bool
     */
    public function importFromArray($data, $anr, $modeImport = 'merge', &$objectsCache = [])
    {
        if (isset($data['type']) && $data['type'] == 'object' &&
            array_key_exists('version', $data) && $data['version'] == $this->getVersion()
        ) {

            if (isset($data['object']['name' . $this->getLanguage()]) && isset($objectsCache['objects'][$data['object']['name' . $this->getLanguage()]])) {
                return $objectsCache['objects'][$data['object']['name' . $this->getLanguage()]];
            }
            // import asset
            $assetId = $this->get('assetService')->importFromArray($data['asset'], $anr, $objectsCache);

            if ($assetId) {
                // import categories
                $idCateg = $this->importFromArrayCategories($data['categories'], $data['object']['category'], $anr->get('id'));

                // Import des RisksOp
                if (!empty($data['object']['rolfTag']) && !empty($data['rolfTags'][$data['object']['rolfTag']])) {
                    $tag = current($this->get('rolfTagTable')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'code' => $data['rolfTags'][$data['object']['rolfTag']]['code'],
                    ]));
                    if (empty($tag)) {
                        $ct = $this->get('rolfTagTable')->getClass();
                        $tag = new $ct();
                    }
                    $tag->setDbAdapter($this->get('rolfTagTable')->getDb());
                    $tag->setLanguage($this->getLanguage());

                    if (!empty($data['rolfTags'][$data['object']['rolfTag']]['risks'])) {
                        $risks = [];
                        foreach ($data['rolfTags'][$data['object']['rolfTag']]['risks'] as $k) {
                            if (isset($data['rolfRisks'][$k])) {
                                $risk = current($this->get('rolfRiskTable')->getEntityByFields([
                                    'anr' => $anr->get('id'),
                                    'code' => $data['rolfRisks'][$k]['code'],
                                ]));
                                if (empty($risk)) {
                                    $cr = $this->get('rolfRiskTable')->getClass();
                                    $risk = new $cr();
                                }
                                $risk->setDbAdapter($this->get('rolfRiskTable')->getDb());
                                $risk->setLanguage($this->getLanguage());
                                $toExchange = $data['rolfRisks'][$k];
                                unset($toExchange['id']);
                                $toExchange['anr'] = $anr->get('id');
                                $risk->exchangeArray($toExchange);
                                $this->setDependencies($risk, ['anr']);
                                $risks[] = $this->get('rolfRiskTable')->save($risk);
                            }
                        }
                        $data['rolfTags'][$data['object']['rolfTag']]['risks'] = $risks;
                    }

                    $toExchange = $data['rolfTags'][$data['object']['rolfTag']];
                    unset($toExchange['id']);
                    $toExchange['anr'] = $anr->get('id');
                    $tag->exchangeArray($toExchange);
                    $this->setDependencies($tag, ['anr', 'risks']);
                    $data['object']['rolfTag'] = $this->get('rolfTagTable')->save($tag);
                } else {
                    $data['object']['rolfTag'] = null;
                }

                /*
                 * INFO:
                 * Selon le mode d'import, la contruction de l'objet ne sera pas la même
                 * Seul un objet SCOPE_GLOBAL (scope) pourra être dupliqué par défaut
                 * Sinon c'est automatiquement un test de fusion, en cas d'échec de fusion on part sur une "duplication" (création)
                 */
                if ($data['object']['scope'] == \MonarcCore\Model\Entity\ObjectSuperClass::SCOPE_GLOBAL &&
                    $modeImport == 'duplicate'
                ) {
                    // Cela sera traité après le "else"
                } else { // Fuusion
                    /*
                     * Le pivot pour savoir si on peut faire le merge est:
                     * 1. Même nom
                     * 2. Même catégorie
                     * 3. Même type d'actif
                     * 4. Même scope
                     */
                    $object = current($this->get('table')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'name' . $this->getLanguage() => $data['object']['name' . $this->getLanguage()],
                        // il faut que le scope soit le même sinon souci potentiel sur l'impact des valeurs dans les instances (ex : on passe de local à global, toutes les instances choperaient la valeur globale)
                        'scope' => $data['object']['scope'],
                        // il faut bien sûr que le type d'actif soit identique sinon on mergerait des torchons et des serviettes, ça donne des torchettes et c'est pas cool
                        'asset' => $assetId,
                        'category' => $idCateg
                    ]));
                    if (!empty($object)) {
                        $object->setDbAdapter($this->get('table')->getDb());
                        $object->setLanguage($this->getLanguage());
                    }
                    // Si il existe, c'est bien, on ne fera pas de "new"
                    // Sinon, on passera dans la création d'un nouvel "object"
                }

                $toExchange = $data['object'];
                if (empty($object)) {
                    $class = $this->get('table')->getClass();
                    $object = new $class();
                    $object->setDbAdapter($this->get('table')->getDb());
                    $object->setLanguage($this->getLanguage());
                    // Si on passe ici, c'est qu'on est en mode "duplication", il faut donc vérifier qu'on n'est pas plusieurs fois le même "name"
                    $suffixe = 0;
                    $current = current($this->get('table')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'name' . $this->getLanguage() => $toExchange['name' . $this->getLanguage()]
                    ]));
                    while (!empty($current)) {
                        $suffixe++;
                        $current = current($this->get('table')->getEntityByFields([
                            'anr' => $anr->get('id'),
                            'name' . $this->getLanguage() => $toExchange['name' . $this->getLanguage()] . ' - Imp. #' . $suffixe
                        ]));
                    }
                    if ($suffixe > 0) { // sinon inutile de modifier le nom, on garde celui de la source
                        for ($i = 1; $i <= 4; $i++) {
                            if (!empty($toExchange['name' . $i])) { // on ne modifie que pour les langues renseignées
                                $toExchange['name' . $i] .= ' - Imp. #' . $suffixe;
                            }
                        }
                    }
                } else {
                    // Si l'objet existe déjà, on risque de lui recréer des fils qu'il a déjà, dans ce cas faut détacher tous ses fils avant de lui re-rattacher (après import)
                    $links = $this->get('objectObjectService')->get('table')->getEntityByFields([
                        'anr' => $anr->get('id'),
                        'father' => $object->get('id')
                    ], ['position' => 'DESC']);
                    foreach ($links as $l) {
                        if (!empty($l)) {
                            $this->get('objectObjectService')->get('table')->delete($l->get('id'));
                        }
                    }
                }
                unset($toExchange['id']);
                $toExchange['anr'] = $anr->get('id');
                $toExchange['asset'] = $assetId;
                $toExchange['category'] = $idCateg;
                $object->exchangeArray($toExchange);
                $this->setDependencies($object, ['anr', 'category', 'asset', 'rolfTag']);
                $object->addAnr($anr);
                $idObj = $this->get('table')->save($object);

                $objectsCache['objects'][$data['object']['name' . $this->getLanguage()]] = $idObj;

                //on s'occupe des enfants
                if (!empty($data['children'])) {
                    foreach ($data['children'] as $c) {
                        $child = $this->importFromArray($c, $anr, $modeImport, $objectsCache);

                        if ($child) {
                            $class = $this->get('objectObjectService')->get('table')->getClass();
                            $oo = new $class();
                            $oo->setDbAdapter($this->get('objectObjectService')->get('table')->getDb());
                            $oo->setLanguage($this->getLanguage());
                            $oo->exchangeArray([
                                'anr' => $anr->get('id'),
                                'father' => $idObj,
                                'child' => $child,
                                'implicitPosition' => 2
                            ]);
                            $this->setDependencies($oo, ['father', 'child', 'anr']);
                            $this->get('objectObjectService')->get('table')->save($oo);
                        }
                    }
                }
                return $idObj;
            }
        }
        return false;
    }

    /**
     * Import From Array Categories
     *
     * @param $data
     * @param $idCateg
     * @param $anrId
     * @return null
     */
    protected function importFromArrayCategories($data, $idCateg, $anrId)
    {
        $return = null;
        if (!empty($data[$idCateg])) {
            // On commence par le parent
            $idParent = $this->importFromArrayCategories($data, $data[$idCateg]['parent'], $anrId);

            $categ = current($this->get('categoryTable')->getEntityByFields([
                'anr' => $anrId,
                'parent' => $idParent,
                'label' . $this->getLanguage() => $data[$idCateg]['label' . $this->getLanguage()]
            ]));
            $checkLink = null;
            if (empty($categ)) { // on crée une nouvelle catégorie
                $class = $this->get('categoryTable')->getClass();
                $categ = new $class();
                $categ->setDbAdapter($this->get('categoryTable')->getDb());
                $categ->setLanguage($this->getLanguage());

                $toExchange = $data[$idCateg];
                unset($toExchange['id']);
                $toExchange['anr'] = $anrId;
                $toExchange['parent'] = $idParent;
                $toExchange['implicitPosition'] = 2;
                // le "exchangeArray" permet de gérer la position de façon automatique & de mettre à jour le "root"
                $categ->exchangeArray($toExchange);
                $this->setDependencies($categ, ['anr', 'parent']);

                $return = $this->get('categoryTable')->save($categ);
                if (empty($idParent)) {
                    $checkLink = $return;
                }
            } else { // sinon on utilise l'éxistant
                if (empty($categ->get('parent'))) {
                    $checkLink = $categ->get('id');
                }
                $return = $categ->get('id');
            }

            if (!empty($checkLink)) {
                $link = current($this->get('anrObjectCategoryTable')->getEntityByFields([
                    'anr' => $anrId,
                    'category' => $checkLink,
                ]));
                if (empty($link)) {
                    $class = $this->get('anrObjectCategoryTable')->getClass();
                    $link = new $class();
                    $link->setDbAdapter($this->get('anrObjectCategoryTable')->getDb());
                    $link->setLanguage($this->getLanguage());
                    $link->exchangeArray([
                        'anr' => $anrId,
                        'category' => $checkLink,
                        'implicitPosition' => 2
                    ]);
                    $this->setDependencies($link, ['category', 'anr']);
                    $this->get('anrObjectCategoryTable')->save($link);
                }
            }
        }
        return $return;
    }
}