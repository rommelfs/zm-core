<?php
namespace MonarcCore\Model\Table;

class ObjectCategoryTable extends AbstractEntityTable {

    /**
     * Change positions by parent
     *
     * @param $parentId
     * @param $position
     * @param string $direction
     * @param string $referential
     * @param bool $strict
     * @return array
     */
    public function changePositionsByParent($parentId, $position, $direction = 'up', $referential = 'after', $strict = false)
    {
        $positionDirection = ($direction == 'up') ? '+1' : '-1';
        $sign = ($referential == 'after') ? '>' : '<';
        if (!$strict) {
            $sign .= '=';
        }
        if (is_null($parentId)) {
            return $this->getRepository()->createQueryBuilder('t')
                ->update()
                ->set('t.position', 't.position' . $positionDirection)
                ->where('t.parent IS NULL')
                ->andWhere('t.position ' . $sign . ' :position')
                ->setParameter(':position', $position)
                ->getQuery()
                ->getResult();
        } else {
            return $this->getRepository()->createQueryBuilder('t')
                ->update()
                ->set('t.position', 't.position' . $positionDirection)
                ->where('t.parent = :parentid')
                ->andWhere('t.position ' . $sign . ' :position')
                ->setParameter(':parentid', $parentId)
                ->setParameter(':position', $position)
                ->getQuery()
                ->getResult();
        }
    }

    /**
     * Max position by parent
     *
     * @param $parentId
     * @return mixed
     */
    public function maxPositionByParent($parentId)
    {
        if (is_null($parentId)) {
            $maxPosition = $this->getRepository()->createQueryBuilder('t')
                ->select(array('max(t.position)'))
                ->where('t.parent IS NULL')
                ->getQuery()
                ->getResult();
        } else {
            $maxPosition = $this->getRepository()->createQueryBuilder('t')
                ->select(array('max(t.position)'))
                ->where('t.parent = :parentid')
                ->setParameter(':parentid', $parentId)
                ->getQuery()
                ->getResult();
        }

        return $maxPosition[0][1];
    }
}
