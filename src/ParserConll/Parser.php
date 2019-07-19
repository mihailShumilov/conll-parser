<?php


namespace ParserConll;

/**
 * Class Parser
 * @package ParserConll
 */
class Parser {

    /**
     * @var string
     */
    private $conllText = '';

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var array
     */
    private $tree = [];


    private $usedTreeItems = [];

    /**
     * Parser constructor.
     *
     * @param string $conllText
     */
    public function __construct(string $conllText) {
        $this->conllText = $conllText;
    }

    /**
     *
     */
    public function parse(): void {
        $lines = explode("\n", $this->conllText);
        foreach ($lines as $line) {
            if (mb_strlen($line) > 0) {
                $this->processSingleLine($line);
            }
        }

        $this->tree = $this->buildTreeFromObjects($this->entities);

    }

    /**
     * @param string $line
     */
    private function processSingleLine(string $line) {
        $this->entities[] = new Entity($line);
    }

    /**
     * @param $items
     *
     * @return array|mixed
     */
    private function buildTreeFromObjects($items) {
        $childs = [];

        foreach ($items as $item) {
            $childs[$item->getParentID() ?? 0][] = $item;
        }

        foreach ($items as $item) {
            if (isset($childs[$item->getId()])) {
                $item->childs = $childs[$item->getId()];
            }
        }

        return $childs[0] ?? [];
    }

    /**
     * @return array
     */
    public function getEntities(): array {
        return $this->entities;
    }

    /**
     * @return mixed
     */
    public function getTree() {
        return $this->tree;
    }

    /**
     * @return false|string
     */
    public function toJSON() {
        return json_encode($this->tree);
    }

    public function getPersonsLabel(): array {
        $persons = [];
        foreach ($this->entities as $entity) {
            if ($entity->getRole() === Entity::ROLE_NAME) {
                $parentEntity = false;
                foreach ($this->entities as $pentity) {
                    if (($pentity->getRole() === Entity::ROLE_APPOS) && ($pentity->getId() === $entity->getParentID())) {
                        $parentEntity = $pentity;
                        break;
                    }
                }

                $personLabel = $entity->getWord();
                if ($parentEntity) {
                    $personLabel = $parentEntity->getWord() . ' ' . $personLabel;
                }
                $persons[] = $personLabel;
            }
        }

        return $persons;
    }

    public function getPersons(): array {
        //TODO check if that not fail other logic
        $this->usedTreeItems = [];

        $persons = [];
        foreach ($this->entities as $entity) {
            if ($entity->getRole() === Entity::ROLE_NAME) {
                $this->setAsUsed($entity->getId());
                $personData   = [];
                $parentEntity = false;
                foreach ($this->entities as $pentity) {
                    if (in_array($pentity->getRole(), [
                            Entity::ROLE_APPOS,
                            Entity::ROLE_NMOD
                        ]) && ($pentity->getId() === $entity->getParentID())) {
                        $parentEntity = $pentity;
                        break;
                    }
                }

                $personLabel = $entity->getWord();
                if ($parentEntity) {
                    $this->setAsUsed($parentEntity->getId());
                    $personLabel = $parentEntity->getWord() . ' ' . $personLabel;
                }
                $personData['label'] = $personLabel;
                list($role, $action) = $this->getPersonPosition($parentEntity ? $parentEntity : $entity);
                $personData['role']   = $role;
                $personData['action'] = $action;


                $persons[] = $personData;
            }
        }

        return $persons;
    }

    private function getPersonPosition(Entity $entity): array {
        $role = '';

        $entityID = $entity->getParentID();

        $vector = [];

        $search = true;

        $splitItem = false;

        while ($search) {

            $item = $this->getParentEntity($entityID);

            if (isset($item)) {
                $vector[] = $item->word;
                $entityID = $item->parentID;
                $this->setAsUsed($item->id);

                if (count($item->childs) > 1) {
                    $splitItem = $item;
                    $search    = false;
                }
            } else {
                $search = false;
            }
        }

        $roleSubj   = $this->getRoleSubj($splitItem);
        $roleAction = [];
        if ($roleActionItem = $this->getVerb($splitItem)) {
            $roleAction = $this->getPersonAction($roleActionItem);
        }

        if (!empty($roleSubj)) {
            array_push($vector, ...$roleSubj);
        }

//        if (!empty($roleAction)) {
//            echo PHP_EOL;
//            print_r($roleAction);
//            echo PHP_EOL;
//        }


        $role   = implode(' ', $vector);
        $action = implode(' ', $roleAction);

        return [$role, $action];
    }

    private function getParentEntity(int $entityID): ?\stdClass {

        return $this->findEntityInTree(json_decode($this->toJSON()), $entityID);
    }

    private function findEntityInTree(array $tree, int $entityID): ?\stdClass {

        foreach ($tree as $item) {
            if ((int)$item->id === $entityID) {
                return $item;
            } elseif (isset($item->childs) && (count($item->childs) > 0)) {
                $result = $this->findEntityInTree($item->childs, $entityID);
                if (isset($result)) {
                    return $result;
                }
            }
        }

        return null;
    }

    private function setAsUsed(int $id): void {
        $this->usedTreeItems[] = $id;
    }

    private function getRoleSubj(\stdClass $tree): array {
        $roleSubj = [];

        foreach ($tree->childs as $item) {
            if (!in_array((int)$item->id, $this->usedTreeItems)) {
                if (in_array($item->role, [Entity::ROLE_DOBJ, Entity::ROLE_AMOD])) {
                    $roleSubj[] = $item->word;
                    $this->setAsUsed($item->id);
                }

                if (isset($item->childs) && (!in_array($item->wordForm, [Entity::WORD_FORM_VERB]))) {
                    $nestedResult = $this->getRoleSubj($item);
                    if (!empty($nestedResult)) {
                        array_push($roleSubj, ...$nestedResult);
                    }
                }
            }
        }

        return $roleSubj;
    }


    private function getPersonAction(\stdClass $tree): array {
        $roleAction = [];

        $roleAction[$tree->id] = $tree->word;

        foreach ($tree->childs as $item) {

                if (in_array($item->wordForm, [Entity::WORD_FORM_VERB, Entity::WORD_FORM_ADJ, Entity::WORD_FORM_ADV])
                    || in_array($item->role, [Entity::ROLE_DOBJ, Entity::ROLE_CASE, Entity::ROLE_CONJ])) {

                    if (!in_array((int)$item->id, $this->usedTreeItems)) {
                        $roleAction[$item->id] = $item->word;
                    }

//                    if (!in_array($item->wordForm, [Entity::WORD_FORM_VERB])) {
                        $this->setAsUsed($item->id);
//                    }



                if (isset($item->childs)) {
                    $nestedResult = $this->getPersonAction($item);
                    if (!empty($nestedResult)) {
                        foreach($nestedResult as $nrID => $nr){
                            $roleAction[$nrID] = $nr;
                        }
                    }
                }
            }
        }

        ksort($roleAction);

        return $roleAction;
    }

    private function getVerb(\stdClass $tree): ?\stdClass {
        if ($tree->wordForm === Entity::WORD_FORM_VERB) {
            return $tree;
        }

        $search = true;
        $check  = $tree;
        while ($search) {
            if ($result = $this->getParentEntity($check->parentID)) {
                if ($result->wordForm === Entity::WORD_FORM_VERB) {
                    $search = false;
                    return $result;
                } else {
                    $check = $result;
                }
            } else {
                $search = false;
            }
        }

        return null;
    }

}
