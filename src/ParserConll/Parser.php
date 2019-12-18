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


    /**
     * @var array
     */
    private $usedTreeItems = [];

    public const ROLE_ROOT = 'ROOT';

    /**
     * @var Entity[]
     */
    private $persons = [];

    /**
     * @var integer[]
     */
    private $usedPersonsNodes = [];

    /**
     * @var string[]
     */
    private $subjectPositions = [];

    /**
     * @var string
     */
    private $message = '';

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
            if ($line !== '') {
                $this->processSingleLine($line);
            }
        }

        $this->buildTreeFromObjects();
        $this->processTree($this->tree);
        $this->message = $this->buildMessage($this->entities);
    }

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @param $entities
     *
     * @return string
     */
    protected function buildMessage($entities): string {
        $wordArr = [];
        foreach ($entities as $entity) {
            $wordArr[$entity->getId()] = $entity->getWord();
        }
        ksort($wordArr);
        return implode(' ', $wordArr);
    }

    /**
     * @param string $line
     */
    private function processSingleLine(string $line) {
        $this->entities[] = new Entity($line);
    }

    /**
     *
     */
    private function buildTreeFromObjects(): void {
        $rootItem       = $this->getRootEntity();
        $item           = [];
        $item['entity'] = $rootItem;
        $item['child']  = $this->getChildItem($rootItem->getId());

        $this->tree[] = $item;
    }

    private function getRootEntity(): Entity {
        foreach ($this->entities as $entity) {
            if ($entity->getRole() === self::ROLE_ROOT) {
                return $entity;
            }
        }
    }

    private function getChildItem(int $parentId): array {
        $result = [];
        foreach ($this->entities as $entity) {
            if ($entity->getParentID() === $parentId) {
                $item           = [];
                $item['entity'] = $entity;
                $item['child']  = $this->getChildItem($entity->getId());
                $result[]       = $item;
            }
        }

        return $result;
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


    private function processTree(array $tree, int $level = 0): void {
//        echo "\n";
        foreach ($tree as $levelData) {
            /**
             * @var Entity $entity
             */
            $entity = $levelData['entity'];
//            $preText = str_pad('', $level, "\t");
//            print_r($preText . $level.' ');
//            print_r($preText . $entity->getWord().' '.$entity->getId());
//            echo "\n";
            if (in_array($entity->getRole(), [
                Entity::ROLE_APPOS,
                Entity::ROLE_NMOD,
                Entity::ROLE_NUMMOD,
                Entity::ROLE_NSUBJ,
                Entity::ROLE_OBJ,
                Entity::ROLE_IOBJ
            ], false)) {
                $this->processSubjectNode($entity, $levelData['child']);
            }
            if (!empty($levelData['child'])) {
                $this->processTree($levelData['child'], $level + 1);
            }
        }
    }

    /**
     * @param Entity   $entity
     * @param Entity[] $child
     */
    private function processSubjectNode(Entity $entity, array $child = []) {
        if (!in_array($entity->getId(), $this->usedPersonsNodes, false)) {

            //if node has child with role 'appos' - that is node with subject position
            if (!empty($child)) {
                foreach ($child as $childData) {
                    $item = $childData['entity'];
                    if (in_array($item->getRole(), [
                        Entity::ROLE_APPOS
                    ], false)) {
                        $this->subjectPositions[$item->getId()] = $this->getPosition($entity, $child);
                    }
                }
            }

            $subjectLabel = $entity->getWord();
            if (!empty($child)) {
                foreach ($child as $childData) {
                    $item = $childData['entity'];
                    if (in_array($item->getRole(), [
                        Entity::ROLE_DOBJ,
                        Entity::ROLE_NAME,
                        Entity::ROLE_NUMMOD
                    ], false)) {
                        $subjectLabel             .= ' ' . $item->getWord();
                        $this->usedPersonsNodes[] = $item->getId();
                    }
                }
            }


            if ($subjectLabel) {
                $this->usedPersonsNodes[] = $entity->getId();
                $person                   = [];
                $person['name']           = $subjectLabel;
                if (isset($this->subjectPositions[$entity->getId()])) {
                    $person['position'] = $this->subjectPositions[$entity->getId()];
                }
                $this->persons[] = $person;
            }


        }
    }


    private function getPosition(Entity $entity, array $child = []): string {
        $positionLabels = [];

        $positionLabels[$entity->getId()] = $entity->getWord();

        if (!empty($child)) {
            foreach ($child as $childData) {
                $item = $childData['entity'];
                if (in_array($item->getRole(), [
                    Entity::ROLE_DOBJ,
                    Entity::ROLE_AMOD,
                    Entity::ROLE_CASE,
                    Entity::ROLE_NMOD
                ], false)) {
                    $positionLabels[$item->getId()] = $item->getWord();
                    if (!empty($childData['child'])) {
                        $nextLevel      = $this->getAllChild($childData['child']);
                        $positionLabels += $nextLevel;
                    }
                }
            }
        }

        ksort($positionLabels, SORT_NUMERIC);

        return implode(' ', $positionLabels);
    }

    /**
     * @return Entity[]
     */
    public function getPersons(): array {
        return $this->persons;
    }

    /**
     * @return array
     */
    public function getEntities(): array {
        return $this->entities;
    }

    private function getAllChild(array $child = []): array {
        $result = [];

        foreach ($child as $childData) {
            $item                   = $childData['entity'];
            $result[$item->getId()] = $item->getWord();
            if (!empty($childData['child'])) {
                $nextLevel = $this->getAllChild($childData['child']);
                $result    += $nextLevel;
            }
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

}
